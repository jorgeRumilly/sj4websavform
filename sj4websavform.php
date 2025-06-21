<?php

class Sj4websavform extends Module
{
    public function __construct()
    {
        $this->name = 'sj4websavform';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'SJ4WEB.FR';
        $this->need_instance = 0;
        $this->is_configurable = true;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('SJ4WEB - SAV Request Form',[],'Modules.Sj4websavform.Admin');
        $this->description = $this->trans('Allows customers to submit support (SAV) requests with file uploads.',[],'Modules.Sj4websavform.Admin');;

        $this->ps_versions_compliancy = ['min' => '1.7.8.5','max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->installDatabase()
            && $this->installMeta()
            && $this->setControllerLayouts()
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall()
            &&    $this->uninstallMeta()
            && $this->uninstallDatabase();
    }

    protected function installDatabase()
    {
        $sql = '
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sj4web_savform_request` (
            `id_savform_request` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_customer` INT UNSIGNED DEFAULT NULL,
            `email` VARCHAR(255) NOT NULL,
            `firstname` VARCHAR(128),
            `lastname` VARCHAR(128),
            `phone` VARCHAR(64),
            `intervention_address` TEXT,
            `order_reference` VARCHAR(64),
            `product_types` TEXT DEFAULT NULL COMMENT "JSON list of selected product types",
            `nature` VARCHAR(64) DEFAULT NULL,
            `nature_other` VARCHAR(255) DEFAULT NULL,
            `delai` VARCHAR(32) DEFAULT NULL,
            `subject` VARCHAR(255),
            `message` TEXT,
            `attachments` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_savform_request`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;
    ';

        return Db::getInstance()->execute($sql);
    }

    protected function uninstallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'sj4web_savform_request`;';
        return Db::getInstance()->execute($sql);
    }

    public function setControllerLayouts()
    {
        $theme_name = $this->context->shop->theme->getName();
        $theme_repo = (new PrestaShop\PrestaShop\Core\Addon\Theme\ThemeManagerBuilder($this->context, Db::getInstance()))->buildRepository();
        $theme = $theme_repo->getInstanceByName($theme_name);
        $layouts = $theme->getPageLayouts();
        $layouts['module-' . $this->name . '-savrequest'] = 'layout-full-width';
        $this->context->shop->theme->setPageLayouts($layouts);
        return true;
    }

    public function installMeta($controller_name = 'savrequest')
    {
        $languages = Language::getLanguages(false, false, true);
        $page = 'module-' . $this->name . '-' . $controller_name;

        $exists = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'meta WHERE page="' . pSQL($page) . '"'
        );

        if ($exists > 0) {
            return true;
        }

        $meta = new Meta();
        $meta->page = $page;
        $meta->configurable = 1;

        foreach ($languages as $id_lang) {
            $meta->title[$id_lang] = 'Support Request';
            $meta->url_rewrite[$id_lang] = 'sav-formulaire';
        }

        return $meta->save();
    }

    public function uninstallMeta($controller_name = 'savrequest')
    {
        $page = 'module-' . $this->name . '-' . $controller_name;

        $meta_data = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'meta WHERE page="' . pSQL($page) . '"'
        );

        if ($meta_data && isset($meta_data['id_meta'])) {
            $meta = new Meta((int)$meta_data['id_meta']);
            $meta->delete();

            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                Db::getInstance()->execute(
                    'DELETE FROM ' . _DB_PREFIX_ . 'theme_meta WHERE id_meta = ' . (int)$meta_data['id_meta']
                );
            }
        }

        return true;
    }

    public function hookModuleRoutes()
    {
        return [
            'module-sj4websavform-savrequest' => [
                'controller' => 'savrequest',
                'rule' => 'sav-formulaire',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                    'controller' => 'savrequest',
                ],
            ],
        ];
    }

    public function hookDisplayHeader()
    {
        if (Tools::getValue('controller') === 'savrequest') {
            $this->context->controller->registerStylesheet(
                'module-sj4websavform',
                'modules/' . $this->name . '/views/css/savform.css',
                ['media' => 'all', 'priority' => 150]
            );
            $this->context->controller->registerJavascript(
                'module-sj4websavform',
                'modules/' . $this->name . '/views/js/savform.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    public static function getProductTypes()
    {
        return [
            'portes_garage' => 'Portes de Garage',
            'fenetres' => 'Fenêtres',
            'portes_fenetres' => 'Portes fenêtres',
            'volets' => 'Volets',
            'portes_entree' => 'Portes d\'entrée',
            'pergolas' => 'Pergolas bioclimatique',
            'veranda' => 'Véranda - Toiture plate',
            'carport' => 'Carport',
            'garde_corps' => 'Garde-Corps',
            'portails' => 'Portails / Portillons',
            'stores' => 'Stores à banne',
            'palines' => 'Palines',
            'vitrages' => 'Vitrages',
            'accessoires' => 'Accessoires',
        ];
    }

    public static function getNatures()
    {
        return [
            'reglage' => 'Réglage',
            'panne_moteur' => 'Panne moteur',
            'vitrage_casse' => 'Vitrage cassé',
            'defaut_etancheite' => 'Défaut d\'étanchéité',
            'autre' => 'Autre',
        ];
    }

    public static function getDelais()
    {
        return [
            'prioritaire' => 'Prioritaire',
            'classique' => 'Classique',
            'non_prioritaire' => 'Non prioritaire',
        ];
    }

    public static function getTemplateVarOrders()
    {
        $orders = [];
        if (isset(Context::getContext()->customer) && Context::getContext()->customer->isLogged()) {
            $customer_orders = Order::getCustomerOrders(Context::getContext()->customer->id);
            foreach ($customer_orders as $customer_order) {
                $myOrder = new Order((int) $customer_order['id_order']);

                if (Validate::isLoadedObject($myOrder)) {
                    $orders[$customer_order['id_order']] = $customer_order;
                    $orders[$customer_order['id_order']]['products'] = $myOrder->getProducts();
                }
            }
        }
        return $orders;
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }


}
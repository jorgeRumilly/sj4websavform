<?php

class Sj4websavform extends Module
{
    protected $tabname = '';
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
        $this->displayName = $this->trans('SJ4WEB - SAV Request Form', [], 'Modules.Sj4websavform.Admin');
        $this->description = $this->trans('Allows customers to submit support (SAV) requests with file uploads.', [], 'Modules.Sj4websavform.Admin');;
        $this->tabname = $this->trans('SAV Requests', [], 'Modules.Sj4websavform.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.8.5', 'max' => _PS_VERSION_];
//        $this->installAdminTab();
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('SJ4WEBSAVFORM_ACTIVE', 0)
            && Configuration::updateValue('SJ4WEBSAVFORM_CONTACT_ID', 0)
            && Configuration::updateValue('SJ4WEBSAVFORM_CC_EMAILS', '')
            && $this->installDatabase()
            && $this->installMeta()
            && $this->setControllerLayouts()
            && $this->installAdminTab()
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('SJ4WEBSAVFORM_ACTIVE')
            && Configuration::deleteByName('SJ4WEBSAVFORM_CONTACT_ID')
            && Configuration::deleteByName('SJ4WEBSAVFORM_CC_EMAILS')
            && $this->uninstallMeta()
            && $this->uninstallDatabase()
            && $this->uninstallAdminTab();
    }

    protected function installDatabase()
    {
        $sql = '
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'sj4web_savform_request` (
            `id_savform_request` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_customer` INT UNSIGNED DEFAULT NULL,
            `email` VARCHAR(255) NOT NULL,
            `firstname` VARCHAR(128),
            `lastname` VARCHAR(128),
            `phone` VARCHAR(64),
            `intervention_address` TEXT,
            `id_order` INT UNSIGNED DEFAULT 0,
            `order_reference` VARCHAR(64),
            `product_types` TEXT DEFAULT NULL COMMENT "JSON list of selected product types",
            `nature` VARCHAR(64) DEFAULT NULL,
            `nature_other` VARCHAR(255) DEFAULT NULL,
            `delai` VARCHAR(32) DEFAULT NULL,
            `subject` VARCHAR(255),
            `message` TEXT,
            `attachments` TEXT,
            `sent` TINYINT(1) NOT NULL DEFAULT 0 COMMENT "0 = not sent, 1 = sent",
            `processed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT "0 = not processed, 1 = processed",
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_savform_request`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;
    ';

        return Db::getInstance()->execute($sql);
    }

    protected function uninstallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'sj4web_savform_request`;';
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

    /**
     * Install the admin tab for the module.
     * This creates a parent tab and a child tab for managing SAV requests.
     *
     * @return bool
     */
    protected function installAdminTab()
    {

        $id_tab = (int)Tab::getIdFromClassName('AdminSj4webSavformRequests');
        if (!$id_tab) {
            $tab = new Tab();
            $tab->class_name = 'AdminSj4webSavformRequests';
            $tab->module = $this->name;
            $tab->active = 1;
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentCustomerThreads'); // Parent tab ID
            $tab->name = [];
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[$lang['id_lang']] = $this->tabname;
            }
            $tab->add();
        }
        return true;
    }

    /**
     * Uninstall the admin tab for the module.
     * This removes the child tab and the parent tab.
     *
     * @return bool
     */
    protected function uninstallAdminTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminSj4webSavformRequests');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
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


    /**
     * Get the content of the module configuration page.
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit_sj4websavform')) {
            // Save values
            Configuration::updateValue('SJ4WEBSAVFORM_ACTIVE', (int)Tools::getValue('SJ4WEBSAVFORM_ACTIVE'));
            Configuration::updateValue('SJ4WEBSAVFORM_CONTACT_ID', (int)Tools::getValue('SJ4WEBSAVFORM_CONTACT_ID'));
            Configuration::updateValue('SJ4WEBSAVFORM_CC_EMAILS', Tools::getValue('SJ4WEBSAVFORM_CC_EMAILS'));

            $output .= $this->displayConfirmation($this->trans('Settings updated.', [], 'Modules.Sj4websavform.Admin'));
        }

        $contacts = Contact::getContacts($this->context->language->id);
        $options = [];
        foreach ($contacts as $contact) {
            $options[] = [
                'id_option' => (int)$contact['id_contact'],
                'name' => $contact['name'] . ' (' . $contact['email'] . ')',
            ];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('SAV Form Settings', [], 'Modules.Sj4websavform.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable SAV form', [], 'Modules.Sj4websavform.Admin'),
                        'name' => 'SJ4WEBSAVFORM_ACTIVE',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Modules.Sj4websavform.Admin')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Modules.Sj4websavform.Admin')
                            ]
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Default contact for SAV emails', [], 'Modules.Sj4websavform.Admin'),
                        'name' => 'SJ4WEBSAVFORM_CONTACT_ID',
                        'options' => [
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('CC email addresses (one per line)', [], 'Modules.Sj4websavform.Admin'),
                        'name' => 'SJ4WEBSAVFORM_CC_EMAILS',
                        'rows' => 5,
                        'cols' => 40,
                        'desc' => $this->trans('Enter one email per line or separated by commas.', [], 'Modules.Sj4websavform.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Modules.Sj4websavform.Admin')
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submit_sj4websavform';
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->fields_value['SJ4WEBSAVFORM_ACTIVE'] = Configuration::get('SJ4WEBSAVFORM_ACTIVE');
        $helper->fields_value['SJ4WEBSAVFORM_CONTACT_ID'] = Configuration::get('SJ4WEBSAVFORM_CONTACT_ID');
        $helper->fields_value['SJ4WEBSAVFORM_CC_EMAILS'] = Configuration::get('SJ4WEBSAVFORM_CC_EMAILS');

        return $output . $helper->generateForm([$fields_form]);
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
                $myOrder = new Order((int)$customer_order['id_order']);

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
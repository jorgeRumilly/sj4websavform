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
            && $this->registerHook('moduleRoutes');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDatabase();
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
    public function hookModuleRoutes()
    {
        return [
            'module-sj4web_savform-savrequest' => [
                'controller' => 'savrequest',
                'rule' => 'sav-formulaire',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ],
            ],
        ];
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }


}
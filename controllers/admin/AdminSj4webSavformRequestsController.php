<?php

class AdminSj4webSavformRequestsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->module = Module::getInstanceByName('sj4websavform');
        $this->bootstrap = true;
        $this->table = 'sj4web_savform_request';
//        $this->className = 'Sj4webSavformRequests'; // pour plus tard si on crée un objet modèle
        $this->lang = false;
        $this->explicitSelect = true;
        $this->display = 'list';

        parent::__construct();
    }

    public function initContent()
    {
        if (Tools::getIsset('view' . $this->table)) {
            $this->display = 'view';
        }

        parent::initContent();

        if ($this->display === 'view') {
            return;
        }

        $this->meta_title = $this->trans('Service After Sales Requests', [], 'Modules.Sj4websavform.Admin');

        $fields_list = [
            'id_savform_request' => [
                'title' => $this->trans('ID', [], 'Modules.Sj4websavform.Admin'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'date_add' => [
                'title' => $this->trans('Date', [], 'Modules.Sj4websavform.Admin'),
                'type' => 'datetime',
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'firstname' => [
                'title' => $this->trans('Firstname', [], 'Modules.Sj4websavform.Admin'),
            ],
            'lastname' => [
                'title' => $this->trans('Lastname', [], 'Modules.Sj4websavform.Admin'),
            ],
            'email' => [
                'title' => $this->trans('Email', [], 'Modules.Sj4websavform.Admin'),
            ],
            'order_reference' => [
                'title' => $this->trans('Order reference', [], 'Modules.Sj4websavform.Admin'),
            ],
            'nature' => [
                'title' => $this->trans('Nature', [], 'Modules.Sj4websavform.Admin'),
            ],
            'processed' => [
                'title' => $this->trans('Processed', [], 'Modules.Sj4websavform.Admin'),
                'type' => 'bool',
                'align' => 'center',
                'active' => 'processed', // pas de toggling automatique sans objet
                'class' => 'fixed-width-xs'
            ],
        ];

        $this->_select = '';
        $this->_join = '';

        $this->actions = ['view', 'delete', 'markProcessed'];

        $helper = new HelperList();
        $helper->module = $this->module;
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_savform_request';
        $helper->title = $this->trans('Sav requests list', [], 'Modules.Sj4websavform.Admin');
        $helper->table = $this->table;
        $helper->token = Tools::getAdminTokenLite('AdminSj4webSavformRequests');
//        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;
        $helper->currentIndex = self::$currentIndex;
        $helper->actions = $this->actions;
        $helper->show_toolbar = true;
        $helper->tpl_vars['pagination'] = [20, 50, 100, 300];
        $helper->tpl_vars['show_toolbar'] = true;
        $helper->tpl_vars['show_pagination'] = true;
        $helper->tpl_vars['fields_list'] = $fields_list;
        $this->context->smarty->assign([
            'content' => $helper->generateList(
                Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'sj4web_savform_request'),
                $fields_list
            ),
        ]);

    }


    public function renderView()
    {
        $id = (int)Tools::getValue('id_savform_request');
        $data = Db::getInstance()->getRow(
            'SELECT * FROM '._DB_PREFIX_.'sj4web_savform_request WHERE id_savform_request = '.(int)$id
        );

        if (!$data) {
            return parent::renderView();
        }

        $fieldLabels = $this->getFieldLabelsMapping();

        $displayData = [];
        $order_link = '';
        $order_ref = '';
        $order_id = 0;
        foreach ($data as $field => $value) {
            if ($field === 'product_types' && $value) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = implode(', ', $decoded);
                }
            }
            if($field == 'id_order' && $value){
                $order  = new Order((int)$value);
                if (Validate::isLoadedObject($order)) {
                    $order_id = $order->id;
                    $order_link =  Link::getAdminLink('AdminOrders', true, ['id_order' => (int)$order_id, 'vieworder' => '']);
                    $order_ref = $order->order_ref;
                }
            }
            $displayData[$field] = [
                'field' => $field,
                'label' => $fieldLabels[$field] ?? $field,
                'value' => $value,
            ];
        }

        $id = (int)Tools::getValue('id_savform_request');

        $backUrl = self::$currentIndex.'&token='.$this->token;

        $processUrl = self::$currentIndex
            . '&process' . $this->table
            . '&' . $this->identifier . '=' . $id
            . '&token=' . $this->token;

        $this->context->smarty->assign([
            'displayData' => $displayData,
            'order_link' => $order_link,
            'order_ref' => $order_ref,
            'order_id' => $order_id ?? 0,
            'back_url' => $backUrl,
            'process_url' => $processUrl,
            'module_dir' => $this->module->getPathUri(),
        ]);

        return $this->module->display(
            $this->module->getLocalPath(),
            'views/templates/admin/view_request.tpl'
        );
    }


    public function renderList()
    {
        $this->addRowAction('markProcessed');
    }

    public function displayMarkProcessedLink($token = null, $id)
    {
        $token = $token ?: $this->token;
        $href = self::$currentIndex . '&process' . $this->table . '&' . $this->identifier . '=' . $id . '&token=' . $token;
        return '<a href="' . $href . '" class="btn btn-default"><i class="icon-check"></i> ' . $this->l('Mark as processed') . '</a>';
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('delete' . $this->table)) {
            $id = (int)Tools::getValue($this->identifier);
            Db::getInstance()->delete(_DB_PREFIX_ . 'sj4web_savform_request', 'id_savform_request = ' . $id);
            Tools::redirectAdmin(self::$currentIndex . '&conf=1&token=' . $this->token);
        } elseif (Tools::isSubmit('process' . $this->table)) {
            $id = (int)Tools::getValue($this->identifier);
            Db::getInstance()->update('sj4web_savform_request',
                ['processed' => 1],
                'id_savform_request = ' . $id
            );
            Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
        }
    }

    public function getFieldLabelsMapping()
    {
        return [
            'id_savform_request' => $this->trans('Request ID', [], 'Modules.Sj4websavform.Admin'),
            'id_customer' => $this->trans('Customer ID', [], 'Modules.Sj4websavform.Admin'),
            'email' => $this->trans('Email', [], 'Modules.Sj4websavform.Admin'),
            'firstname' => $this->trans('Firstname', [], 'Modules.Sj4websavform.Admin'),
            'lastname' => $this->trans('Lastname', [], 'Modules.Sj4websavform.Admin'),
            'phone' => $this->trans('Phone', [], 'Modules.Sj4websavform.Admin'),
            'intervention_address' => $this->trans('Intervention Address', [], 'Modules.Sj4websavform.Admin'),
            'id_order' => $this->trans('Order ID', [], 'Modules.Sj4websavform.Admin'),
            'order_reference' => $this->trans('Order reference', [], 'Modules.Sj4websavform.Admin'),
            'product_types' => $this->trans('Product types', [], 'Modules.Sj4websavform.Admin'),
            'nature' => $this->trans('Nature', [], 'Modules.Sj4websavform.Admin'),
            'nature_other' => $this->trans('Nature (other)', [], 'Modules.Sj4websavform.Admin'),
            'delai' => $this->trans('Delay', [], 'Modules.Sj4websavform.Admin'),
            'subject' => $this->trans('Subject', [], 'Modules.Sj4websavform.Admin'),
            'message' => $this->trans('Message', [], 'Modules.Sj4websavform.Admin'),
            'attachments' => $this->trans('Attachments', [], 'Modules.Sj4websavform.Admin'),
            'sent' => $this->trans('Mail sent', [], 'Modules.Sj4websavform.Admin'),
            'processed' => $this->trans('Processed', [], 'Modules.Sj4websavform.Admin'),
            'date_add' => $this->trans('Date added', [], 'Modules.Sj4websavform.Admin'),
        ];

    }


}

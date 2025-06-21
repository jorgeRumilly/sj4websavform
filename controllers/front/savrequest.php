<?php

class Sj4websavformSavrequestModuleFrontController extends ModuleFrontController
{
    public $php_self = 'module-sj4websavform-savrequest';

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'form_action' => $this->context->link->getModuleLink('sj4websavform', 'savrequest'),
            // on préparera plus tard $form_data, $product_types, $is_logged, etc.
        ]);

        // On peut charger les données du formulaire ici si nécessaire
        $this->context->smarty->assign([
            'form_action'     => $this->context->link->getModuleLink('sj4websavform', 'savrequest'),
            'form_data'       => $this->getFormData(),
            'is_logged'       => $this->context->customer->isLogged(),
            'token'           => Tools::getToken(false),
            'customer_orders' => Sj4websavform::getTemplateVarOrders(),
            'product_types'   => Sj4websavform::getProductTypes(),
            'natures' => Sj4websavform::getNatures(),
            'delais' => Sj4websavform::getDelais()
        ]);

        $this->setTemplate('module:sj4websavform/views/templates/front/sav_request.tpl');
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();

        $page['meta']['title'] = $this->trans(
            'Support Request',
            [],
            'Modules.Sj4websavform.Shop'
        );

        $page['meta']['description'] = $this->trans(
            'Submit a support request using this form.',
            [],
            'Modules.Sj4websavform.Shop'
        );

        $page['meta']['canonical'] = $this->context->link->getModuleLink(
            'sj4websavform',
            'savrequest'
        );

        return $page;
    }

    public function getFormData() {
        $form_data = [
            'firstname' => '',
            'lastname' => '',
            'email' => '',
            'phone' => '',
            'intervention_address' => '',
            'order_reference' => '',
            'product_types' => [],
            'subject' => '',
            'message' => '',
        ];

        if ($this->context->customer->isLogged()) {
            $form_data['firstname'] = $this->context->customer->firstname;
            $form_data['lastname'] = $this->context->customer->lastname;
            $form_data['email'] = $this->context->customer->email;
        }
        return $form_data;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit_savform')) {
            $form = [
                'firstname' => Tools::getValue('firstname'),
                'lastname' => Tools::getValue('lastname'),
                'email' => Tools::getValue('email'),
                'phone' => Tools::getValue('phone'),
                'intervention_address' => Tools::getValue('intervention_address'),
                'order_reference' => Tools::getValue('order_reference'),
                'product_types' => Tools::getValue('product_types'), // array
                'subject' => Tools::getValue('subject'),
                'message' => Tools::getValue('message'),
                'nature' => Tools::getValue('nature'),
                'nature_other' => Tools::getValue('nature_other'),
                'delai' => Tools::getValue('delai'),
            ];

            // Sanitize + fallback
            $form['product_types'] = is_array($form['product_types']) ? $form['product_types'] : [];

            // Validation minimaliste (à étoffer si besoin)
            if (!$form['firstname'] || !$form['lastname'] || !$form['email'] || !$form['message']) {
                $this->errors[] = $this->module->l('Please fill all required fields.', 'savrequest');
                return;
            }

            // Uploads
            $attachments = [];
            if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                foreach ($_FILES['attachments']['name'] as $i => $name) {
                    if (!$_FILES['attachments']['error'][$i]) {
                        $tmp_name = $_FILES['attachments']['tmp_name'][$i];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $newName = uniqid('sav_') . '.' . $ext;
                        $uploadDir = _PS_MODULE_DIR_ . $this->module->name . '/uploads/';
                        @mkdir($uploadDir, 0755, true);
                        move_uploaded_file($tmp_name, $uploadDir . $newName);
                        $attachments[] = $newName;
                    }
                }
            }

            // Insertion en base
            Db::getInstance()->insert('sj4web_savform_request', [
                'id_customer' => (int)$this->context->customer->id,
                'firstname' => pSQL($form['firstname']),
                'lastname' => pSQL($form['lastname']),
                'email' => pSQL($form['email']),
                'phone' => pSQL($form['phone']),
                'intervention_address' => pSQL($form['intervention_address']),
                'order_reference' => pSQL($form['order_reference']),
                'product_types' => pSQL(json_encode($form['product_types'])),
                'subject' => pSQL($form['subject']),
                'message' => pSQL($form['message']),
                'nature' => pSQL($form['nature']),
                'nature_other' => pSQL($form['nature_other']),
                'delai' => pSQL($form['delai']),
                'attachments' => pSQL(json_encode($attachments)),
                'date_add' => date('Y-m-d H:i:s'),
            ]);

            $this->context->smarty->assign('confirmation', true);

            // Thread + Message SAV
            if ($this->context->customer->isLogged()) {
                $id_customer = (int)$this->context->customer->id;
                $id_shop = (int)$this->context->shop->id;
                $thread = new CustomerThread();
                $thread->id_shop = $id_shop;
                $thread->id_lang = (int)$this->context->language->id;
                $thread->id_customer = $id_customer;
                $thread->email = $form['email'];
                $thread->status = 'open';
                $thread->token = Tools::passwdGen(12);
                $thread->add();

                $message = new CustomerMessage();
                $message->id_customer_thread = $thread->id;
                $message->id_employee = 0;
                $message->message = $form['message'];
                $message->private = false;
                $message->add();
            }

            Tools::redirect($this->context->link->getPageLink('contact', true, null, ['savform_success' => 1]));
        }
    }

}

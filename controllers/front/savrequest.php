<?php

class Sj4websavformSavrequestModuleFrontController extends ModuleFrontController
{
    public $php_self = 'module-sj4websavform-savrequest';

    public function initContent()
    {
        parent::initContent();

        $token = Tools::passwdGen(16);
        $this->context->cookie->savFormToken = $token;
        $this->context->cookie->savFormTokenTTL = time() + 3600; // 1h de validité

        $this->context->smarty->assign([
            'form_action' => $this->context->link->getModuleLink('sj4websavform', 'savrequest'),
            // on préparera plus tard $form_data, $product_types, $is_logged, etc.
        ]);

        // On peut charger les données du formulaire ici si nécessaire
        $this->context->smarty->assign([
            'form_action' => $this->context->link->getModuleLink('sj4websavform', 'savrequest'),
            'form_data' => $this->getFormData(),
            'is_logged' => $this->context->customer->isLogged(),
            'token' => $token,
            'customer_orders' => Sj4websavform::getTemplateVarOrders(),
            'product_types' => Sj4websavform::getProductTypes(),
            'natures' => Sj4websavform::getNatures(),
            'delais' => Sj4websavform::getDelais()
        ]);
        if (Tools::getValue('savform_success')) {
            $this->context->smarty->assign('confirmation', true);
        }

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

    public function getFormData()
    {
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

            // Anti-bot : honeypot + token expiré/invalide
            $clientToken = Tools::getValue('token');
            $serverToken = $this->context->cookie->savFormToken;
            $tokenTTL = (int)$this->context->cookie->savFormTokenTTL;

            if (Tools::getValue('url') !== ''
                || empty($serverToken)
                || $clientToken !== $serverToken
                || $tokenTTL < time()
            ) {
                $this->errors[] = $this->trans(
                    'An error occurred while sending the message, please try again.',
                    [],
                    'Modules.Sj4websavform.Shop'
                );
                $this->createNewToken();
                return;
            }

            $form = [
                'firstname' => Tools::getValue('firstname'),
                'lastname' => Tools::getValue('lastname'),
                'email' => Tools::getValue('email'),
                'phone' => Tools::getValue('phone'),
                'intervention_address' => Tools::getValue('intervention_address'),
                'id_order' => Tools::getValue('id_order'),
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

            // Validation champ par champ
            if (empty($form['firstname'])) {
                $this->errors[] = $this->trans('First name is required.', [], 'Modules.Sj4websavform.Shop');
            }
            if (empty($form['lastname'])) {
                $this->errors[] = $this->trans('Last name is required.', [], 'Modules.Sj4websavform.Shop');
            }
            if (empty($form['email'])) {
                $this->errors[] = $this->trans('Email is required.', [], 'Modules.Sj4websavform.Shop');
            } elseif (!Validate::isEmail($form['email'])) {
                $this->errors[] = $this->trans('Invalid email address.', [], 'Modules.Sj4websavform.Shop');
            }
            if (empty($form['message'])) {
                $this->errors[] = $this->trans('Message is required.', [], 'Modules.Sj4websavform.Shop');
            }
            if (!Validate::isCleanHtml($form['message'])) {
                $this->errors[] = $this->trans(
                    'Invalid message',
                    [],
                    'Modules.Sj4websavform.Shop'
                );
            }
            // On peut définir les extensions autorisées pour les fichiers joints
            $attachments = $this->handleAttachments();

            // Si erreurs, on stoppe ici
            if (!empty($this->errors)) {
                return;
            }

            // Insertion en base
            $idRequest = $this->insertionEnBase($form, $attachments);

            // Envoi du message (email de notification)
            $this->handleMessageSend($form, $attachments);
            // Si erreurs, on stoppe ici
            if (!empty($this->errors)) {
                return;
            }

            // Mets à jour la base de données pour marquer la demande comme envoyée
            if ($idRequest && empty($this->errors)) {
                Db::getInstance()->update(
                    'sj4web_savform_request',
                    ['sent' => 1],
                    'id_savform_request = ' . (int)$idRequest
                );
            }

            $this->context->smarty->assign('confirmation', true);

            Tools::redirect($this->context->link->getModuleLink('sj4websavform', 'savrequest', ['savform_success' => 1]));
        }
    }

    /**
     * Gère l’upload des pièces jointes et retourne un tableau de noms de fichiers enregistrés.
     * En cas d’erreur, les messages sont ajoutés dans $this->errors[].
     */
    protected function handleAttachments(): array
    {
        $allowedExtensions = ['txt', 'rtf', 'doc', 'docx', 'png', 'jpeg', 'gif', 'jpg', 'heic', 'heif', 'webp', 'pdf'];
        $uploadDir = _PS_MODULE_DIR_ . $this->module->name . '/uploads/';
        @mkdir($uploadDir, 0755, true);

        $attachments = [];

        if (!empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
            foreach ($_FILES['attachments']['name'] as $i => $name) {
                $error = $_FILES['attachments']['error'][$i];

                if ($error !== UPLOAD_ERR_OK) {
                    $this->errors[] = $this->trans(
                        'Error uploading file: %name%',
                        ['%name%' => $name],
                        'Modules.Sj4websavform.Shop'
                    );
                    continue;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions)) {
                    $this->errors[] = $this->trans(
                        'File type not allowed: %name%',
                        ['%name%' => $name],
                        'Modules.Sj4websavform.Shop'
                    );
                    continue;
                }

                $newName = uniqid('sav_') . '.' . $ext;
                if (!move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $uploadDir . $newName)) {
                    $this->errors[] = $this->trans(
                        'Failed to save file: %name%',
                        ['%name%' => $name],
                        'Modules.Sj4websavform.Shop'
                    );
                    continue;
                }

                $attachments[] = $newName;
            }
        }

        return $attachments;
    }

    /**
     * Gère l’envoi du mail SAV et l’enregistrement dans customer_thread/message si client connu.
     *
     * @param array $form Données du formulaire
     * @param array $attachments Noms des fichiers (dans /uploads)
     */
    protected function handleMessageSend(array $form, array $attachments): void
    {

        $customer = $this->context->customer;
        $from = $form['email'];
        if (!$customer->id) {
            $customer->getByEmail($from);
        }

        $id_order = $form['id_order'] ?? 0;
        if ($id_order > 0) {
            $order = new Order($id_order);
            $id_order = (int)$order->id_customer === (int)$customer->id ? $id_order : 0;

        }

        $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($from, $id_order);

        if ($id_customer_thread) {
            $ct = new CustomerThread($id_customer_thread);
            $ct->status = 'open';
            $ct->id_lang = (int)$this->context->language->id;
            $ct->id_order = $id_order;
            $ct->id_contact = 1;
            $ct->update();
        } else {
            $ct = new CustomerThread();
            if (isset($customer->id)) {
                $ct->id_customer = (int)$customer->id;
            }
            $ct->id_shop = (int)$this->context->shop->id;
            $ct->id_order = $id_order;
            $ct->id_lang = (int)$this->context->language->id;
            $ct->email = $from;
            $ct->status = 'open';
            $ct->id_contact = 1;
            $ct->token = Tools::passwdGen(12);
            $ct->add();
        }
        if ($ct->id) {
            $lastMessage = CustomerMessage::getLastMessageForCustomerThread($ct->id);
            if ($lastMessage != $form['message']) {
                $cm = new CustomerMessage();
                $cm->id_customer_thread = $ct->id;
                $cm->message = $form['message'];
                $cm->ip_address = (string)ip2long(Tools::getRemoteAddr());
                $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
                $cm->add();
            } else {
                $mailAlreadySend = true;
            }
        }
        if (empty($mailAlreadySend)) {
            // Construction du mail
            $var_list = [
                '{firstname}' => $form['firstname'],
                '{lastname}' => $form['lastname'],
                '{email}' => $form['email'],
                '{phone}' => $form['phone'],
                '{intervention_address}' => $form['intervention_address'],
                '{order_reference}' => ((isset($order) && $order->reference) ? $order->reference : $form['order_reference']),
                '{product_types}' => implode(', ', $form['product_types']),
                '{subject}' => $form['subject'],
                '{nature}' => $form['nature'] === 'autre' ? $form['nature_other'] : $form['nature'],
                '{delai}' => $form['delai'],
                '{message}' => Tools::nl2br(Tools::htmlentitiesUTF8($form['message'])),
            ];

            $files = [];
            foreach ($attachments as $file) {
                $path = _PS_MODULE_DIR_ . $this->module->name . '/uploads/' . $file;
                if (file_exists($path)) {
                    $files[] = [
                        'content' => file_get_contents($path),
                        'name' => $file,
                        'mime' => mime_content_type($path),
                    ];
                }
            }

            $to = Configuration::get('SJ4WEBSAVFORM_EMAIL');
            $to = !empty($to) ? $to : Configuration::get('PS_SHOP_EMAIL'); // Fallback si pas de config
            $to = 'jorge.sj4web@gmail.com';

            try {
                $sentMail = Mail::Send(
                    (int)$this->context->language->id,
                    'sav_request', // template .tpl à créer dans /mails/
                    $this->trans('New SAV request', [], 'Emails.Subject'),
                    $var_list,
                    $to, // à configurer ou fallback
                    null,
                    $from,
                    null,
                    $files,
                    null,
                    _PS_MODULE_DIR_ . 'sj4websavform/mails/',
                    false,
                    (int)Context::getContext()->shop->id,
                    null,
                    $from
                );
                if (!$sentMail) {
                    $this->errors[] = $this->trans(
                        'An error occurred while sending the confirmation email.',
                        [],
                        'Modules.Sj4websavform.Shop'
                    );
                }
            } catch (Exception $e) {
                $this->errors[] = $this->trans(
                    'An error occurred while sending the confirmation email. %s',
                    [$e->getMessage()],
                    'Modules.Sj4websavform.Shop'
                );
            }
        }
    }

    /**
     * @param array $form
     * @param array $attachments
     * @return void
     * @throws PrestaShopDatabaseException
     */
    public function insertionEnBase(array $form, array $attachments): ?int
    {
        $success = Db::getInstance()->insert('sj4web_savform_request', [
            'id_customer' => (int)$this->context->customer->id,
            'firstname' => pSQL($form['firstname']),
            'lastname' => pSQL($form['lastname']),
            'email' => pSQL($form['email']),
            'phone' => pSQL($form['phone']),
            'intervention_address' => pSQL($form['intervention_address']),
            'id_order' => (int)$form['id_order'],
            'order_reference' => pSQL($form['order_reference']),
            'product_types' => pSQL(json_encode($form['product_types'])),
            'subject' => pSQL($form['subject']),
            'message' => pSQL($form['message']),
            'nature' => pSQL($form['nature']),
            'nature_other' => pSQL($form['nature_other']),
            'delai' => pSQL($form['delai']),
            'attachments' => pSQL(json_encode($attachments)),
            'sent' => 0,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        if (!$success) {
            return null;
        }

        return (int)Db::getInstance()->Insert_ID();
    }


    protected function createNewToken(): void
    {
        $token = Tools::passwdGen(16);
        $this->context->cookie->savFormToken = $token;
        $this->context->cookie->savFormTokenTTL = time() + 3600;
    }


}

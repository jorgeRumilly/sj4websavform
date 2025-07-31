<?php

class Sj4websavformSavrequestModuleFrontController extends ModuleFrontController
{
    public $php_self = 'module-sj4websavform-savrequest';

    public function initContent()
    {
        parent::initContent();

        if (!Configuration::get('SJ4WEBSAVFORM_ACTIVE')) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }


        if (Tools::getValue('inject') === '1') {
            $this->injectFakeSavRequests();
            exit;
        }

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
            'is_intervention_address' => Configuration::get('SJ4WEBSAVFORM_ADDRESS_ACTIVE', true),
            'display_nature' => Configuration::get('SJ4WEBSAVFORM_DISPLAY_NATURE', true),
            'display_priority' => Configuration::get('SJ4WEBSAVFORM_DISPLAY_PRIORITY', true),
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

        // Récupère la Meta configurée pour cette page
        $meta = Meta::getMetaByPage($this->context->language->id, $this->php_self);

        if ($meta) {
            if (!empty($meta['title'])) {
                $page['meta']['title'] = $meta['title'];
            }

            if (!empty($meta['description'])) {
                $page['meta']['description'] = $meta['description'];
            }

            $page['meta']['canonical'] = $this->context->link->getModuleLink(
                'sj4websavform',
                'savrequest'
            );

            if (!empty($meta['url_rewrite'])) {
                $page['meta']['canonical'] = $this->context->link->getPageLink($meta['url_rewrite']);
            }
        }

        if (empty($page['meta']['title'])) {
            $page['meta']['title'] = $this->trans('Support Request', [], 'Modules.Sj4websavform.Shop');
        }
        if (empty($page['meta']['description'])) {
            $page['meta']['description'] = $this->trans('Submit a support request using this form.', [], 'Modules.Sj4websavform.Shop');
        }
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
                'intervention_address' => Tools::getValue('intervention_address', ''),
                'zip_code' => Tools::getValue('zip_code', ''),
                'city' => Tools::getValue('city', ''),
                'id_order' => Tools::getValue('id_order'),
                'order_reference' => Tools::getValue('order_reference'),
                'product_types' => Tools::getValue('product_types'), // array
                'subject' => Tools::getValue('subject'),
                'message' => Tools::getValue('message'),
                'nature' => Tools::getValue('nature', null),
                'nature_other' => Tools::getValue('nature_other', null),
                'delai' => Tools::getValue('delai', null),
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

        $idContact = (int)Configuration::get('SJ4WEBSAVFORM_CONTACT_ID');
        if (!$idContact) {
            $idContact = 1;
        }
        $contact = new Contact($idContact);

        $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($from, $id_order);

        if ($id_customer_thread) {
            $ct = new CustomerThread($id_customer_thread);
            $ct->status = 'open';
            $ct->id_lang = (int)$this->context->language->id;
            $ct->id_order = $id_order;
            $ct->id_contact = $contact->id;
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
            $ct->id_contact = $contact->id;
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
            $info_address = (isset($form['intervention_address']) && !empty($form['intervention_address']))
                ? $form['intervention_address'] : $form['zip_code'] . ' ' . $form['city'];
            // Construction du mail
            $var_list = [
                '{firstname}' => $form['firstname'],
                '{lastname}' => $form['lastname'],
                '{email}' => $form['email'],
                '{phone}' => $form['phone'],
                '{intervention_address}' => $info_address,
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

            $to = $contact->email;
            $to = !empty($to) ? $to : Configuration::get('PS_SHOP_EMAIL'); // Fallback si pas de config

            $ccEmailsRaw = Configuration::get('SJ4WEBSAVFORM_CC_EMAILS');
            $ccEmails = [];

            if (!empty($ccEmailsRaw)) {
                // découper sur virgules OU retours à la ligne
                $ccLines = preg_split('/[\r\n,]+/', $ccEmailsRaw);
                foreach ($ccLines as $email) {
                    $email = trim($email);
                    if (Validate::isEmail($email)) {
                        $ccEmails[] = $email;
                    }
                }
            }

            try {
                $sentMail = Mail::Send(
                    (int)$this->context->language->id,
                    'sav_request', // template .tpl à créer dans /mails/
                    $this->trans('New SAV request', [], 'Modules.Sj4websavform.Shop'),
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
                    $ccEmails, // CC email,
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
        $id_order = (int)$form['id_order'];
        $order_ref = $form['order_reference'] ?? '';
        if($id_order > 0) {
            $order = new Order($id_order);
            if(Validate::isLoadedObject($order)) {
                $order_ref = $order->order_ref;
            }
        }

        $success = Db::getInstance()->insert('sj4web_savform_request', [
            'id_customer' => (int)$this->context->customer->id,
            'firstname' => pSQL($form['firstname']),
            'lastname' => pSQL($form['lastname']),
            'email' => pSQL($form['email']),
            'phone' => pSQL($form['phone']),
            'intervention_address' => pSQL($form['intervention_address']),
            'zip_code' => pSQL($form['zip_code']),
            'city' => pSQL($form['city']),
            'id_order' => (int)$form['id_order'],
            'order_reference' => pSQL($order_ref),
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

    public function injectFakeSavRequests(): void
    {
        $jsonPath = _PS_MODULE_DIR_ . 'sj4websavform/data/psans_sj4web_savform_request.json';
        if (!file_exists($jsonPath)) {
            echo 'Fichier JSON non trouvé.';
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($data)) {
            echo 'Contenu JSON invalide.';
            return;
        }

        // Répéter les données existantes pour avoir au moins 25 entrées
        $entries = [];
        while (count($entries) < 25) {
            foreach ($data as $row) {
                $entries[] = $row;
                if (count($entries) >= 25) {
                    break;
                }
            }
        }

        // @todo : vérifier si on c ok pour le zip_code et city
        $inserted = 0;
        foreach ($entries as $row) {
            // Nettoyage et préparation des champs
            $form = [
                'firstname' => $row['firstname'] ?? '',
                'lastname' => $row['lastname'] ?? '',
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
                'intervention_address' => $row['intervention_address'] ?? '',
                'zip_code' => $row['zip_code'] ?? '',
                'city' => $row['city'] ?? '',
                'id_order' => (int)($row['id_order'] ?? 0),
                'order_reference' => $row['order_reference'] ?? '',
                'product_types' => json_decode($row['product_types'], true) ?? [],
                'subject' => $row['subject'] ?? '',
                'message' => $row['message'] ?? '',
                'nature' => $row['nature'] ?? '',
                'nature_other' => $row['nature_other'] ?? '',
                'delai' => $row['delai'] ?? '',
            ];
            $attachments = json_decode($row['attachments'], true) ?? [];

            // Mock du customer context si absent
            $this->context->customer = new Customer((int)$row['id_customer']);
            if (!Validate::isLoadedObject($this->context->customer)) {
                $this->context->customer = new Customer(); // id=0
                $this->context->customer->id = 0;
            }

            $id = $this->insertionEnBase($form, $attachments);
            if ($id) {
                $inserted++;
            }
        }

        echo $inserted . ' demandes SAV injectées avec succès.';
    }


}

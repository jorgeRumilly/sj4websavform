<?php
class Sj4webSavformRequest extends ObjectModel
{
    public $id_savform_request;
    public $id_customer;
    public $firstname;
    public $lastname;
    public $email;
    public $phone;
    public $intervention_address;
    public $zip_code;
    public $city;
    public $id_order;
    public $order_reference;
    public $product_types;
    public $nature;
    public $nature_other;
    public $delai;
    public $subject;
    public $message;
    public $attachments;
    public $sent;
    public $processed;
    public $date_add;

    public static $definition = [
        'table' => 'sj4web_savform_request',
        'primary' => 'id_savform_request',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT],
            'firstname' => ['type' => self::TYPE_STRING],
            'lastname' => ['type' => self::TYPE_STRING],
            'email' => ['type' => self::TYPE_STRING],
            'phone' => ['type' => self::TYPE_STRING],
            'intervention_address' => ['type' => self::TYPE_STRING],
            'zip_code' => ['type' => self::TYPE_STRING],
            'city' => ['type' => self::TYPE_STRING],
            'id_order' => ['type' => self::TYPE_INT],
            'order_reference' => ['type' => self::TYPE_STRING],
            'product_types' => ['type' => self::TYPE_STRING],
            'nature' => ['type' => self::TYPE_STRING],
            'nature_other' => ['type' => self::TYPE_STRING],
            'delai' => ['type' => self::TYPE_STRING],
            'subject' => ['type' => self::TYPE_STRING],
            'message' => ['type' => self::TYPE_HTML],
            'attachments' => ['type' => self::TYPE_STRING],
            'sent' => ['type' => self::TYPE_BOOL],
            'processed' => ['type' => self::TYPE_BOOL],
            'date_add' => ['type' => self::TYPE_DATE],
        ],
    ];
}

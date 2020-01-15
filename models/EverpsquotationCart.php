<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

class EverpsquotationCart extends ObjectModel
{
    public $id;

    public $id_shop_group;

    public $id_shop;

    /** @var int Customer delivery address ID */
    public $id_address_delivery;

    /** @var int Customer invoicing address ID */
    public $id_address_invoice;

    /** @var int Customer currency ID */
    public $id_currency;

    /** @var int Customer ID */
    public $id_customer;

    /** @var int Guest ID */
    public $id_guest;

    /** @var int Language ID */
    public $id_lang;

    /** @var bool True if the customer wants a recycled package */
    public $recyclable = 0;

    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;

    /** @var string Gift message if specified */
    public $gift_message;

    /** @var bool Mobile Theme */
    public $mobile_theme;

    /** @var string Object creation date */
    public $date_add;

    /** @var string secure_key */
    public $secure_key;

    /** @var int Carrier ID */
    public $id_carrier = 0;

    /** @var string Object last modification date */
    public $date_upd;

    public $checkedTos = false;
    public $pictures;
    public $textFields;

    public $delivery_option;

    public $allow_seperated_package = false;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'everpsquotation_cart',
        'primary' => 'id_everpsquotation_cart',
        'fields' => array(
            'id_shop_group' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_invoice' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_currency' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_guest' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'recyclable' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'delivery_option' => array('type' => self::TYPE_STRING),
            'secure_key' => array('type' => self::TYPE_STRING, 'size' => 32),
            'allow_seperated_package' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public static function getEvercartByCustomerId($id_customer, $id_shop, $id_lang)
    {
        $sql = 'SELECT id_everpsquotation_cart 
        FROM '._DB_PREFIX_.'everpsquotation_cart
        WHERE id_customer = '.(int)$id_customer.'
        AND id_shop = '.(int)$id_shop.'
        AND id_lang = '.(int)$id_lang;
        return Db::getInstance()->getValue($sql);
    }

    public static function deleteEverQuoteCart($id_evercart)
    {
        $evercart = new EverpsquotationCart((int)$id_evercart);
        if (!$evercart->delete()) {
            die('cant delete quotation cart');
        } else {
            return true;
        }
    }
}

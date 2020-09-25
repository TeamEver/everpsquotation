<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

class EverpsquotationClass extends ObjectModel
{
    /** @var int Delivery address id */
    public $id_address_delivery;

    /** @var int Invoice address id */
    public $id_address_invoice;

    public $id_shop_group;

    public $id_shop;

    /** @var int Cart id */
    public $id_cart;

    /** @var int Currency id */
    public $id_currency;

    /** @var int Language id */
    public $id_lang;

    /** @var int Customer id */
    public $id_customer;

    /** @var int Carrier id */
    public $id_carrier;

    /** @var string Secure key */
    public $secure_key;

    /** @var bool Customer is ok for a recyclable package */
    public $recyclable = 1;

    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;

    /** @var string Gift message if specified */
    public $gift_message;

    /** @var bool Mobile Theme */
    public $mobile_theme;

    /** @var float Discounts total */
    public $total_discounts;

    public $total_discounts_tax_incl;
    public $total_discounts_tax_excl;

    /** @var float Total to pay */
    public $total_paid;

    /** @var float Total to pay tax included */
    public $total_paid_tax_incl;

    /** @var float Total to pay tax excluded */
    public $total_paid_tax_excl;

    /** @var float Products total */
    public $total_products;

    /** @var float Products total tax included */
    public $total_products_wt;

    /** @var float Shipping total */
    public $total_shipping;

    /** @var float Shipping total tax included */
    public $total_shipping_tax_incl;

    /** @var float Shipping total tax excluded */
    public $total_shipping_tax_excl;

    /** @var float Wrapping total */
    public $total_wrapping;

    /** @var float Wrapping total tax included */
    public $total_wrapping_tax_incl;

    /** @var float Wrapping total tax excluded */
    public $total_wrapping_tax_excl;

    /** @var bool Order validity: current order status is logable (usually paid and not canceled) */
    public $valid;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /**
     * @var string Order reference, this reference is not unique, but unique for a payment
     */
    public $reference;

    /**
     * @var int Round mode method used for this order
     */
    public $round_mode;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'everpsquotation_quotes',
        'primary' => 'id_everpsquotation_quotes',
        'fields' => array(
            'id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_address_invoice' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_currency' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_shop_group' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'secure_key' => array('type' => self::TYPE_STRING, 'validate' => 'isMd5'),
            'total_discounts' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_discounts_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_discounts_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_paid_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_paid_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_products' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_products_wt' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_shipping' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_shipping_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_shipping_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_wrapping' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_wrapping_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_wrapping_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'valid' => array('type' => self::TYPE_BOOL),
            'reference' => array('type' => self::TYPE_STRING),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        $is_admin = (is_object(Context::getContext()->controller)
            && Context::getContext()->controller->controller_type == 'admin');
        if ($this->id_customer && !$is_admin) {
            $customer = new Customer((int)$this->id_customer);
            $this->_taxCalculationMethod = Group::getPriceDisplayMethod((int)$customer->id_default_group);
        } else {
            $this->_taxCalculationMethod = Group::getDefaultPriceDisplayMethod();
        }
    }

    public static function evercartexists($id_cart)
    {
        return Db::getInstance()->delete('everpsquotation_cart', 'id_everpsquotation_cart = '.$id_cart)
            && Db::getInstance()->delete('everpsquotation_cart_product', 'id_everpsquotation_cart = '.$id_cart);
    }

    public static function evercopycart($id_cart)
    {
        $isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        $copyCart = Db::getInstance()->Execute(
            'INSERT INTO `'._DB_PREFIX_.'everpsquotation_cart`
            (
                id_shop_group,
                id_shop,
                id_carrier,
                delivery_option,
                id_lang,
                id_address_delivery,
                id_address_invoice,
                id_currency,
                id_customer,
                id_guest,
                secure_key,
                recyclable,
                allow_seperated_package,
                date_add,
                date_upd
            )
            SELECT
            id_shop_group,
            id_shop,
            id_carrier,
            delivery_option,
            id_lang,
            id_address_delivery,
            id_address_invoice,
            id_currency,
            id_customer,
            id_guest,
            secure_key,
            recyclable,
            allow_seperated_package,
            date_add,
            date_upd
            FROM `'._DB_PREFIX_.'cart`
            WHERE id_cart = '.$id_cart
        );
        if ($copyCart) {
            $quoteid = (int)Db::getInstance()->Insert_ID();
            if ((bool)$isSeven === true) {
                $copyCartProducts = Db::getInstance()->Execute(
                    'INSERT INTO `'._DB_PREFIX_.'everpsquotation_cart_product`
                    (
                        id_everpsquotation_cart,
                        id_product,
                        id_address_delivery,
                        id_shop,
                        id_product_attribute,
                        id_customization
                    )
                    SELECT
                        '.(int)$quoteid.',
                        id_product,
                        id_address_delivery,
                        id_shop,
                        id_product_attribute,
                        id_customization
                    FROM `'._DB_PREFIX_.'cart_product`
                    WHERE id_cart = '.(int)$id_cart
                );
            } else {
                $copyCartProducts = Db::getInstance()->Execute(
                    'INSERT INTO `'._DB_PREFIX_.'everpsquotation_cart_product`
                    (
                        id_everpsquotation_cart,
                        id_product,
                        id_address_delivery,
                        id_shop,
                        id_product_attribute
                    )
                    SELECT
                        '.(int)$quoteid.',
                        id_product,
                        id_address_delivery,
                        id_shop,
                        id_product_attribute
                    FROM `'._DB_PREFIX_.'cart_product`
                    WHERE id_cart = '.(int)$id_cart
                );
            }
            if ($copyCartProducts) {
                return true;
            }
        }
    }

    public static function erase($id_everpsquotation_quotes)
    {
        $everquote_obj = new EverpsquotationClass($id_everpsquotation_quotes);

        return $everquote_obj->delete()
                && Db::getInstance()->delete(
                    'everpsquotation_quote_detail',
                    'id_everpsquotation_quotes = '.$id_everpsquotation_quotes
                );
    }

    public static function truncate()
    {
        return Db::getInstance()->Execute('TRUNCATE `'._DB_PREFIX_.'everpsquotation_quotes`')
            && Db::getInstance()->Execute('TRUNCATE `'._DB_PREFIX_.'everpsquotation_quote_detail`');
    }

    public static function validateEverPsQuote($id_everpsquotation_quotes)
    {
        return Db::getInstance()->Execute(
            'UPDATE `'._DB_PREFIX_.'everpsquotation_quotes`
            SET valid = 1
            WHERE id_everpsquotation_quotes = '.$id_everpsquotation_quotes
        );
    }

    public static function getQuoteById($id_everpsquotation_quotes)
    {
        return Db::getInstance()->Execute(
            'SELECT * FROM `'._DB_PREFIX_.'everpsquotation_quotes`
            WHERE id_everpsquotation_quotes = '.$id_everpsquotation_quotes
        );
    }

    public static function getQuoteByIdCustomer($id_customer)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('everpsquotation_quotes', 'c');
        $sql->where('c.id_customer = '.$id_customer);
        $sql->orderBy('id_everpsquotation_quotes  DESC');
        return Db::getInstance()->executeS($sql);
    }
}

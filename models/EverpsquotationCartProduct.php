<?php
/**
 * 2019-2021 Team Ever
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Team Ever <https://www.team-ever.com/>
 *  @copyright 2019-2021 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class EverpsquotationCartProduct extends ObjectModel
{
    public $id_everpsquotation_cart;

    public $id_product;

    /** @var int Customer delivery address ID */
    public $id_address_delivery;

    public $id_shop;

    /** @var int Customer invoicing address ID */
    public $id_product_attribute;

    /** @var int Customer currency ID */
    public $id_customization;

    /** @var int Customer ID */
    public $quantity;

    /** @var string Object creation date */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'everpsquotation_cart_product',
        'primary' => 'id_everpsquotation_cart_product',
        'fields' => array(
            'id_everpsquotation_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_product_attribute' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_customization' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'quantity' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public static function getEverCartByIdProduct($id_evercart, $id_product, $id_product_attribute, $id_customization)
    {
        $evercart = 'SELECT id_everpsquotation_cart_product
        FROM '._DB_PREFIX_.'everpsquotation_cart_product
        WHERE id_everpsquotation_cart = '.$id_evercart.'
        AND id_shop = '.$id_product.'
        AND id_product_attribute = '.$id_product_attribute.'
        AND id_customization = '.$id_customization;
        return Db::getInstance()->getValue($evercart);
    }

    public static function getEverCartByIdEvercart($id_evercart)
    {
        $evercart = 'SELECT *
        FROM '._DB_PREFIX_.'everpsquotation_cart_product
        WHERE id_everpsquotation_cart = '.$id_evercart;
        return Db::getInstance()->ExecuteS($evercart);
    }
}

<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

$sql[] =
    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'everpsquotation_cart` (
        `id_everpsquotation_cart` int(10) unsigned NOT NULL auto_increment,
        `id_shop_group` int(11) unsigned NOT NULL,
        `id_shop` int(11) unsigned NOT NULL,
        `id_carrier` int(10) unsigned NOT NULL,
        `delivery_option` text NOT NULL,
        `id_lang` int(10) unsigned NOT NULL,
        `id_address_delivery` int(10) unsigned NOT NULL,
        `id_address_invoice` int(10) unsigned NOT NULL,
        `id_currency` int(10) unsigned NOT NULL,
        `id_customer` int(10) unsigned NOT NULL,
        `id_guest` int(10) unsigned NOT NULL,
        `secure_key` varchar(32) NOT NULL,
        `recyclable` tinyint(1) unsigned NOT NULL,
        `allow_seperated_package` tinyint(1) unsigned NOT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_everpsquotation_cart`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] =
    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'everpsquotation_cart_product` (
        `id_everpsquotation_cart_product` int(10) unsigned NOT NULL auto_increment,
        `id_everpsquotation_cart` int(10) unsigned NOT NULL,
        `id_product` int(10) unsigned NOT NULL,
        `id_address_delivery` int(10) unsigned NOT NULL,
        `id_shop` int(10) unsigned NOT NULL,
        `id_product_attribute` int(10) unsigned NOT NULL,
        `id_customization` int(10) unsigned NOT NULL,
        `quantity` int(10) unsigned NOT NULL,
        `date_add` datetime DEFAULT NULL,
        PRIMARY KEY (`id_everpsquotation_cart_product`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] =
    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'everpsquotation_quotes` (
        `id_everpsquotation_quotes` int(10) unsigned NOT NULL auto_increment,
        `reference` varchar(9) DEFAULT NULL,
        `id_shop_group` int(11) unsigned NOT NULL,
        `id_shop` int(11) unsigned NOT NULL,
        `id_carrier` int(10) unsigned NOT NULL,
        `id_lang` int(10) unsigned NOT NULL,
        `id_customer` int(10) unsigned NOT NULL,
        `secure_key` varchar(32) NOT NULL,
        `recyclable` varchar(32) NOT NULL,
        `id_cart` int(10) unsigned NOT NULL,
        `id_currency` int(10) unsigned NOT NULL,
        `id_address_delivery` int(10) unsigned NOT NULL,
        `id_address_invoice` int(10) unsigned NOT NULL,
        `total_discounts` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_discounts_tax_incl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_discounts_tax_excl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_paid_tax_incl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_paid_tax_excl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_products` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_products_wt` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_shipping` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_shipping_tax_incl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_shipping_tax_excl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_wrapping` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_wrapping_tax_incl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_wrapping_tax_excl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `valid` tinyint(1) unsigned NOT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_everpsquotation_quotes`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] =
    'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'everpsquotation_quote_detail` (
        `id_everpsquotation_quote_detail` int(10) unsigned NOT NULL auto_increment,
        `id_everpsquotation_quotes` int(10) unsigned NOT NULL,
        `id_shop` int(11) unsigned NOT NULL,
        `product_id` int(10) unsigned NOT NULL,
        `product_attribute_id` int(10) DEFAULT NULL,
        `id_customization` int(10) DEFAULT NULL,
        `product_name` varchar(255) NOT NULL,
        `product_quantity` int(10) unsigned NOT NULL,
        `product_price` decimal(20,6) NOT NULL,
        `reduction_percent` decimal(10,2) NOT NULL,
        `reduction_amount` decimal(20,6) NOT NULL,
        `reduction_amount_tax_incl` decimal(20,6) NOT NULL,
        `reduction_amount_tax_excl` decimal(20,6) NOT NULL,
        `group_reduction` decimal(10,2) NOT NULL,
        `product_quantity_discount` decimal(20,6) NOT NULL,
        `product_ean13` varchar(13) DEFAULT NULL,
        `product_isbn` varchar(32) DEFAULT NULL,
        `product_upc` varchar(12) DEFAULT NULL,
        `product_reference` varchar(32) DEFAULT NULL,
        `product_supplier_reference` varchar(32) DEFAULT NULL,
        `product_weight` decimal(20,6) DEFAULT NULL,
        `tax_name` varchar(16) NOT NULL,
        `tax_rate` decimal(10,3) NOT NULL,
        `ecotax` decimal(21,6) NOT NULL,
        `discount_quantity_applied` tinyint(1) NOT NULL,
        `total_price_tax_incl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `total_price_tax_excl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        `unit_price_tax_excl` decimal(20,6) NOT NULL DEFAULT "0.000000",
        PRIMARY KEY (`id_everpsquotation_quote_detail`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

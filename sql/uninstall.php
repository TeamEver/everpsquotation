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
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'everpsquotation_cart`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'everpsquotation_cart_product`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'everpsquotation_quotes`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'everpsquotation_quote_detail`;';

<?php
/**
 * 2019-2023 Team Ever
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
 *  @copyright 2019-2023 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationCart.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationDetail.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';

/**
 * @property Order $object
 */
class AdminEverPsQuotationController extends ModuleAdminController
{
    public $toolbar_title;

    protected $statuses_array = array();

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'everpsquotation_quotes';
        $this->className = 'EverpsquotationClass';
        $this->identifier = 'id_everpsquotation_quotes';
        $this->module_name = 'everpsquotation';
        parent::__construct();

        $this->_select = '
        a.id_currency,
        a.id_everpsquotation_quotes AS id_pdf,
        CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
        IF((SELECT so.id_everpsquotation_quotes
            FROM `' . _DB_PREFIX_ . 'everpsquotation_quotes` so
            WHERE so.id_customer = a.id_customer
            AND so.id_everpsquotation_quotes < a.id_everpsquotation_quotes LIMIT 1) > 0, 0, 1) as new,
        country_lang.name as cname,
        IF(a.valid, 1, 0) badge_success';

        $this->_join = '
        LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)
        INNER JOIN `' . _DB_PREFIX_ . 'address` address ON address.id_address = a.id_address_delivery
        INNER JOIN `' . _DB_PREFIX_ . 'country` country ON address.id_country = country.id_country
        INNER JOIN `' . _DB_PREFIX_ . 'country_lang` country_lang ON (
            country.`id_country` = country_lang.`id_country`
            AND country_lang.`id_lang` = ' . (int)$this->context->language->id . '
        )';
        $this->_orderBy = 'id_everpsquotation_quotes';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        $this->fields_list = array(
            'id_everpsquotation_quotes' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'customer' => array(
                'title' => $this->l('Customer'),
                'havingFilter' => true,
            ),
        );

        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->l('Company'),
                    'filter_key' => 'c!company'
                ),
            ));
        }

        $this->fields_list = array_merge($this->fields_list, array(
            'total_paid_tax_incl' => array(
                'title' => $this->l('Total'),
                'align' => 'text-center',
                'type' => 'price',
                'currency' => true,
                'badge_success' => true
            ),
            'total_shipping_tax_incl' => array(
                'title' => $this->l('Total shipping'),
                'align' => 'text-center',
                'type' => 'price',
                'currency' => true,
                'badge_success' => true
            ),
            'valid' => array(
                'title' => $this->l('Valid'),
                'type' => 'bool',
                'active' => 'statusvalid',
                'orderby' => false,
                'class' => 'fixed-width-sm'
            ),
            'date_add' => array(
                'title' => $this->l('Date'),
                'align' => 'text-center',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ),
        ));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT DISTINCT c.id_country, cl.`name`
        FROM `' . _DB_PREFIX_ . 'orders` o
        ' . Shop::addSqlAssociation('orders', 'o') . '
        INNER JOIN `' . _DB_PREFIX_ . 'address` a ON a.id_address = o.id_address_delivery
        INNER JOIN `' . _DB_PREFIX_ . 'country` c ON a.id_country = c.id_country
        INNER JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (
            c.`id_country` = cl.`id_country`
            AND cl.`id_lang` = ' . (int)$this->context->language->id . '
        )
        ORDER BY cl.name ASC');

        $country_array = array();
        foreach ($result as $row) {
            $country_array[$row['id_country']] = $row['name'];
        }

        $part1 = array_slice($this->fields_list, 0, 3);
        $part2 = array_slice($this->fields_list, 3);
        $part1['cname'] = array(
            'title' => $this->l('Delivery'),
            'type' => 'select',
            'list' => $country_array,
            'filter_key' => 'country!id_country',
            'filter_type' => 'int',
            'order_key' => 'cname',
            'align' => 'text-center'
        );
        $this->fields_list = array_merge($part1, $part2);

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;
        $this->toolbar_title = $this->l('Quotations list');
        $moduleConfUrl  = 'index.php?controller=AdminModules&configure=everpsquotation&token=';
        $moduleConfUrl .= Tools::getAdminTokenLite('AdminModules');
        $this->context->smarty->assign(array(
            'moduleConfUrl' => $moduleConfUrl,
            'everpsquotation_dir' => Tools::getHttpHost(true).'/modules/everpsquotation'
        ));
    }

    public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        return Context::getContext()->getTranslator()->trans(
            $string,
            [],
            'Modules.Everpsquotation.Admineverpsquotationcontroller'
        );
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(_PS_JS_DIR_ . 'vendor/d3.v3.min.js');
    }

    public function renderList()
    {
        $this->initToolbar();
        $this->addRowAction('view');
        $this->addRowAction('dropQuote');
        $this->addRowAction('convertToOrder');
        $lists = parent::renderList();
        $html = $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/everpsquotation/views/templates/admin/header.tpl');
        $module_instance = Module::getInstanceByName($this->module_name);
        if ($module_instance->checkLatestEverModuleVersion($this->module_name, $module_instance->version)) {
            $html .= $this->context->smarty->fetch(
                _PS_MODULE_DIR_
                    . '/'
                    . $this->module_name
                    . '/views/templates/admin/upgrade.tpl'
            );
        }
        $html .= $lists;
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/everpsquotation/views/templates/admin/footer.tpl');

        return $html;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('vieweverpsquotation_quotes')) {
            $id_everpsquotation_quotes = Tools::getValue(
                'id_everpsquotation_quotes'
            );
            $pdf = new PDF($id_everpsquotation_quotes, 'EverQuotationPdf', Context::getContext()->smarty);
            $pdf->render();
        }
        if (Tools::getIsset('statusvalideverpsquotation_quotes')) {
            $quote = new EverpsquotationClass(
                (int)Tools::getValue('id_everpsquotation_quotes')
            );
            $quote->valid = !$quote->valid;
            if (!$quote->update()) {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t update the current object');
            }
        }
        if (Tools::getIsset('deleteeverpsquotation_quotes')) {
            $quote = new EverpsquotationClass(
                (int)Tools::getValue('id_everpsquotation_quotes')
            );
            $quote_cart = new EverpsquotationCart(
                (int)$quote->id_cart
            );
            if (!$quote->deleteQuoteCart() || $quote_cart->dropQuoteCartProducts()) {
                $this->errors[] = Tools::displayError('An error has occurred: Can\'t update the current object');
            }
        }
        if (Tools::getIsset('convert_to_order')) {
            $quote = new EverpsquotationClass(
                (int) Tools::getValue('id_everpsquotation_quotes')
            );
            $products = EverpsquotationDetail::getQuoteDetailByQuoteId(
                (int)$quote->id,
                (int)$this->context->shop->id,
                (int)$this->context->language->id
            );
            $cart = new Cart();
            $cart->id_customer = (int) $quote->id_customer;
            $cart->id_currency = (int) $quote->id_currency;
            $cart->id_address_delivery = (int) $quote->id_address_delivery;
            $cart->id_address_invoice = (int) $quote->id_address_invoice;
            $cart->id_lang = (int) $quote->id_lang;
            $cart->id_carrier = (int) $quote->id_carrier;
            $cart->save();
            foreach ($products as $value) {
                $cart->updateQty(
                    $value['product_quantity'],
                    $value['product_id'],
                    $value['product_attribute_id'],
                    false
                );
            }
            $link = new Link();
            $createOrderLink = $link->getAdminLink('AdminOrders', true, [], ['id_cart' => $cart->id, 'addorder' => 1]);
            Tools::redirect(
                'http://localhost/ps8/admin7634ugpd7fexth1thbe/index.php/sell/orders/new?cartId=' . (int) $cart->id . '&_token=on87aET4vTNr7B37xQqNFiydBGBOIb-dO67MxgVoKRw'
            );
        }
        return parent::postProcess();
    }

    public function displayDropQuoteLink($token, $id_everpsquotation)
    {
        if (!$token) {
            return;
        }
        $quote_controller_link  = 'index.php?controller=AdminEverPsQuotation&token=';
        $quote_controller_link .= Tools::getAdminTokenLite('AdminEverPsQuotation');
        $quote_controller_link .= '&id_everpsquotation_quotes='.(int)$id_everpsquotation;
        $quote_controller_link .= '&deleteeverpsquotation_quotes';

        $this->context->smarty->assign(array(
            'href' => $quote_controller_link,
            'confirm' => null,
            'action' => $this->l('Delete')
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'everpsquotation/views/templates/admin/helpers/lists/list_action_drop_quote.tpl'
        );
    }

    public function displayConvertToOrderLink($token, $id_everpsquotation)
    {
        if (!$token) {
            return;
        }
        $quote_controller_link  = 'index.php?controller=AdminEverPsQuotation&token=';
        $quote_controller_link .= Tools::getAdminTokenLite('AdminEverPsQuotation');
        $quote_controller_link .= '&id_everpsquotation_quotes='.(int)$id_everpsquotation;
        $quote_controller_link .= '&convert_to_order';

        $this->context->smarty->assign(array(
            'href' => $quote_controller_link,
            'confirm' => null,
            'action' => $this->l('Convert to order')
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'everpsquotation/views/templates/admin/helpers/lists/list_action_convert_quote_to_order.tpl'
        );
    }
}

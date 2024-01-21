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
            'everpsquotation_dir' => Tools::getHttpHost(true).__PS_BASE_URI__.'/modules/everpsquotation'
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
        if (Tools::getValue('transformThisCartId')) {
            $this->processTransformCartToQuote();
        }
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
            $createOrderLink = $link->getAdminLink('AdminOrders', true, [], ['cartId' => $cart->id, 'addorder' => 1]);
            Tools::redirectAdmin(
                $createOrderLink
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

    protected function processTransformCartToQuote()
    {
        $cart = new Cart(
            (int) Tools::getValue('transformThisCartId')
        );
        Hook::exec('actionBeforeCreateEverQuote');
        $id_quote_cart = EverpsquotationCart::copyCartToQuoteCart(
            (int)$cart->id
        );
        if (!Validate::isInt($id_quote_cart)) {
            die($this->trans('An error has occured.', array(), 'Modules.Everpsquotation.Shop'));
        }

        //Create new quotation object based on current cart
        $cartdetails = $cart->getSummaryDetails();
        $cartproducts = $cart->getProducts();
        if (count($cartproducts) <= 0) {
            return;
        }
        $quote = new EverpsquotationClass();
        $quote->reference = (string)Configuration::get('EVERPSQUOTATION_PREFIX');
        $quote->id_shop_group = (int)$cart->id_shop_group;
        $quote->id_shop = (int)$cart->id_shop;
        $quote->id_carrier = (int)$cart->id_carrier;
        $quote->id_lang = (int)$cart->id_lang;
        $quote->id_customer = (int)$cart->id_customer;
        $quote->id_cart = (int)$cart->id;
        $quote->id_currency = (int)$cart->id_currency;
        $quote->id_address_delivery = (int)$cart->id_address_delivery;
        $quote->id_address_invoice = (int)$cart->id_address_invoice;
        $quote->secure_key = (string)$cart->secure_key;
        $quote->recyclable = (int)$cart->recyclable;
        $quote->total_discounts = (float)$cartdetails['total_discounts'];
        $quote->total_discounts_tax_incl = (float)$cartdetails['total_discounts'];
        $quote->total_discounts_tax_excl = (float)$cartdetails['total_discounts_tax_exc'];
        $quote->total_paid_tax_incl = (float)$cartdetails['total_price'];
        $quote->total_paid_tax_excl = (float)$cartdetails['total_price_without_tax'];
        $quote->total_products = (float)$cartdetails['total_products'];
        $quote->total_products_wt = (float)$cartdetails['total_products_wt'];
        $quote->total_shipping = (float)$cartdetails['total_shipping'];
        $quote->total_shipping_tax_incl = (float)$cartdetails['total_shipping'];
        $quote->total_shipping_tax_excl = (float)$cartdetails['total_shipping_tax_exc'];
        $quote->total_wrapping = (float)$cartdetails['total_wrapping'];
        $quote->total_wrapping_tax_incl = (float)$cartdetails['total_wrapping'];
        $quote->total_wrapping_tax_excl = (float)$cartdetails['total_wrapping_tax_exc'];
        $quote->valid = 0;
        $quote->date_add = date('Y-m-d H:i:s');
        $quote->date_upd = date('Y-m-d H:i:s');
        $quote->save();

        //Now create new Everpsquotationdetail object
        foreach ($cartproducts as $cartproduct) {
            $quotedetail = new EverpsquotationDetail();
            $quotedetail->id_everpsquotation_quotes = (int)$quote->id;
            $quotedetail->id_warehouse = $cartdetails['total_discounts']['id_warehouse'];
            $quotedetail->id_shop = (int)$cartproduct['id_shop'];
            $quotedetail->product_id = (int)$cartproduct['id_product'];
            $quotedetail->product_attribute_id = (int)$cartproduct['id_product_attribute'];
            $quotedetail->id_customization = (int)$cartproduct['id_customization'];
            $quotedetail->product_name = (string)$cartproduct['name'];
            $quotedetail->product_quantity = (int)$cartproduct['cart_quantity'];
            $quotedetail->product_quantity_in_stock = (int)$cartproduct['stock_quantity'];
            $quotedetail->product_price = $cartproduct['price'];
            $quotedetail->product_ean13 = (string)$cartproduct['ean13'];
            $quotedetail->product_isbn = (string)$cartproduct['isbn'];
            $quotedetail->product_upc = (string)$cartproduct['upc'];
            $quotedetail->product_reference = (string)$cartproduct['reference'];
            $quotedetail->product_supplier_reference = (string)$cartproduct['supplier_reference'];
            $quotedetail->product_weight = (float)$cartproduct['weight'];
            $quotedetail->tax_name = (string)$cartproduct['tax_name'];
            $quotedetail->ecotax = (float)$cartproduct['ecotax'];
            $quotedetail->unit_price_tax_excl = (float)$cartproduct['price'];
            $quotedetail->total_price_tax_incl = (float)$cartproduct['total_wt'];
            $quotedetail->total_price_tax_excl = (float)$cartproduct['total'];
            $quotedetail->add();
        }
        Hook::exec('actionAfterCreateEverQuote');
        if ((bool)Configuration::get('EVERPSQUOTATION_RENDER_ON_VALIDATION') === true) {
            $pdf = new PDF($quote->id, 'EverQuotationPdf', Context::getContext()->smarty);
            $pdf->render();
        }
    }
}

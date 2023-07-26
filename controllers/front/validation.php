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

/**
 * @since 1.5.0
 */
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';

class EverpsquotationValidationModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::init();
    }

    public function initContent()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        parent::initContent();

        $customer = new Customer((int)$cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $total_cart = $cart->getOrderTotal(
            false,
            Cart::BOTH_WITHOUT_SHIPPING,
            null,
            null,
            true
        );
        if ($total_cart <= 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        if ((float)Configuration::get('EVERPSQUOTATION_MIN_AMOUNT') > 0
            && $total_cart < Configuration::get('EVERPSQUOTATION_MIN_AMOUNT')) {
            Tools::redirect('index.php?controller=order&step=1');
        }

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

        //Preparing emails
        if (Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL')) {
            $everShopEmail = Configuration::get('EVERPSQUOTATION_ACCOUNT_EMAIL');
        } else {
            $everShopEmail = Configuration::get('PS_SHOP_EMAIL');
        }

        // Subject
        $ever_subject = $this->module::getConfigInMultipleLangs('EVERPSQUOTATION_MAIL_SUBJECT');
        $subject = $ever_subject[(int)Context::getContext()->language->id];
        // Filename
        $filename = $this->module::getConfigInMultipleLangs('EVERPSQUOTATION_FILENAME');
        $ever_filename = $filename[(int)Context::getContext()->language->id];

        $id_shop = (int)Context::getContext()->shop->id;
        $mailDir = _PS_MODULE_DIR_.'everpsquotation/mails/';
        $pdf = new PDF($quote->id, 'EverQuotationPdf', Context::getContext()->smarty);
        $customerNames = $customer->firstname.' '.$customer->lastname;
        $attachment = array();
        $attachment['content'] = $pdf->render(false);
        $attachment['name'] = $ever_filename;
        $attachment['mime'] = 'application/pdf';
        Mail::send(
            (int)$this->context->language->id,
            'everquotecustomer',
            (string)$subject,
            array(
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{shop_logo}'=>_PS_IMG_DIR_.Configuration::get(
                    'PS_LOGO',
                    null,
                    null,
                    (int)$id_shop
                ),
                '{firstname}' => (string)$customer->firstname,
                '{lastname}' => (string)$customer->lastname,
            ),
            (string)$customer->email,
            (string)$customerNames,
            (string)$everShopEmail,
            Configuration::get('PS_SHOP_NAME'),
            $attachment,
            null,
            $mailDir,
            false,
            null,
            (string)$everShopEmail,
            (string)$everShopEmail,
            Configuration::get('PS_SHOP_NAME')
        );
        if ((bool)Configuration::get('EVERPSQUOTATION_RENDER_ON_VALIDATION') === true) {
            $pdf = new PDF($quote->id, 'EverQuotationPdf', Context::getContext()->smarty);
            $pdf->render();
        }
        $my_quotations_link = Context::getContext()->link->getModuleLink(
            'everpsquotation',
            'quotations',
            array(),
            true
        );
        $this->context->smarty->assign(array(
            'my_quotations_link' => $my_quotations_link,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$id_shop),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int)$id_shop),
        ));
        $this->setTemplate('module:everpsquotation/views/templates/front/quotation_added.tpl');
    }

    public function l($string, $specific = false, $class = null, $addslashes = false, $htmlentities = true)
    {
        return Context::getContext()->getTranslator()->trans(
            $string,
            [],
            'Modules.Everpsquotation.quotations'
        );
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = array(
            'title' => $this->l('Quotation saved'),
            'url' => $this->context->link->getModuleLink(
                'everpsquotation',
                'validation'
            ),
        );
        return $breadcrumb;
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['body_classes']['page-everpsquotation'] = true;
        $page['body_classes']['page-everpsquotation-validation'] = true;
        return $page;
    }
}

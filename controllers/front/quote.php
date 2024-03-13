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

class EverpsquotationQuoteModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
    }

    public function l($string, $specific = false, $class = null, $addslashes = false, $htmlentities = true)
    {
        return Context::getContext()->getTranslator()->trans(
            $string,
            [],
            'Modules.Everpsquotation.quotations'
        );
    }

    public function displayAjaxSetQuote()
    {
        $token = Tools::encrypt(
            $this->module->name . '_token/setquote'
        );
        if (!Tools::getValue('token')
            || $token != Tools::getValue('token')
        ) {
            Tools::redirect('index.php');
        }
        if (!$this->context->customer->isLogged()) {
            $modal = $this->renderModal();
            die(json_encode([
                'modal' => $modal,
            ]));
        }
        if (Tools::getValue('simple_quotation')) {
            if (Tools::getValue('everid_product_attribute')) {
                $id_attribute = (int) Tools::getValue('everid_product_attribute');
            } else {
                $id_attribute = (int) Tools::getValue('id_product_attribute');
            }
            if (!$id_attribute) {
                $id_attribute = 0;
            }
            $sql = 'SELECT minimal_quantity FROM '._DB_PREFIX_.'product WHERE id_product = '. (int) Tools::getValue('everid_product') . '';

            $minimalQuantity = (int) Db::getInstance()->getValue($sql);

            if ((int) Tools::getValue('ever_qty') < (int) $minimalQuantity) {
                $quantity = (int) $minimalQuantity;
            } else {
                $quantity = (int) Tools::getValue('ever_qty');
            }
            $quoteId = $this->createSimpleProductQuote(
                (int) Tools::getValue('everid_product'),
                (int) $id_attribute,
                (int) Tools::getValue('id_customization'),
                (int) $quantity
            );
        } else {
            $quoteId = $this->setCartAsQuote();
        }
        $gtmTag = $this->triggerGtmTag($quoteId);
        $downloadLink = $this->context->link->getModuleLink(
            $this->module->name,
            'quotations',
            [
                'id_everpsquotation' => $quoteId,
                'action' => 'pdf',
                'token' => $token,
            ]
        );
        die(json_encode([
            'downloadLink' => $downloadLink,
            'quoteId' => $quoteId,
            'gtm' => $gtmTag,
        ]));
    }

    private function triggerGtmTag($quoteId)
    {
        $quote = new EverpsquotationClass(
            (int) $quoteId
        );
        $quoteDetails = EverpsquotationDetail::getQuoteDetailByQuoteId(
            (int) $quote->id,
            (int) $this->context->shop->id,
            (int) $this->context->language->id
        );
        $quoteCustomer = new Customer(
            (int) $quote->id_customer
        );
        $quoteCurrency = new Currency(
            (int) $quote->id_currency
        );
        $dataForGTM = [
            'quoteEvent' => 'requestForQuote',
            'quoteId' => (int) $quote->id,
            'quoteIdCustomer' => (int) $quote->id_customer,
            'quoteProducts' => $quoteDetails,
            'quoteCustomer' => $quoteCustomer,
            'quoteCurrency' => $quoteCurrency->name,
            'quoteShopName' => Configuration::get('PS_SHOP_NAME'),
        ];
        return $dataForGTM;
    }

    private function createSimpleProductQuote($id_product, $id_product_attribute, $id_customization, $qty)
    {
        $cart = Context::getContext()->cart;
        $cart->updateQty(
            $qty,
            $id_product,
            $id_product_attribute,
            $id_customization,
        );

        // Récupérez tous les transporteurs disponibles
        $carriers = Carrier::getCarriers(
            Context::getContext()->language->id,
            true,
            false,
            false,
            null,
            PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
        );

        // Initialisez une variable pour stocker le premier transporteur payant
        $first_paid_carrier = null;
        $deliveryAddress = new Address(
            $cart->id_address_delivery
        );
        $country = new Country(
            (int) $deliveryAddress->id_country
        );
        $zone = new Zone(
            (int) $country->id_zone
        );

        foreach ($carriers as $carrier) {
            $carrier = new Carrier(
                (int) $carrier['id_carrier']
            );
            // Vérifiez si le transporteur est disponible pour la destination du panier
            if (Carrier::checkCarrierZone(
                $carrier->id,
                (int)$zone->id
            )) {
                $deliveryPriceByWeight = $carrier->getDeliveryPriceByWeight(
                    $cart->getTotalWeight(),
                    $zone->id
                );
                // Vérifiez si le coût du transporteur est supérieur à zéro
                if ($deliveryPriceByWeight > 0) {
                    $first_paid_carrier = $carrier;
                    break;
                }
            }
        }

        if ($first_paid_carrier !== null) {
            $id_first_paid_carrier = $first_paid_carrier->id;
            $id_cart = $cart->id;
            $id_address_delivery = $cart->id_address_delivery;
            $delivery_option = array(
                $id_address_delivery => $id_first_paid_carrier.',' // Notez la virgule à la fin
            );
            $cart->setDeliveryOption($delivery_option);
            $cart->update();
        }

        Hook::exec('actionBeforeCreateEverQuote');

        // First create Quote Cart
        $ever_cart = new EverpsquotationCart();
        $ever_cart->id_shop_group = $cart->id_shop_group;
        $ever_cart->id_shop = $cart->id_shop;
        $ever_cart->id_carrier = $cart->id_carrier;
        $ever_cart->delivery_option = $cart->delivery_option;
        $ever_cart->id_lang = $cart->id_lang;
        $ever_cart->id_address_delivery = $cart->id_address_delivery;
        $ever_cart->id_address_invoice = $cart->id_address_invoice;
        $ever_cart->id_currency = $cart->id_currency;
        $ever_cart->id_customer = $cart->id_customer;
        $ever_cart->id_guest = $cart->id_guest;
        $ever_cart->secure_key = $cart->secure_key;
        $ever_cart->recyclable = $cart->recyclable;
        $ever_cart->allow_seperated_package = $cart->allow_seperated_package;
        $ever_cart->date_add = $cart->date_add;
        $ever_cart->date_upd = $cart->date_upd;
        $ever_cart->save();

        // Then add product
        $ever_cart->addProductToQuoteCart(
            (int)$id_product,
            (int)$id_product_attribute,
            (int)$id_customization,
            (int)$qty
        );

        // Get ever cart informations
        $cart_details = $ever_cart->getSummaryDetails(
            (int)$cart->id
        );
        $cart_products = $ever_cart->getProducts();

        // Now create quotation
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
        $quote->total_discounts = (float)$cart_details['total_discounts'];
        $quote->total_discounts_tax_incl = (float)$cart_details['total_discounts'];
        $quote->total_discounts_tax_excl = (float)$cart_details['total_discounts_tax_exc'];
        $quote->total_paid_tax_incl = (float)$cart_details['total_price'];
        $quote->total_paid_tax_excl = (float)$cart_details['total_price_without_tax'];
        $quote->total_products = (float)$cart_details['total_products'];
        $quote->total_products_wt = (float)$cart_details['total_products_wt'];
        $quote->total_shipping = (float)$cart_details['total_shipping'];
        $quote->total_shipping_tax_incl = (float)$cart_details['total_shipping'];
        $quote->total_shipping_tax_excl = (float)$cart_details['total_shipping_tax_exc'];
        $quote->total_wrapping = (float)$cart_details['total_wrapping'];
        $quote->total_wrapping_tax_incl = (float)$cart_details['total_wrapping'];
        $quote->total_wrapping_tax_excl = (float)$cart_details['total_wrapping_tax_exc'];
        $quote->valid = 0;
        $quote->date_add = date('Y-m-d H:i:s');
        $quote->date_upd = date('Y-m-d H:i:s');
        $quote->save();

        // Add ever cart to quotation details
        foreach ($cart_products as $cart_product) {
            $product_stock = StockAvailable::getQuantityAvailableByProduct(
                (int)$cart_product['id_product'],
                (int)$cart_product['id_product_attribute']
            );
            $price_with_tax = Product::getPriceStatic(
                (int)$cart_product['id_product'],
                true,
                (int)$cart_product['id_product_attribute']
            );
            $price_without_tax = Product::getPriceStatic(
                (int)$cart_product['id_product'],
                false,
                (int)$cart_product['id_product_attribute']
            );
            $total_wt = (float)$price_with_tax * (int)$cart_product['cart_quantity'];
            $total = (float)$price_without_tax * (int)$cart_product['cart_quantity'];
            // $product_taxes = $price_with_tax - $price_without_tax;
            // $total_product_taxes = $total_wt - $total;
            // die(var_dump($price_without_tax));
            $quotedetail = new EverpsquotationDetail();
            $quotedetail->id_everpsquotation_quotes = (int)$quote->id;
            // $quotedetail->id_warehouse = (int)$cart_details['total_discounts']['id_warehouse'];
            $quotedetail->id_shop = (int)$cart_product['id_shop'];
            $quotedetail->product_id = (int)$cart_product['id_product'];
            $quotedetail->product_attribute_id = (int)$cart_product['id_product_attribute'];
            $quotedetail->id_customization = (int)$cart_product['id_customization'];
            $quotedetail->product_name = (string)$cart_product['name'];
            $quotedetail->product_quantity = (int)$cart_product['cart_quantity'];
            $quotedetail->product_quantity_in_stock = (int)$product_stock;
            $quotedetail->product_price = (float)$price_without_tax;
            $quotedetail->product_ean13 = (string)$cart_product['ean13'];
            $quotedetail->product_isbn = (string)$cart_product['isbn'];
            $quotedetail->product_upc = (string)$cart_product['upc'];
            $quotedetail->product_reference = (string)$cart_product['reference'];
            $quotedetail->product_supplier_reference = (string)$cart_product['supplier_reference'];
            $quotedetail->product_weight = (float)$cart_product['weight'];
            // $quotedetail->tax_name = (string)$cart_product['tax_name'];
            $quotedetail->ecotax = (float)$cart_product['ecotax'];
            $quotedetail->unit_price_tax_excl = (float)$price_without_tax;
            $quotedetail->total_price_tax_incl = (float)$total_wt;
            $quotedetail->total_price_tax_excl = (float)$total;
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
        $customer = Context::getContext()->customer;
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
                '{shop_name}'=>Configuration::get('PS_SHOP_NAME'),
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
        $cart->updateQty(
            $qty,
            $id_product,
            $id_product_attribute,
            $id_customization,
            'down'
        );
        return $quote->id;
    }

    private function setCartAsQuote(): int
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
        try {
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
            return (int) $quote->id;
        } catch (Exception $e) {
            PrestaShopLogger::addLog($this->module->name . ' : ' . $e->getMessage());
            return 0;
        }
    }

    private function renderModal()
    {
        $suggestLogIn = Configuration::get('EVERPSQUOTATION_SUGGEST_CONNECT_UNLOGGED');
        if ((bool) $suggestLogIn === true) {
            $this->context->smarty->assign(array(
                'suggestLogIn' => $suggestLogIn,
            ));
        }
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/front/modal.tpl');
    }
}

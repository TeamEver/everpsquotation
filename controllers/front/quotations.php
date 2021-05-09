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

/**
 * @since 1.5.0
 */
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';

class EverpsquotationQuotationsModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        $this->isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
        $cart = $this->context->cart;
        $id_shop = (int)Context::getContext()->shop->id;

        if (!(bool)$this->context->customer->isLogged()) {
            $link = new Link();
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }

        if ($cart->id_customer == 0
            || !$this->module->active) {
            Tools::redirect('index.php');
        }
        if (Tools::getValue('id_everpsquotation') && Tools::getValue('action')) {
            $id_quote = (int)Tools::getValue('id_everpsquotation');
            $quote = new EverpsquotationClass(
                (int)$id_quote
            );
            if (Validate::isLoadedObject($quote)
                && $quote->id_customer == $cart->id_customer
            ) {
                switch (Tools::getValue('action')) {
                    case 'pdf':
                        $pdf = new PDF($id_quote, 'EverQuotationPdf', Context::getContext()->smarty);
                        $pdf->render();
                        break;
                    case 'addtocart':
                        $products = EverpsquotationDetail::getQuoteDetailByQuoteId(
                            (int)$id_quote,
                            (int)$this->context->shop->id,
                            (int)$this->context->language->id
                        );
                        if ($quote->validateEverPsQuote()) {
                            foreach ($products as $value) {
                                $cart->updateQty(
                                    $value['product_quantity'],
                                    $value['product_id'],
                                    $value['product_attribute_id'],
                                    false
                                );
                            }
                        }
                        Tools::redirect('index.php?controller=order&step=1');
                        break;
                    case 'validate':
                        $products = EverpsquotationDetail::getQuoteDetailByQuoteId(
                            (int)$id_quote,
                            (int)$this->context->shop->id,
                            (int)$this->context->language->id
                        );
                        foreach ($products as $value) {
                            $cart->updateQty(
                                $value['product_quantity'],
                                $value['product_id'],
                                $value['product_attribute_id'],
                                false
                            );
                        }
                        if (!$quote->validateEverPsQuote()) {
                            Tools::redirect($_SERVER['PHP_SELF']);
                        }
                        break;
                    default:
                        # code...
                        break;
                }
            } else {
                Tools::redirect(Link::getBaseLink($id_shop));
            }
        }
        $quotationsList = EverpsquotationClass::getQuotesByIdCustomer($cart->id_customer);

        $this->context->smarty->assign(array(
            'prefix' => Configuration::get('EVERPSQUOTATION_PREFIX'),
            'quotationsList' => $quotationsList,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$id_shop),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int)$id_shop),
        ));
        if (_PS_VERSION_ >= '1.7') {
            $this->setTemplate('module:everpsquotation/views/templates/front/quotations.tpl');
        } else {
            $this->setTemplate('quotations16.tpl');
        }
    }

    public function l($string, $specific = false, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ($this->isSeven) {
            return Context::getContext()->getTranslator()->trans(
                $string,
                [],
                'Modules.Everpsquotation.quotations'
            );
        }

        return parent::l($string, $specific, $class, $addslashes, $htmlentities);
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = array(
            'title' => $this->l('My quotations'),
            'url' => $this->context->link->getModuleLink(
                'everpsquotation',
                $this->l('quotations')
            ),
        );
        return $breadcrumb;
    }
}

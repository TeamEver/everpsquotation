<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
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
            $quoteVerif = new EverpsquotationClass($id_quote);
            if ($quoteVerif->id_customer == $cart->id_customer) {
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
                        if (EverpsquotationClass::validateEverPsQuote($id_quote)) {
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
                        if (!EverpsquotationClass::validateEverPsQuote($id_quote)) {
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
        $quotationsList = EverpsquotationClass::getQuoteByIdCustomer($cart->id_customer);

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

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = array(
            'title' => $this->l('My quotations'),
            'url' => $this->context->link->getModuleLink(
                'everpsquotation',
                'quotations'
            ),
        );
        return $breadcrumb;
    }
}

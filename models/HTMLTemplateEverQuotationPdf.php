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

require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationDetail.php';

class HTMLTemplateEverQuotationPdf extends HTMLTemplate
{
    public $id_everpsquotation_quotes;

    public function __construct($id_everpsquotation_quotes, $smarty)
    {
        $module = Module::getInstanceByName('everpsquotation');
        $text = $module::getConfigInMultipleLangs('EVERPSQUOTATION_TEXT');
        $mentions = $module::getConfigInMultipleLangs('EVERPSQUOTATION_MENTIONS');
        $filename = $module::getConfigInMultipleLangs('EVERPSQUOTATION_FILENAME');
        $this->id_everpsquotation_quotes = $id_everpsquotation_quotes;
        $this->smarty = $smarty;
        $this->pdfDir = _PS_MODULE_DIR_ . 'everpsquotation/views/templates/front/pdf/';
        $this->context = Context::getContext();
        $this->shop = new Shop(Context::getContext()->shop->id);
        $this->lang = new Language((int)Context::getContext()->language->id);
        $this->text = $text[(int)Context::getContext()->language->id];
        $this->mentions = $mentions[(int)Context::getContext()->language->id];
        $this->duration = Configuration::get('EVERPSQUOTATION_DURATION');
        $this->filename = $filename[(int)Context::getContext()->language->id]
        .$this->id_everpsquotation_quotes;
    }

    /**
     * Returns the template's HTML content
     * @return string HTML content
     */
    public function getContent()
    {
        $everpsquotation = new EverpsquotationClass(
            (int)$this->id_everpsquotation_quotes
        );
        $everpsquotation->date_add = date(
            'd/m/Y',
            strtotime($everpsquotation->date_add)
        );
        $details = EverpsquotationDetail::getQuoteDetailByQuoteId(
            $this->id_everpsquotation_quotes,
            Context::getContext()->shop->id,
            Context::getContext()->language->id
        );
        $customizations = array();
        foreach ($details as $detail) {
            if ((int)$detail['id_customization']) {
                $custs = EverpsquotationDetail::getCustomizationValue(
                    (int)$detail['id_customization']
                );
                $customizations[] = array(
                    'product_id' => (int)$detail['product_id'],
                    'customizations' => $custs
                );
            }
        }
        $total_taxes = $everpsquotation->total_paid_tax_incl - $everpsquotation->total_paid_tax_excl;

        $this->smarty->assign(array(
            '_PS_VERSION_' => _PS_VERSION_,
            'details' => $details,
            'customizations' => $customizations,
            'total_discounts' => $everpsquotation->total_discounts,
            'total_discounts_tax_incl' => $everpsquotation->total_discounts_tax_incl,
            'total_discounts_tax_excl' => $everpsquotation->total_discounts_tax_excl,
            'total_paid_tax_incl' => $everpsquotation->total_paid_tax_incl,
            'total_paid_tax_excl' => $everpsquotation->total_paid_tax_excl,
            'total_products' => $everpsquotation->total_products,
            'total_products_wt' => $everpsquotation->total_products_wt,
            'total_shipping' => $everpsquotation->total_shipping,
            'total_shipping_tax_incl' => $everpsquotation->total_shipping_tax_incl,
            'total_shipping_tax_excl' => $everpsquotation->total_shipping_tax_excl,
            'total_wrapping' => $everpsquotation->total_wrapping,
            'total_wrapping_tax_incl' => $everpsquotation->total_wrapping_tax_incl,
            'total_wrapping_tax_excl' => $everpsquotation->total_wrapping_tax_excl,
            'total_taxes' => $total_taxes,
            'date_add' => $everpsquotation->date_add,
            'everpsquotationmentions' => $this->mentions,
        ));

        return $this->smarty->fetch($this->pdfDir . '/everquotation_content.tpl');
    }

    public function getHeader()
    {
        $everpsquotation = new EverpsquotationClass($this->id_everpsquotation_quotes);
        $customerInfos = new Customer($everpsquotation->id_customer);
        $customerAddress = new Address($everpsquotation->id_address_invoice);
        $customerAddressDelivery = new Address($everpsquotation->id_address_delivery);
        $id_shop = (int)Context::getContext()->shop->id;
        $shop_address = $this->getShopAddress();
        if ((int) $this->duration > 0) {
            // Ajouter la durée en jours à la date d'ajout
            $newDateTimestamp = strtotime($everpsquotation->date_add . " +{$this->duration} days");

            // Formater la nouvelle date au format 'd/m/Y'
            $deadline = date('Y-m-d h:m:s', $newDateTimestamp);
        } else {
            $deadline = false;
        }

        if (file_exists(_PS_MODULE_DIR_.'everpsquotation/views/img/quotation.jpg')) {
            $pathLogo = _PS_MODULE_DIR_.'everpsquotation/views/img/quotation.jpg';
        } else {
            $pathLogo = __PS_BASE_URI__.'img/'.Configuration::get(
                'PS_LOGO'
            );
        }
        $width = (int)Configuration::get('EVERPSQUOTATION_LOGO_WIDTH');
      

        $this->smarty->assign(array(
            'deadline' => $deadline,
            'id_everpsquotation_quotes' => $this->id_everpsquotation_quotes,
            'prefix' => Configuration::get('EVERPSQUOTATION_PREFIX'),
            'date_add' => $everpsquotation->date_add,
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'customerInfos' => $customerInfos,
            'customerAddress' => $customerAddress,
            'customerAddressDelivery' => $customerAddressDelivery,
            'shop_address' => $shop_address,
            'logo_path' => $pathLogo,
            'width_logo' => $width,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$id_shop),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int)$id_shop),
        ));

        return $this->smarty->fetch($this->pdfDir . '/everquotation_header.tpl');
    }

    /**
     * Returns the template filename
     * @return string filename
     */
    public function getFooter()
    {
        $this->smarty->assign(array(
            'id_everpsquotation_quotes' => $this->id_everpsquotation_quotes,
            'everpsquotationtext' => $this->text,
        ));
        return $this->smarty->fetch($this->pdfDir . '/everquotation_footer.tpl');
    }

    /**
     * Returns the template filename
     * @return string filename
     */
    public function getFilename()
    {
        return $this->filename . '.pdf';
    }

    /**
     * Returns the template filename when using bulk rendering
     * @return string filename
     */
    public function getBulkFilename()
    {
        return $this->filename . '.pdf';
    }
}

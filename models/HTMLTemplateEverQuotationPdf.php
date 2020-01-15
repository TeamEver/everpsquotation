<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';

class HTMLTemplateEverQuotationPdf extends HTMLTemplate
{
    public $id_everpsquotation_quotes;

    public function __construct($id_everpsquotation_quotes, $smarty)
    {
        $this->id_everpsquotation_quotes = $id_everpsquotation_quotes;
        $this->smarty = $smarty;
        $this->pdfDir = _PS_MODULE_DIR_.'everpsquotation/views/templates/front/pdf/';
        $this->context = Context::getContext();
        $this->shop = new Shop(Context::getContext()->shop->id);
        $this->lang = new Language((int)Context::getContext()->language->id);
        $text = Configuration::getInt('EVERPSQUOTATION_TEXT');
        $filename = Configuration::getInt('EVERPSQUOTATION_FILENAME');
        $this->text = $text[(int)Context::getContext()->language->id];
        $this->filename = $filename[(int)Context::getContext()->language->id];
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
        $details = EverpsquotationDetail::getQuoteDetailByQuoteId(
            $this->id_everpsquotation_quotes,
            Context::getContext()->shop->id,
            Context::getContext()->language->id
        );
        $cart = new Cart(
            (int)$everpsquotation->id_cart
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
        // die(var_dump($customizations));
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
        ));

        return $this->smarty->fetch($this->pdfDir.'/everquotation_content.tpl');
    }

    public function getHeader()
    {
        $everpsquotation = new EverpsquotationClass($this->id_everpsquotation_quotes);
        $customerInfos = new Customer($everpsquotation->id_customer);
        $customerAddress = new Address($everpsquotation->id_address_invoice);
        $customerAddressDelivery = new Address($everpsquotation->id_address_delivery);
        $id_shop = (int)Context::getContext()->shop->id;
        $shop_address = $this->getShopAddress();
        $path_logo = $this->getLogo();
        $width = 0;
        $height = 0;
        if (!empty($path_logo)) {
            list($width, $height) = getimagesize(_PS_IMG_DIR_.$path_logo);
        }

        //Limit the height of the logo for the PDF render
        $maximum_height = 100;
        if ($height > $maximum_height) {
            $ratio = $maximum_height / $height;
            $height *= $ratio;
            $width *= $ratio;
        }

        $this->smarty->assign(array(
            'id_everpsquotation_quotes' => $this->id_everpsquotation_quotes,
            'prefix' => Configuration::get('EVERPSQUOTATION_PREFIX'),
            'date_add' => $everpsquotation->date_add,
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'customerInfos' => $customerInfos,
            'customerAddress' => $customerAddress,
            'customerAddressDelivery' => $customerAddressDelivery,
            'shop_address' => $shop_address,
            'logo_path' => $path_logo,
            'width_logo' => $width,
            'height_logo' => $height,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$id_shop),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int)$id_shop),
        ));

        return $this->smarty->fetch($this->pdfDir.'/everquotation_header.tpl');
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
        return $this->smarty->fetch($this->pdfDir.'/everquotation_footer.tpl');
    }

    /**
     * Returns the template filename
     * @return string filename
     */
    public function getFilename()
    {
        return $this->filename.'.pdf';
    }

    /**
     * Returns the template filename when using bulk rendering
     * @return string filename
     */
    public function getBulkFilename()
    {
         return $this->filename.'.pdf';
    }
}

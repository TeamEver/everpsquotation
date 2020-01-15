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

require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationClass.php';
require_once _PS_MODULE_DIR_.'everpsquotation/models/EverpsquotationDetail.php';

/**
 * @property Order $object
 */
class AdminEverPsQuotationController extends ModuleAdminController
{
    public $toolbar_title;

    protected $statuses_array = array();

    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if (_PS_VERSION_ >= '1.7') {
            return Context::getContext()->getTranslator()->trans($string);
        } else {
            return parent::l($string, $class, $addslashes, $htmlentities);
        }
    }

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'everpsquotation_quotes';
        $this->className = 'EverpsquotationClass';
        $this->identifier = "id_everpsquotation_quotes";

        parent::__construct();

        $this->_select = '
        a.id_currency,
        a.id_everpsquotation_quotes AS id_pdf,
        CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
        IF((SELECT so.id_everpsquotation_quotes
            FROM `'._DB_PREFIX_.'everpsquotation_quotes` so
            WHERE so.id_customer = a.id_customer
            AND so.id_everpsquotation_quotes < a.id_everpsquotation_quotes LIMIT 1) > 0, 0, 1) as new,
        country_lang.name as cname,
        IF(a.valid, 1, 0) badge_success';

        $this->_join = '
        LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
        INNER JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
        INNER JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
        INNER JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (
            country.`id_country` = country_lang.`id_country`
            AND country_lang.`id_lang` = '.(int)$this->context->language->id.'
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
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'badge_success' => true
            ),
            'date_add' => array(
                'title' => $this->l('Date'),
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ),
        ));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
        SELECT DISTINCT c.id_country, cl.`name`
        FROM `'._DB_PREFIX_.'orders` o
        '.Shop::addSqlAssociation('orders', 'o').'
        INNER JOIN `'._DB_PREFIX_.'address` a ON a.id_address = o.id_address_delivery
        INNER JOIN `'._DB_PREFIX_.'country` c ON a.id_country = c.id_country
        INNER JOIN `'._DB_PREFIX_.'country_lang` cl ON (
            c.`id_country` = cl.`id_country`
            AND cl.`id_lang` = '.(int)$this->context->language->id.'
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
            'order_key' => 'cname'
        );
        $this->fields_list = array_merge($part1, $part2);

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;
        $this->toolbar_title = $this->l('Quotations list');
        $this->context->smarty->assign(array(
            'everpsquotation_dir' => _PS_BASE_URL_ . '/modules/everpsquotation/'
        ));
    }

    public function initToolbar()
    {
        //Empty because of reasons :-)
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(_PS_JS_DIR_.'vendor/d3.v3.min.js');
    }

    public function renderList()
    {
        $this->initToolbar();
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->addRowAction('validate');
        $lists = parent::renderList();
        $html=$this->context->smarty->fetch(_PS_MODULE_DIR_ . '/everpsquotation/views/templates/admin/header.tpl');
        $html .= $lists;
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/everpsquotation/views/templates/admin/footer.tpl');

        return $html;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('vieweverpsquotation_quotes')) {
            $id_everpsquotation_quotes = Tools::getValue('id_everpsquotation_quotes');
            require_once _PS_MODULE_DIR_ . 'everpsquotation/models/HTMLTemplateEverQuotationPdf.php';
            $pdf = new PDF($id_everpsquotation_quotes, 'EverQuotationPdf', Context::getContext()->smarty);
            $pdf->render();
        }
        if (Tools::isSubmit('validateeverpsquotation_quotes')) {
            require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationClass.php';
            $validation = new EverpsquotationClass(Tools::getValue('id_everpsquotation_quotes'));
            $validation->valid = 1;
            if (!$validation->update()) {
                    $this->errors[] = Tools::displayError('An error has occurred: Can\'t update the current object');
            }
        }
        if (Tools::isSubmit('deleteeverpsquotation_quotes')) {
            require_once _PS_MODULE_DIR_ . 'everpsquotation/models/EverpsquotationClass.php';
            $quote = new EverpsquotationClass(Tools::getValue('id_everpsquotation_quotes'));
            if (!$quote->delete()) {
                    $this->errors[] = Tools::displayError('An error has occurred: Can\'t update the current object');
            }
        }
        return parent::postProcess();
    }
}

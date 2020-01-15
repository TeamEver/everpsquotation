<?php
/**
 * Project : everpsquotation
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link http://team-ever.com
 */

class EverpsquotationDetail extends ObjectModel
{
    /** @var int */
    public $id_order_detail;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_order_invoice;

    /** @var int */
    public $product_id;

    /** @var int */
    public $id_shop;

    /** @var int */
    public $product_attribute_id;

    /** @var int */
    public $id_customization;

    /** @var string */
    public $product_name;

    /** @var int */
    public $product_quantity;

    /** @var int */
    public $product_quantity_return;

    /** @var int */
    public $product_quantity_refunded;

    /** @var int */
    public $product_quantity_reinjected;

    /** @var float */
    public $product_price;

    /** @var float */
    public $original_product_price;

    /** @var float */
    public $unit_price_tax_incl;

    /** @var float */
    public $unit_price_tax_excl;

    /** @var float */
    public $total_price_tax_incl;

    /** @var float */
    public $total_price_tax_excl;

    /** @var float */
    public $reduction_percent;

    /** @var float */
    public $reduction_amount;

    /** @var float */
    public $reduction_amount_tax_excl;

    /** @var float */
    public $reduction_amount_tax_incl;

    /** @var float */
    public $group_reduction;

    /** @var float */
    public $product_quantity_discount;

    /** @var string */
    public $product_ean13;

    /** @var string */
    public $product_isbn;

    /** @var string */
    public $product_upc;

    /** @var string */
    public $product_reference;

    /** @var string */
    public $product_supplier_reference;

    /** @var float */
    public $product_weight;

    /** @var float */
    public $ecotax;

    /** @var float */
    public $ecotax_tax_rate;

    /** @var int */
    public $discount_quantity_applied;

    /** @var string */
    public $download_hash;

    /** @var int */
    public $download_nb;

    /** @var datetime */
    public $download_deadline;

    /** @var string $tax_name **/
    public $tax_name;

    /** @var float $tax_rate **/
    public $tax_rate;

    /** @var float */
    public $purchase_supplier_price;

    /** @var float */
    public $original_wholesale_price;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'everpsquotation_quote_detail',
        'primary' => 'id_everpsquotation_quote_detail',
        'fields' => array(
            'id_everpsquotation_quotes' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'product_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'product_attribute_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_customization' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'product_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'product_quantity' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'product_price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'reduction_percent' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'reduction_amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'reduction_amount_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'reduction_amount_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'group_reduction' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'product_quantity_discount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'product_ean13' => array('type' => self::TYPE_STRING, 'validate' => 'isEan13'),
            'product_isbn' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'product_upc' => array('type' => self::TYPE_STRING, 'validate' => 'isUpc'),
            'product_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference'),
            'product_supplier_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference'),
            'product_weight' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'tax_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'tax_rate' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'ecotax' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'discount_quantity_applied' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'unit_price_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_price_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_price_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
        ),
    );

    /** @var bool */
    protected $outOfStock = false;

    /** @var TaxCalculator object */
    protected $tax_calculator = null;

    /** @var Address object */
    protected $vat_address = null;

    /** @var Address object */
    protected $specificPrice = null;

    /** @var Customer object */
    protected $customer = null;

    /** @var Context object */
    protected $context = null;

    public function __construct($id = null, $id_lang = null, $context = null)
    {
        $this->context = $context;
        $id_shop = null;
        if ($this->context != null && isset($this->context->shop)) {
            $id_shop = $this->context->shop->id;
        }
        parent::__construct($id, $id_lang, $id_shop);

        if ($context == null) {
            $context = Context::getContext();
        }
        $this->context = $context->cloneContext();
    }

    public static function getQuoteDetailByQuoteId($id_everpsquotation_quotes, $id_shop, $id_lang)
    {
        $sql = new DbQuery();
        $sql->select('*, GROUP_CONCAT(name SEPARATOR ", ") AS name');
        $sql->from('everpsquotation_quote_detail', 'c');
        $sql->leftJoin('product_attribute', 'pa', 'c.product_attribute_id = pa.id_product_attribute');
        $sql->leftJoin('product_attribute_combination', 'pac', 'c.product_attribute_id = pac.id_product_attribute');
        $sql->leftJoin('attribute_lang', 'al', 'pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$id_lang);
        $sql->where('c.id_everpsquotation_quotes = '.(int)$id_everpsquotation_quotes);
        $sql->where('c.id_shop = '.(int)$id_shop);
        $sql->groupBy('id_everpsquotation_quote_detail');
        $sql->orderBy('id_everpsquotation_quote_detail');
        return Db::getInstance()->executeS($sql);
    }

    public static function getCustomizationValue($id_customization)
    {
        $sql = new DbQuery();
        $sql->select('type, value');
        $sql->from('customized_data');
        $sql->where('id_customization = '.(int)$id_customization);
        $customizations = Db::getInstance()->executeS($sql);
        $return = array();
        foreach ($customizations as $cust) {
            if ((int)$cust['type'] == 1) {
                $return[] = $cust['value'];
            } else {
                $return[] = 'Image';
            }
        }
        return $return;
    }
}

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

use PrestaShop\PrestaShop\Adapter\ServiceLocator;

class EverpsquotationCart extends ObjectModel
{
    public $id;

    public $id_shop_group;

    public $id_shop;

    /** @var int Customer delivery address ID */
    public $id_address_delivery;

    /** @var int Customer invoicing address ID */
    public $id_address_invoice;

    /** @var int Customer currency ID */
    public $id_currency;

    /** @var int Customer ID */
    public $id_customer;

    /** @var int Guest ID */
    public $id_guest;

    /** @var int Language ID */
    public $id_lang;

    /** @var bool True if the customer wants a recycled package */
    public $recyclable = 0;

    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;

    /** @var string Gift message if specified */
    public $gift_message;

    /** @var bool Mobile Theme */
    public $mobile_theme;

    /** @var string Object creation date */
    public $date_add;

    /** @var string secure_key */
    public $secure_key;

    /** @var int Carrier ID */
    public $id_carrier = 0;

    /** @var string Object last modification date */
    public $date_upd;

    public $checkedTos = false;
    public $pictures;
    public $textFields;

    public $delivery_option;

    public $allow_seperated_package = false;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'everpsquotation_cart',
        'primary' => 'id_everpsquotation_cart',
        'fields' => array(
            'id_shop_group' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_shop' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_address_delivery' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_address_invoice' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_carrier' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_currency' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'id_customer' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_guest' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId'
            ),
            'id_lang' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'recyclable' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ),
            'delivery_option' => array(
                'type' => self::TYPE_STRING
            ),
            'secure_key' => array(
                'type' => self::TYPE_STRING,
                'size' => 32
            ),
            'allow_seperated_package' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
        ),
    );

    public static function getEvercartByCustomerId($id_customer, $id_shop, $id_lang)
    {
        $sql = 'SELECT id_everpsquotation_cart 
        FROM '._DB_PREFIX_.'everpsquotation_cart
        WHERE id_customer = '.(int)$id_customer.'
        AND id_shop = '.(int)$id_shop.'
        AND id_lang = '.(int)$id_lang;
        return Db::getInstance()->getValue($sql);
    }

    public static function copyCartToQuoteCart($id_cart)
    {
        $copy_cart_query = Db::getInstance()->Execute(
            'INSERT INTO `'._DB_PREFIX_.'everpsquotation_cart`
            (
                id_shop_group,
                id_shop,
                id_carrier,
                delivery_option,
                id_lang,
                id_address_delivery,
                id_address_invoice,
                id_currency,
                id_customer,
                id_guest,
                secure_key,
                recyclable,
                allow_seperated_package,
                date_add,
                date_upd
            )
            SELECT
            id_shop_group,
            id_shop,
            id_carrier,
            delivery_option,
            id_lang,
            id_address_delivery,
            id_address_invoice,
            id_currency,
            id_customer,
            id_guest,
            secure_key,
            recyclable,
            allow_seperated_package,
            date_add,
            date_upd
            FROM `'._DB_PREFIX_.'cart`
            WHERE id_cart = '.(int)$id_cart
        );
        if ($copy_cart_query) {
            $id_quote_cart = (int)Db::getInstance()->Insert_ID();
            Db::getInstance()->Execute(
                'INSERT INTO `'._DB_PREFIX_.'everpsquotation_cart_product`
                (
                    id_everpsquotation_cart,
                    id_product,
                    id_address_delivery,
                    id_shop,
                    id_product_attribute,
                    id_customization,
                    quantity
                )
                SELECT
                    '.(int)$id_quote_cart.',
                    id_product,
                    id_address_delivery,
                    id_shop,
                    id_product_attribute,
                    id_customization,
                    quantity
                FROM `'._DB_PREFIX_.'cart_product`
                WHERE id_cart = '.(int)$id_cart
            );
            return (int)$id_quote_cart;
        }
        return false;
    }

    public function getProductQtyFromQuoteCart($id_product, $id_product_attribute, $id_customization)
    {
        $sql = new DbQuery();
        $sql->select(
            'quantity'
        );
        $sql->from(
            'everpsquotation_cart_product'
        );
        $sql->where(
            'id_product = '.(int)$id_product
        );
        $sql->where(
            'id_product_attribute = '.(int)$id_product_attribute
        );
        $sql->where(
            'id_customization = '.(int)$id_customization
        );
        return (int)Db::getInstance()->getValue($sql);
    }

    public function addProductToQuoteCart($id_product, $id_product_attribute, $id_customization, $qty)
    {
        $cart_qty = (int)$this->getProductQtyFromQuoteCart(
            (int)$id_product,
            (int)$id_product_attribute,
            (int)$id_customization
        );
        if ($cart_qty > 0) {
            return $this->updateProductToQuoteCart(
                (int)$id_product,
                (int)$id_product_attribute,
                (int)$id_customization,
                (int)$qty
            );
        }
        return Db::getInstance()->insert(
            'everpsquotation_cart_product',
            array(
                'id_everpsquotation_cart' => (int)$this->id,
                'id_product' => (int)$id_product,
                'id_address_delivery' => (int)$this->id_address_delivery,
                'id_shop' => (int)$this->id_shop,
                'id_product_attribute' => (int)$id_product_attribute,
                'id_customization' => (int)$id_customization,
                'quantity' => (int)$qty,
                'date_add' => date('Y-m-d H:i:s')
            ),
            false,
            true,
            Db::INSERT_IGNORE
        );
    }

    public function updateProductToQuoteCart($id_product, $id_product_attribute, $id_customization, $qty)
    {
        $where = 'id_everpsquotation_cart = '.(int)$this->id.' 
            AND id_product = '.(int)$id_product.' 
            AND id_product_attribute = '.(int)$id_product_attribute.' 
            AND id_customization = '.(int)$id_customization;
        return Db::getInstance()->update(
            'everpsquotation_cart_product',
            array(
                'quantity' => (int)$qty
            ),
            $where
        );
    }

    public function deleteProductFromEverCart($id_product, $id_product_attribute, $id_customization)
    {
        $where = 'id_everpsquotation_cart = '.(int)$this->id.' 
            AND id_product = '.(int)$id_product.' 
            AND id_product_attribute = '.(int)$id_product_attribute.' 
            AND id_customization = '.(int)$id_customization;
        return Db::getInstance()->delete(
            'everpsquotation_cart_product',
            $where
        );
    }

    public function dropQuoteCartProducts()
    {
        $where = 'id_everpsquotation_cart = '.(int)$this->id;
        Db::getInstance()->delete(
            'everpsquotation_cart_product',
            $where
        );
        Db::getInstance()->delete(
            'everpsquotation_cart',
            $where
        );
    }

    public function getSummaryDetails($id_cart)
    {
        $cart = new Cart(
            (int)$id_cart
        );
        $delivery = new Address((int) $this->id_address_delivery);
        $invoice = new Address((int) $this->id_address_invoice);
        $context = Context::getContext();
        // New layout system with personalization fields
        $formatted_addresses = [
            'delivery' => AddressFormat::getFormattedLayoutData($delivery),
            'invoice' => AddressFormat::getFormattedLayoutData($invoice),
        ];

        $base_total_tax_inc = $this->getOrderTotal(true);
        $base_total_tax_exc = $this->getOrderTotal(false);

        $total_tax = $base_total_tax_inc - $base_total_tax_exc;

        if ($total_tax < 0) {
            $total_tax = 0;
        }

        $products = $this->getProducts();

        foreach ($products as $key => &$product) {
            $product['price_without_quantity_discount'] = Product::getPriceStatic(
                (int)$product['id_product'],
                !Product::getTaxCalculationMethod(),
                (int)$product['id_product_attribute'],
                6,
                null,
                false,
                false
            );
            $product['price_wt'] = Product::getPriceStatic(
                (int)$product['id_product'],
                true,
                (int)$product['id_product_attribute'],
                6,
                null,
                false,
                false
            );
            $product['array_key'] = $key;

            if ($product['reduction_type'] == 'amount') {
                $reduction = (!Product::getTaxCalculationMethod()
                    ? (float) $product['price_wt']
                    : (float) $product['price']) - (float) $product['price_without_quantity_discount'];

                if (Tools::version_compare(_PS_VERSION_, '1.7.7.1', '<') === true) {
                    $product['reduction_formatted'] = Tools::displayPrice($reduction);
                } else {
                    $product['reduction_formatted'] = Tools::getContextLocale($context)->formatPrice(
                        $reduction,
                        $context->currency->iso_code
                    );
                }
            }
        }

        $summary = [
            'delivery' => $delivery,
            'delivery_state' => State::getNameById($delivery->id_state),
            'invoice' => $invoice,
            'invoice_state' => State::getNameById($invoice->id_state),
            'formattedAddresses' => $formatted_addresses,
            'products' => array_values($products),
            'discounts' => array_values($cart->getCartRules()),
            'total_discounts' => $this->getOrderTotal(true, Cart::ONLY_DISCOUNTS),
            'total_discounts_tax_exc' => $this->getOrderTotal(false, Cart::ONLY_DISCOUNTS),
            'total_wrapping' => $this->getOrderTotal(true, Cart::ONLY_WRAPPING),
            'total_wrapping_tax_exc' => $this->getOrderTotal(false, Cart::ONLY_WRAPPING),
            'total_shipping' => $cart->getTotalShippingCost(),
            'total_shipping_tax_exc' => $cart->getTotalShippingCost(null, false),
            'total_products_wt' => $this->getOrderTotal(true, Cart::ONLY_PRODUCTS),
            'total_products' => $this->getOrderTotal(false, Cart::ONLY_PRODUCTS),
            'total_price' => $base_total_tax_inc,
            'total_tax' => $total_tax,
            'total_price_without_tax' => $base_total_tax_exc,
            'carrier' => new Carrier($this->id_carrier, Context::getContext()->language->id),
        ];

        return $summary;
    }

    public function getProducts()
    {
        if (!$this->id) {
            return [];
        }
        $id_lang = (int)Context::getContext()->language->id;
        $address = new Address(
            (int)$this->id_address_delivery
        );
        $id_country = $address->id_country;
        // Build query
        $sql = new DbQuery();

        // Build SELECT
        $sql->select(
            'cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity,
            cp.id_shop, cp.`id_customization`, pl.`name`, p.`is_virtual`,
            pl.`description_short`, pl.`available_now`, pl.`available_later`,
            product_shop.`id_category_default`, p.`id_supplier`,
            p.`id_manufacturer`, m.`name` AS manufacturer_name, product_shop.`on_sale`,
            product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
            product_shop.`available_for_order`, product_shop.`show_price`, product_shop.`price`,
            product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
            stock.`quantity` AS quantity_available, p.`width`, p.`height`, p.`depth`,
            stock.`out_of_stock`, p.`weight`, p.`available_date`, p.`date_add`, p.`date_upd`,
            IFNULL(stock.quantity, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
            CONCAT(LPAD(cp.`id_product`, 10, 0),
            LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0),
            IFNULL(cp.`id_address_delivery`, 0),
            IFNULL(cp.`id_customization`, 0)) AS unique_id, cp.id_address_delivery,
            product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference'
        );

        // Build FROM
        $sql->from('everpsquotation_cart_product', 'cp');

        // Build JOIN
        $sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->innerJoin(
            'product_shop',
            'product_shop',
            '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)'
        );
        $sql->leftJoin(
            'product_lang',
            'pl',
            'p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang(
                'pl',
                'cp.id_shop'
            )
        );

        $sql->leftJoin(
            'category_lang',
            'cl',
            'product_shop.`id_category_default` = cl.`id_category`
            AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang(
                'cl',
                'cp.id_shop'
            )
        );

        $sql->leftJoin(
            'product_supplier',
            'ps',
            'ps.`id_product` = cp.`id_product`
            AND ps.`id_product_attribute` = cp.`id_product_attribute`
            AND ps.`id_supplier` = p.`id_supplier`'
        );
        $sql->leftJoin(
            'manufacturer',
            'm',
            'm.`id_manufacturer` = p.`id_manufacturer`'
        );

        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sqlStock('cp', 'cp'));

        // Build WHERE clauses
        $sql->where('cp.`id_everpsquotation_cart` = ' . (int) $this->id);
        $sql->where('p.`id_product` IS NOT NULL');

        // Build ORDER BY
        $sql->orderBy('cp.`date_add`, cp.`id_product`, cp.`id_product_attribute` ASC');

        $sql->select('NULL AS customization_quantity, NULL AS id_customization');

        if (Combination::isFeatureActive()) {
            $sql->select('
                product_attribute_shop.`price` AS price_attribute,
                product_attribute_shop.`ecotax` AS ecotax_attr,
                IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
                (p.`weight`+ pa.`weight`) weight_attribute,
                IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
                IF (IFNULL(pa.`isbn`, \'\') = \'\', p.`isbn`, pa.`isbn`) AS isbn,
                IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
                IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
                IF(
                    product_attribute_shop.wholesale_price > 0,
                    product_attribute_shop.wholesale_price, product_shop.`wholesale_price`
                    )
                wholesale_price
            ');

            $sql->leftJoin(
                'product_attribute',
                'pa',
                'pa.`id_product_attribute` = cp.`id_product_attribute`'
            );
            $sql->leftJoin(
                'product_attribute_shop',
                'product_attribute_shop',
                '(product_attribute_shop.`id_shop` = cp.`id_shop`
                AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)'
            );
        } else {
            $sql->select(
                'p.`reference` AS reference, p.`ean13`, p.`isbn`,
                p.`upc` AS upc,
                product_shop.`minimal_quantity` AS minimal_quantity,
                product_shop.`wholesale_price` wholesale_price'
            );
        }

        $sql->select(
            'image_shop.`id_image` id_image, il.`legend`'
        );
        $sql->leftJoin(
            'image_shop',
            'image_shop',
            'image_shop.`id_product` = p.`id_product`
            AND image_shop.cover=1 AND image_shop.id_shop='.(int)$this->id_shop
        );
        $sql->leftJoin(
            'image_lang',
            'il',
            'il.`id_image` = image_shop.`id_image`
            AND il.`id_lang` = '.(int)$id_lang
        );

        $result = Db::getInstance()->executeS($sql);

        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $products_ids = [];
        $pa_ids = [];
        if ($result) {
            foreach ($result as $key => $row) {
                $products_ids[] = $row['id_product'];
                $pa_ids[] = $row['id_product_attribute'];
                $specific_price = SpecificPrice::getSpecificPrice(
                    $row['id_product'],
                    $this->id_shop,
                    $this->id_currency,
                    $id_country,
                    $this->id_shop_group,
                    $row['cart_quantity'],
                    $row['id_product_attribute'],
                    $this->id_customer,
                    $this->id
                );
                if ($specific_price) {
                    $reduction_type_row = ['reduction_type' => $specific_price['reduction_type']];
                } else {
                    $reduction_type_row = ['reduction_type' => 0];
                }

                $result[$key] = array_merge($row, $reduction_type_row);
            }
        }
        Product::cacheProductsFeatures($products_ids);
        Cart::cacheSomeAttributesLists($pa_ids, $this->id_lang);

        if (empty($result)) {
            $this->_products = [];

            return [];
        }
        $this->_products = [];

        foreach ($result as &$row) {
            if (!array_key_exists('is_gift', $row)) {
                $row['is_gift'] = false;
            }

            $additionalRow = Product::getProductProperties((int) $this->id_lang, $row);
            $row['reduction'] = $additionalRow['reduction'];
            $row['reduction_without_tax'] = $additionalRow['reduction_without_tax'];
            $row['price_without_reduction'] = $additionalRow['price_without_reduction'];
            $row['specific_prices'] = $additionalRow['specific_prices'];
            unset($additionalRow);


            $this->_products[] = $row;
        }
        return $this->_products;
    }

    /**
     * This function returns the total cart amount.
     *
     * @param bool $withTaxes With or without taxes
     * @param int $type Total type enum
     *                  - Cart::ONLY_PRODUCTS
     *                  - Cart::ONLY_DISCOUNTS
     *                  - Cart::BOTH
     *                  - Cart::BOTH_WITHOUT_SHIPPING
     *                  - Cart::ONLY_SHIPPING
     *                  - Cart::ONLY_WRAPPING
     *                  - Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING
     *                  - Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING
     * @param array $products
     * @param int $id_carrier
     *
     * @return float Order total
     *
     * @throws \Exception
     */
    public function getOrderTotal(
        $withTaxes = true,
        $type = Cart::BOTH,
        $products = null,
        $id_carrier = null,
        $keepOrderPrices = false
    ) {
        $cart = new Cart(
            (int)Context::getContext()->cart->id
        );
        if ((int) $id_carrier <= 0) {
            $id_carrier = null;
        }

        // deprecated type
        if ($type == Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING) {
            $type = Cart::ONLY_PRODUCTS;
        }

        // check type
        $type = (int) $type;
        $allowedTypes = [
            Cart::ONLY_PRODUCTS,
            Cart::ONLY_DISCOUNTS,
            Cart::BOTH,
            Cart::BOTH_WITHOUT_SHIPPING,
            Cart::ONLY_SHIPPING,
            Cart::ONLY_WRAPPING,
            Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING,
        ];
        if (!in_array($type, $allowedTypes)) {
            throw new \Exception('Invalid calculation type: ' . $type);
        }

        // EARLY RETURNS

        // if cart rules are not used
        if ($type == Cart::ONLY_DISCOUNTS && !CartRule::isFeatureActive()) {
            return 0;
        }

        // filter products
        if (null === $products) {
            $products = $this->getProducts(false, false, null, true, $keepOrderPrices);
        }

        if ($type == Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) {
            foreach ($products as $key => $product) {
                if ($product['is_virtual']) {
                    unset($products[$key]);
                }
            }
            $type = Cart::ONLY_PRODUCTS;
        }

        if (Tax::excludeTaxeOption()) {
            $withTaxes = false;
        }

        // CART CALCULATION
        $cartRules = [];
        // Compute precision has been moved
        if (Tools::version_compare(_PS_VERSION_, '1.7.7.1', '<') === true) {
            $configuration = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\ConfigurationInterface');
            $computePrecision = $configuration->get('_PS_PRICE_COMPUTE_PRECISION_');
        } else {
            $computePrecision = Context::getContext()->getComputingPrecision();
        }
        $calculator = $cart->newCalculator($products, $cartRules, $id_carrier, $computePrecision, $keepOrderPrices);
        switch ($type) {
            case Cart::ONLY_SHIPPING:
                $calculator->calculateRows();
                $calculator->calculateFees();
                $amount = $calculator->getFees()->getInitialShippingFees();

                break;
            case Cart::ONLY_WRAPPING:
                $calculator->calculateRows();
                $calculator->calculateFees();
                $amount = $calculator->getFees()->getInitialWrappingFees();

                break;
            case Cart::BOTH:
                $calculator->processCalculation();
                $amount = $calculator->getTotal();

                break;
            case Cart::BOTH_WITHOUT_SHIPPING:
                $calculator->calculateRows();
                // dont process free shipping to avoid calculation loop (and maximum nested functions !)
                $calculator->calculateCartRulesWithoutFreeShipping();
                $amount = $calculator->getTotal(true);
                break;
            case Cart::ONLY_PRODUCTS:
                $calculator->calculateRows();
                $amount = $calculator->getRowTotal();

                break;
            case Cart::ONLY_DISCOUNTS:
                $calculator->processCalculation();
                $amount = $calculator->getDiscountTotal();

                break;
            default:
                throw new \Exception('unknown cart calculation type : ' . $type);
        }

        // TAXES ?

        $value = $withTaxes ? $amount->getTaxIncluded() : $amount->getTaxExcluded();

        // ROUND AND RETURN

        return Tools::ps_round($value, $computePrecision);
    }
}

<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class CartCore extends ObjectModel
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

    /** @var bool Allow to seperate order in multiple package in order to recieve as soon as possible the available products */
    public $allow_seperated_package = false;

    /**
    * @var int is_advance_payment used to determine if this cart is selected for advance payment or full payment
    */
    public $is_advance_payment;

    protected static $_nbProducts = array();
    protected static $_isVirtualCart = array();

    protected $_products = null;
    protected static $_totalWeight = array();
    protected $_taxCalculationMethod = PS_TAX_EXC;
    protected static $_carriers = null;
    protected static $_taxes_rate = null;
    protected static $_attributesLists = array();

    /** @var Customer|null */
    protected static $_customer = null;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'cart',
        'primary' => 'id_cart',
        'fields' => array(
            'id_shop_group' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' =>                array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_delivery' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_address_invoice' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_carrier' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_currency' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_guest' =>                array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_lang' =>                array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'recyclable' =>            array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'gift' =>                    array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'gift_message' =>            array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'mobile_theme' =>            array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'delivery_option' =>        array('type' => self::TYPE_STRING),
            'secure_key' =>            array('type' => self::TYPE_STRING, 'size' => 32),
            'allow_seperated_package' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'is_advance_payment' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0),
            'date_add' =>                array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>                array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    protected $webserviceParameters = array(
        'objectMethods' => array(
            'add' => 'addWs',
            'update' => 'updateWs'
        ),
        'fields' => [
            'id_currency' => ['xlink_resource' => 'currencies'],
            'id_customer' => ['xlink_resource' => 'customers'],
            'id_guest' => ['xlink_resource' => 'guests'],
            'id_lang' => ['xlink_resource' => 'languages'],
            'id_address_delivery' => ['xlink_resource' => 'addresses'],
            'id_address_invoice' => ['xlink_resource' => 'addresses'],
        ],
        'hidden_fields' => array (
            'id_shop',
            'id_shop_group',
            'id_carrier',
            'delivery_option',
            'allow_seperated_package',
            'gift',
            'gift_message',
            'mobile_theme',
            'recyclable',
        ),
        'associations' => array(
            'cart_bookings' => array(
                'resource' => 'booking',
                'fields' => array(
                    'id_hotel' => array('validate' => 'isUnsignedId', 'required' => true),
                    'id_product' => array('validate' => 'isUnsignedId', 'required' => true),
                    'date_from' => array('validate' => 'isDate', 'required' => true),
                    'date_to' => array('validate' => 'isDate', 'required' => true),
                )
            ),
        ),
    );

    const ONLY_PRODUCTS = 1;
    const ONLY_DISCOUNTS = 2;
    const BOTH = 3;
    const BOTH_WITHOUT_SHIPPING = 4;
    const ONLY_SHIPPING = 5;
    const ONLY_WRAPPING = 6;
    const ONLY_PRODUCTS_WITHOUT_SHIPPING = 7;
    const ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING = 8;
    const ADVANCE_PAYMENT = 9;
    const ADVANCE_PAYMENT_ONLY_PRODUCTS = 10;
    const ONLY_ROOMS = 11;
    const ONLY_ROOM_SERVICES = 12;
    const ONLY_STANDALONE_PRODUCTS = 13;

    const ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE = 14;
    const ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD = 16;
    const ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE = 17;
    const ONLY_CONVENIENCE_FEE = 18;

    const ONLY_PRODUCTS_WITH_DEMANDS = 19;

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id);

        if (!is_null($id_lang)) {
            $this->id_lang = (int)(Language::getLanguage($id_lang) !== false) ? $id_lang : Configuration::get('PS_LANG_DEFAULT');
        }

        if ($this->id_customer) {
            if (isset(Context::getContext()->customer) && Context::getContext()->customer->id == $this->id_customer) {
                $customer = Context::getContext()->customer;
            } else {
                $customer = new Customer((int)$this->id_customer);
            }

            Cart::$_customer = $customer;

            if ((!$this->secure_key || $this->secure_key == '-1') && $customer->secure_key) {
                $this->secure_key = $customer->secure_key;
                $this->save();
            }
        }

        $this->setTaxCalculationMethod();
    }

    public function setTaxCalculationMethod()
    {
        $this->_taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
    }

    public function add($autodate = true, $null_values = false)
    {
        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!$this->id_shop) {
            $this->id_shop = Context::getContext()->shop->id;
        }

        $return = parent::add($autodate, $null_values);
        Hook::exec('actionCartSave');

        return $return;
    }

    public function update($null_values = false)
    {
        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }

        $this->_products = null;
        $return = parent::update($null_values);
        Hook::exec('actionCartSave');

        return $return;
    }

    /**
     * Update the address id of the cart
     *
     * @param int $id_address Current address id to change
     * @param int $id_address_new New address id
     */
    public function updateAddressId($id_address, $id_address_new)
    {
        $to_update = false;
        if (!isset($this->id_address_invoice) || $this->id_address_invoice == $id_address) {
            $to_update = true;
            $this->id_address_invoice = $id_address_new;
        }
        if (!isset($this->id_address_delivery) || $this->id_address_delivery == $id_address) {
            $to_update = true;
            $this->id_address_delivery = $id_address_new;
        }
        if ($to_update) {
            $this->update();
        }

        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
		SET `id_address_delivery` = '.(int)$id_address_new.'
		WHERE  `id_cart` = '.(int)$this->id.'
			AND `id_address_delivery` = '.(int)$id_address;
        Db::getInstance()->execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = '.(int)$id_address_new.'
			WHERE  `id_cart` = '.(int)$this->id.'
				AND `id_address_delivery` = '.(int)$id_address;
        Db::getInstance()->execute($sql);
    }

    public function delete()
    {
        if ($this->OrderExists()) { //NOT delete a cart which is associated with an order
            return false;
        }

        $uploaded_files = Db::getInstance()->executeS('
			SELECT cd.`value`
			FROM `'._DB_PREFIX_.'customized_data` cd
			INNER JOIN `'._DB_PREFIX_.'customization` c ON (cd.`id_customization`= c.`id_customization`)
			WHERE cd.`type`= 0 AND c.`id_cart`='.(int)$this->id
        );

        foreach ($uploaded_files as $must_unlink) {
            Tools::deleteFile(_PS_UPLOAD_DIR_.$must_unlink['value'].'_small'); //by webkul use deleteFile insted of unlink
            Tools::deleteFile(_PS_UPLOAD_DIR_.$must_unlink['value']); //by webkul use deleteFile insted of unlink
        }

        Db::getInstance()->execute('
			DELETE cd
			FROM `' . _DB_PREFIX_ . 'customized_data` cd, `' . _DB_PREFIX_ . 'customization` c
			WHERE cd.`id_customization` = c.`id_customization`
			AND `id_cart` = ' . (int) $this->id
        );

        Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int)$this->id
        );

        if (!Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_cart_rule` WHERE `id_cart` = '.(int)$this->id)
         || !Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_product` WHERE `id_cart` = '.(int)$this->id)) {
            return false;
        }

        return parent::delete();
    }

    public static function getTaxesAverageUsed($id_cart)
    {
        $cart = new Cart((int)$id_cart);
        if (!Validate::isLoadedObject($cart)) {
            die(Tools::displayError());
        }

        if (!Configuration::get('PS_TAX')) {
            return 0;
        }

        $products = $cart->getProducts();
        $total_products_moy = 0;
        $ratio_tax = 0;

        if (!count($products)) {
            return 0;
        }

        foreach ($products as $product) {
            // products refer to the cart details
            $address_id = Cart::getIdAddressForTaxCalculation($product['id_product']);
            if (!Address::addressExists($address_id)) {
                $address_id = null;
            }

            $total_products_moy += $product['total_wt'];
            $ratio_tax += $product['total_wt'] * Tax::getProductTaxRate(
                (int)$product['id_product'],
                (int)$address_id
            );
        }

        if ($total_products_moy > 0) {
            return $ratio_tax / $total_products_moy;
        }

        return 0;
    }

    /**
     * The arguments are optional and only serve as return values in case caller needs the details.
     */
    public function getAverageProductsTaxRate(&$cart_amount_te = null, &$cart_amount_ti = null)
    {
        $cart_amount_ti = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS_WITH_DEMANDS);
        $cart_amount_te = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS_WITH_DEMANDS);

        $cart_vat_amount = $cart_amount_ti - $cart_amount_te;

        if ($cart_vat_amount == 0 || $cart_amount_te == 0) {
            return 0;
        } else {
            return Tools::ps_round($cart_vat_amount / $cart_amount_te, 6);
        }
    }

    /**
     * @deprecated 1.5.0, use Cart->getCartRules()
     */
    public function getDiscounts($lite = false, $refresh = false)
    {
        Tools::displayAsDeprecated();
        return $this->getCartRules();
    }

    public function getCartRules($filter = CartRule::FILTER_ACTION_ALL)
    {
        // If the cart has not been saved, then there can't be any cart rule applied
        if (!CartRule::isFeatureActive() || !$this->id) {
            return array();
        }

        $cache_key = 'Cart::getCartRules_'.$this->id.'-'.$filter;
        if (!Cache::isStored($cache_key)) {
            $result = Db::getInstance()->executeS('
				SELECT cr.*, crl.`id_lang`, crl.`name`, cd.`id_cart`
				FROM `'._DB_PREFIX_.'cart_cart_rule` cd
				LEFT JOIN `'._DB_PREFIX_.'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
				LEFT JOIN `'._DB_PREFIX_.'cart_rule_lang` crl ON (
					cd.`id_cart_rule` = crl.`id_cart_rule`
					AND crl.id_lang = '.(int)$this->id_lang.'
				)
				WHERE `id_cart` = '.(int)$this->id.'
				'.($filter == CartRule::FILTER_ACTION_SHIPPING ? 'AND free_shipping = 1' : '').'
				'.($filter == CartRule::FILTER_ACTION_GIFT ? 'AND gift_product != 0' : '').'
				'.($filter == CartRule::FILTER_ACTION_REDUCTION ? 'AND (reduction_percent != 0 OR reduction_amount != 0)' : '')
                .' ORDER by cr.priority ASC'
            );
            Cache::store($cache_key, $result);
        } else {
            $result = Cache::retrieve($cache_key);
        }

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::getContext()->cloneContext();
        $virtual_context->cart = $this;

        foreach ($result as &$row) {
            $row['obj'] = new CartRule($row['id_cart_rule'], (int)$this->id_lang);
            $row['value_real'] = $row['obj']->getContextualValue(true, $virtual_context, $filter);
            $row['value_tax_exc'] = $row['obj']->getContextualValue(false, $virtual_context, $filter);
            // Retro compatibility < 1.5.0.2
            $row['id_discount'] = $row['id_cart_rule'];
            $row['description'] = $row['name'];
        }

        return $result;
    }

    /**
     * Return the cart rules Ids on the cart.
     * @param $filter
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getOrderedCartRulesIds($filter = CartRule::FILTER_ACTION_ALL)
    {
        $cache_key = 'Cart::getOrderedCartRulesIds_'.$this->id.'-'.$filter.'-ids';
        if (!Cache::isStored($cache_key)) {
            $result = Db::getInstance()->executeS('
				SELECT cr.`id_cart_rule`
				FROM `'._DB_PREFIX_.'cart_cart_rule` cd
				LEFT JOIN `'._DB_PREFIX_.'cart_rule` cr ON cd.`id_cart_rule` = cr.`id_cart_rule`
				LEFT JOIN `'._DB_PREFIX_.'cart_rule_lang` crl ON (
					cd.`id_cart_rule` = crl.`id_cart_rule`
					AND crl.id_lang = '.(int)$this->id_lang.'
				)
				WHERE `id_cart` = '.(int)$this->id.'
				'.($filter == CartRule::FILTER_ACTION_SHIPPING ? 'AND free_shipping = 1' : '').'
				'.($filter == CartRule::FILTER_ACTION_GIFT ? 'AND gift_product != 0' : '').'
				'.($filter == CartRule::FILTER_ACTION_REDUCTION ? 'AND (reduction_percent != 0 OR reduction_amount != 0)' : '')
                .' ORDER BY cr.priority ASC'
            );
            Cache::store($cache_key, $result);
        } else {
            $result = Cache::retrieve($cache_key);
        }

        return $result;
    }

    public function getDiscountsCustomer($id_cart_rule)
    {
        if (!CartRule::isFeatureActive()) {
            return 0;
        }
        $cache_id = 'Cart::getDiscountsCustomer_'.(int)$this->id.'-'.(int)$id_cart_rule;
        if (!Cache::isStored($cache_id)) {
            $result = (int)Db::getInstance()->getValue('
				SELECT COUNT(*)
				FROM `'._DB_PREFIX_.'cart_cart_rule`
				WHERE `id_cart_rule` = '.(int)$id_cart_rule.' AND `id_cart` = '.(int)$this->id);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }

    public function getLastProduct()
    {
        $sql = '
			SELECT `id_product`, `id_product_attribute`, id_shop
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE `id_cart` = '.(int)$this->id.'
			ORDER BY `date_add` DESC';

        $result = Db::getInstance()->getRow($sql);
        if ($result && isset($result['id_product']) && $result['id_product']) {
            foreach ($this->getProducts() as $product) {
                if ($result['id_product'] == $product['id_product']
                && (
                    !$result['id_product_attribute']
                    || $result['id_product_attribute'] == $product['id_product_attribute']
                )) {
                    return $product;
                }
            }
        }

        return false;
    }

    /**
     * Return cart products
     *
     * @result array Products
     */
    public function getProducts($refresh = false, $id_product = false, $id_country = null)
    {
        if (!$this->id) {
            return array();
        }
        // Product cache must be strictly compared to NULL, or else an empty cart will add dozens of queries
        if ($this->_products !== null && !$refresh) {
            // Return product row with specified ID if it exists
            if (is_int($id_product)) {
                foreach ($this->_products as $product) {
                    if ($product['id_product'] == $id_product) {
                        return array($product);
                    }
                }
                return array();
            }
            return $this->_products;
        }

        // Build query
        $sql = new DbQuery();

        // Build SELECT
        $sql->select('cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity, cp.id_shop, pl.`name`, p.`is_virtual`, p.`booking_product`, p.`selling_preference_type`,  p.`auto_add_to_cart`, p.`price_addition_type`, p.`show_at_front`, product_shop.`allow_multiple_quantity`, product_shop.`price_calculation_method`,
						pl.`description_short`, pl.`available_now`, pl.`available_later`, product_shop.`id_category_default`, p.`id_supplier`,
						p.`id_manufacturer`, product_shop.`on_sale`, product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
						product_shop.`available_for_order`, product_shop.`price`, product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
						stock.`quantity` AS quantity_available, p.`width`, p.`height`, p.`depth`, stock.`out_of_stock`, p.`weight`,
						p.`date_add`, p.`date_upd`, IFNULL(stock.quantity, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
						CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id, cp.id_address_delivery,
						product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference');

        // Build FROM
        $sql->from('cart_product', 'cp');

        // Build JOIN
        $sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->innerJoin('product_shop', 'product_shop', '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)');
        $sql->leftJoin('product_lang', 'pl', '
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int)$this->id_lang.Shop::addSqlRestrictionOnLang('pl', 'cp.id_shop')
        );

        $sql->leftJoin('category_lang', 'cl', '
			product_shop.`id_category_default` = cl.`id_category`
			AND cl.`id_lang` = '.(int)$this->id_lang.Shop::addSqlRestrictionOnLang('cl', 'cp.id_shop')
        );

        $sql->leftJoin('product_supplier', 'ps', 'ps.`id_product` = cp.`id_product` AND ps.`id_product_attribute` = cp.`id_product_attribute` AND ps.`id_supplier` = p.`id_supplier`');

        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sqlStock('cp', 'cp'));

        // Build WHERE clauses
        $sql->where('cp.`id_cart` = '.(int)$this->id);
        if ($id_product) {
            $sql->where('cp.`id_product` = '.(int)$id_product);
        }
        $sql->where('p.`id_product` IS NOT NULL');

        // Build ORDER BY
        $sql->orderBy('cp.`date_add`, cp.`id_product`, cp.`id_product_attribute` ASC');

        if (Customization::isFeatureActive()) {
            $sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity');
            $sql->leftJoin('customization', 'cu',
                'p.`id_product` = cu.`id_product` AND cp.`id_product_attribute` = cu.`id_product_attribute` AND cu.`id_cart` = '.(int)$this->id);
            $sql->groupBy('cp.`id_product_attribute`, cp.`id_product`, cp.`id_shop`');
        } else {
            $sql->select('NULL AS customization_quantity, NULL AS id_customization');
        }

        if (Combination::isFeatureActive()) {
            $sql->select('
				product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr,
				IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
				(p.`weight`+ pa.`weight`) weight_attribute,
				IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
				IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
				IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
				IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price
			');

            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
        } else {
            $sql->select(
                'p.`reference` AS reference, p.`ean13`,
				p.`upc` AS upc, product_shop.`minimal_quantity` AS minimal_quantity, product_shop.`wholesale_price` wholesale_price'
            );
        }

        $sql->select('image_shop.`id_image` id_image, il.`legend`');
        $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int)$this->id_shop);
        $sql->leftJoin('image_lang', 'il', 'il.`id_image` = image_shop.`id_image` AND il.`id_lang` = '.(int)$this->id_lang);

        $result = Db::getInstance()->executeS($sql);

        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $products_ids = array();
        $pa_ids = array();
        if ($result) {
            foreach ($result as $key => $row) {
                $products_ids[] = $row['id_product'];
                $pa_ids[] = $row['id_product_attribute'];
                $specific_price = SpecificPrice::getSpecificPrice($row['id_product'], $this->id_shop, $this->id_currency, $id_country, $this->id_shop_group, $row['cart_quantity'], $row['id_product_attribute'], $this->id_customer, $this->id);
                if ($specific_price) {
                    $reduction_type_row = array('reduction_type' => $specific_price['reduction_type']);
                } else {
                    $reduction_type_row = array('reduction_type' => 0);
                }

                $result[$key] = array_merge($row, $reduction_type_row);
            }
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheProductsFeatures($products_ids);
        Cart::cacheSomeAttributesLists($pa_ids, $this->id_lang);

        $this->_products = array();
        if (empty($result)) {
            return array();
        }

        $cart_shop_context = Context::getContext()->cloneContext();
        foreach ($result as &$row) {
            if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0) {
                $row['ecotax'] = (float)$row['ecotax_attr'];
            }

            $row['stock_quantity'] = (int)$row['quantity'];
            // for compatibility with 1.2 themes
            $row['quantity'] = (int)$row['cart_quantity'];

            if (isset($row['id_product_attribute']) && (int)$row['id_product_attribute'] && isset($row['weight_attribute'])) {
                $row['weight'] = (float)$row['weight_attribute'];
            }

            // hotel address for tax calculation
            $address_id = Cart::getIdAddressForTaxCalculation($row['id_product']);
            if (!Address::addressExists($address_id)) {
                $address_id = null;
            }

            if ($cart_shop_context->shop->id != $row['id_shop']) {
                $cart_shop_context->shop = new Shop((int)$row['id_shop']);
            }

            $address = Address::initialize($address_id, true);
            $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$row['id_product'], $cart_shop_context);
            $tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

            $row['price_without_reduction'] = Product::getPriceStatic(
                (int)$row['id_product'],
                true,
                isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
                6,
                null,
                false,
                false,
                $row['cart_quantity'],
                false,
                (int)$this->id_customer ? (int)$this->id_customer : null,
                (int)$this->id,
                $address_id,
                $specific_price_output,
                true,
                true,
                $cart_shop_context
            );

            $row['price_with_reduction'] = Product::getPriceStatic(
                (int)$row['id_product'],
                true,
                isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
                6,
                null,
                false,
                true,
                $row['cart_quantity'],
                false,
                (int)$this->id_customer ? (int)$this->id_customer : null,
                (int)$this->id,
                $address_id,
                $specific_price_output,
                true,
                true,
                $cart_shop_context
            );

            $row['price'] = $row['price_with_reduction_without_tax'] = Product::getPriceStatic(
                (int)$row['id_product'],
                false,
                isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
                6,
                null,
                false,
                true,
                $row['cart_quantity'],
                false,
                (int)$this->id_customer ? (int)$this->id_customer : null,
                (int)$this->id,
                $address_id,
                $specific_price_output,
                true,
                true,
                $cart_shop_context
            );

            if ($row['booking_product']) {
                if (Module::isInstalled('hotelreservationsystem') && Module::isEnabled('hotelreservationsystem')) {
                    require_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';
                    // by webkul to calculate rates of the product from hotelreservation syatem tables with feature prices....
                    $objCartBookingData = new HotelCartBookingData();
                    $roomTypesByIdProduct = $objCartBookingData->getCartInfoIdCartIdProduct($this->id, (int)$row['id_product']);

                    // by webkul to calculate rates of the product from hotelreservation syatem tables with feature prices....
                    $totalPriceByProductTaxIncl = 0;
                    $totalPriceByProductTaxExcl = 0;
                    $priceDisplay = Group::getPriceDisplayMethod(Group::getCurrent()->id);
                    foreach ($roomTypesByIdProduct as $key => $cartRoomInfo) {
                        $occupancy = array(
                            array(
                                'adults' => $cartRoomInfo['adults'],
                                'children' => $cartRoomInfo['children'],
                                'child_ages' => json_decode($cartRoomInfo['child_ages'])
                            )
                        );
                        $roomTotalPrice = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice(
                            $cartRoomInfo['id_product'],
                            $cartRoomInfo['date_from'],
                            $cartRoomInfo['date_to'],
                            $occupancy,
                            Group::getCurrent()->id,
                            $cartRoomInfo['id_cart'],
                            $cartRoomInfo['id_guest'],
                            $cartRoomInfo['id_room'],
                            0
                        );
                        $totalPriceByProductTaxIncl += $roomTotalPrice['total_price_tax_incl'];
                        $totalPriceByProductTaxExcl += $roomTotalPrice['total_price_tax_excl'];
                    }

                    // Rounding as per configurations
                    $row['total'] = Tools::processPriceRounding($totalPriceByProductTaxExcl);
                    $row['total_wt'] = Tools::processPriceRounding($totalPriceByProductTaxIncl);
                }
            } else {
                $row['total'] = 0;
                $row['total_wt'] = 0;
                $objServiceProductCartDetail = new ServiceProductCartDetail();
                $price_tax_excl = $objServiceProductCartDetail->getServiceProductsInCart(
                    $this->id,
                    [],
                    null,
                    null,
                    null,
                    $row['id_product'],
                    null,
                    false,
                    1
                );
                $price_tax_incl = $objServiceProductCartDetail->getServiceProductsInCart(
                    $this->id,
                    [],
                    null,
                    null,
                    null,
                    $row['id_product'],
                    null,
                    true,
                    1
                );
                // Rounding as per configurations
                $row['total'] += $price_tax_excl;
                $row['total_wt'] += $price_tax_incl;
            }

            /*END*/
            $row['price_wt'] = $row['price_with_reduction'];
            $row['description_short'] = Tools::nl2br($row['description_short']);

            // check if a image associated with the attribute exists
            if ($row['id_product_attribute']) {
                $row2 = Image::getBestImageAttribute($row['id_shop'], $this->id_lang, $row['id_product'], $row['id_product_attribute']);
                if ($row2) {
                    $row = array_merge($row, $row2);
                }
            }

            $row['reduction_applies'] = ($specific_price_output && (float)$specific_price_output['reduction']);
            $row['quantity_discount_applies'] = ($specific_price_output && $row['cart_quantity'] >= (int)$specific_price_output['from_quantity']);
            $row['id_image'] = Product::defineProductImage($row, $this->id_lang);
            $row['allow_oosp'] = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
            $row['features'] = Product::getFeaturesStatic((int)$row['id_product']);

            if (array_key_exists($row['id_product_attribute'].'-'.$this->id_lang, self::$_attributesLists)) {
                $row = array_merge($row, self::$_attributesLists[$row['id_product_attribute'].'-'.$this->id_lang]);
            }

            $row = Product::getTaxesInformations($row, $cart_shop_context);

            $this->_products[] = $row;
        }

        return $this->_products;
    }

    public function getServiceProducts($productList = false)
    {
        if (!is_array($productList)) {
            $productList = $this->getProducts();
        }
        $serviceProducts = array();
        $context = Context::getContext();
        foreach ($productList as $key => $product) {
            if (!$product['booking_product'] && (Product::SELLING_PREFERENCE_STANDALONE == $product['selling_preference_type'])) {
                if (Validate::isLoadedObject(
                    $objProduct = new Product($product['id_product'], false, $this->id_lang)
                )) {
                    // $address = Address::initialize($product['id_address_delivery']);
                    // $product['id_hotel'] = $address->id_hotel;
                    // $objHotelBranch = new HotelBranchInformation();
                    // $product['hotel'] = $objHotelBranch->hotelBranchInfoById($product['id_hotel']);
                    $coverImageArr = $objProduct->getCover($product['id_product']);
                    if (!empty($coverImageArr)) {
                        $coverImg = $context->link->getImageLink(
                            $objProduct->link_rewrite,
                            $objProduct->id.'-'.$coverImageArr['id_image'],
                            'small_default'
                        );
                    } else {
                        $coverImg = $context->link->getImageLink(
                            $objProduct->link_rewrite,
                            $context->language->iso_code.'-default',
                            'small_default'
                        );
                    }
                    $product['cover_img'] = $coverImg;
                    $serviceProducts[] = $product;
                }
            }
        }

        return $serviceProducts;
    }

    public static function cacheSomeAttributesLists($ipa_list, $id_lang)
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        $pa_implode = array();

        foreach ($ipa_list as $id_product_attribute) {
            if ((int)$id_product_attribute && !array_key_exists($id_product_attribute.'-'.$id_lang, self::$_attributesLists)) {
                $pa_implode[] = (int)$id_product_attribute;
                self::$_attributesLists[(int)$id_product_attribute.'-'.$id_lang] = array('attributes' => '', 'attributes_small' => '');
            }
        }

        if (!count($pa_implode)) {
            return;
        }

        $result = Db::getInstance()->executeS('
			SELECT pac.`id_product_attribute`, agl.`public_name` AS public_group_name, al.`name` AS attribute_name
			FROM `'._DB_PREFIX_.'product_attribute_combination` pac
			LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
			LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (
				a.`id_attribute` = al.`id_attribute`
				AND al.`id_lang` = '.(int)$id_lang.'
			)
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (
				ag.`id_attribute_group` = agl.`id_attribute_group`
				AND agl.`id_lang` = '.(int)$id_lang.'
			)
			WHERE pac.`id_product_attribute` IN ('.implode(',', $pa_implode).')
			ORDER BY ag.`position` ASC, a.`position` ASC'
        );

        foreach ($result as $row) {
            self::$_attributesLists[$row['id_product_attribute'].'-'.$id_lang]['attributes'] .= $row['public_group_name'].' : '.$row['attribute_name'].', ';
            self::$_attributesLists[$row['id_product_attribute'].'-'.$id_lang]['attributes_small'] .= $row['attribute_name'].', ';
        }

        foreach ($pa_implode as $id_product_attribute) {
            self::$_attributesLists[$id_product_attribute.'-'.$id_lang]['attributes'] = rtrim(
                self::$_attributesLists[$id_product_attribute.'-'.$id_lang]['attributes'],
                ', '
            );

            self::$_attributesLists[$id_product_attribute.'-'.$id_lang]['attributes_small'] = rtrim(
                self::$_attributesLists[$id_product_attribute.'-'.$id_lang]['attributes_small'],
                ', '
            );
        }
    }

    /**
     * Return cart products quantity
     *
     * @result integer Products quantity
     */
    public function nbProducts()
    {
        if (!$this->id) {
            return 0;
        }

        return Cart::getNbProducts($this->id);
    }

    public static function getNbProducts($id)
    {
        // Must be strictly compared to NULL, or else an empty cart will bypass the cache and add dozens of queries
        if (isset(self::$_nbProducts[$id]) && self::$_nbProducts[$id] !== null) {
            return self::$_nbProducts[$id];
        }

        self::$_nbProducts[$id] = (int)Db::getInstance()->getValue('
			SELECT SUM(`quantity`)
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE `id_cart` = '.(int)$id
        );

        return self::$_nbProducts[$id];
    }

    /**
     * @deprecated 1.5.0, use Cart->addCartRule()
     */
    public function addDiscount($id_cart_rule)
    {
        Tools::displayAsDeprecated();
        return $this->addCartRule($id_cart_rule);
    }

    public function addCartRule($id_cart_rule)
    {
        // You can't add a cart rule that does not exist
        $cartRule = new CartRule($id_cart_rule, Context::getContext()->language->id);

        if (!Validate::isLoadedObject($cartRule)) {
            return false;
        }

        if (Db::getInstance()->getValue('SELECT id_cart_rule FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart_rule = '.(int)$id_cart_rule.' AND id_cart = '.(int)$this->id)) {
            return false;
        }

        // Add the cart rule to the cart
        if (!Db::getInstance()->insert('cart_cart_rule', array(
            'id_cart_rule' => (int)$id_cart_rule,
            'id_cart' => (int)$this->id
        ))) {
            return false;
        }

        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL);
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING);
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION);
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT);

        Cache::clean('Cart::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL. '-ids');
        Cache::clean('Cart::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING. '-ids');
        Cache::clean('Cart::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION. '-ids');
        Cache::clean('Cart::getOrderedCartRulesIds_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT. '-ids');

        if ((int)$cartRule->gift_product) {
            $this->updateQty(1, $cartRule->gift_product, $cartRule->gift_product_attribute, false, 'up', 0, null, false);
        }
        // after adding new cart rule, check if any of the appled cart rules are not being used
        CartRule::autoRemoveFromCart(Context::getContext());
        CartRule::autoAddToCart(Context::getContext());

        return true;
    }

    public function containsProduct($id_product, $id_product_attribute = 0, $id_customization = 0, $id_address_delivery = 0)
    {
        $sql = 'SELECT cp.`quantity` FROM `'._DB_PREFIX_.'cart_product` cp';

        if ($id_customization) {
            $sql .= '
				LEFT JOIN `'._DB_PREFIX_.'customization` c ON (
					c.`id_product` = cp.`id_product`
					AND c.`id_product_attribute` = cp.`id_product_attribute`
				)';
        }

        $sql .= '
			WHERE cp.`id_product` = '.(int)$id_product.'
			AND cp.`id_product_attribute` = '.(int)$id_product_attribute.'
			AND cp.`id_cart` = '.(int)$this->id;
        if (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery()) {
            $sql .= ' AND cp.`id_address_delivery` = '.(int)$id_address_delivery;
        }

        if ($id_customization) {
            $sql .= ' AND c.`id_customization` = '.(int)$id_customization;
        }

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Update product quantity
     *
     * @param int $quantity Quantity to add (or substract)
     * @param int $id_product Product ID
     * @param int $id_product_attribute Attribute ID if needed
     * @param string $operator Indicate if quantity must be increased or decreased
     */
    public function updateQty($quantity, $id_product, $id_product_attribute = null, $id_customization = false,
        $operator = 'up', $id_address_delivery = 0, ?Shop $shop = null, $auto_add_cart_rule = true)
    {
        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        if (!empty(Context::getContext()->customer->id)) {
            if ($id_address_delivery == 0 && (int)$this->id_address_delivery) { // The $id_address_delivery is null, use the cart delivery address
                $id_address_delivery = $this->id_address_delivery;
            } elseif (!Customer::customerHasAddress(Context::getContext()->customer->id, $id_address_delivery)) { // The $id_address_delivery must be linked with customer
                $id_address_delivery = 0;
            }
        }

        $quantity = (int)$quantity;
        $id_product = (int)$id_product;
        $id_product_attribute = (int)$id_product_attribute;
        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);

        if ($id_product_attribute) {
            $combination = new Combination((int)$id_product_attribute);
            if ($combination->id_product != $id_product) {
                return false;
            }
        }

        /* If we have a product combination, the minimal quantity is set with the one of this combination */
        if (!empty($id_product_attribute)) {
            $minimal_quantity = (int)ProductAttribute::getAttributeMinimalQty($id_product_attribute);
        } else {
            $minimal_quantity = (int)$product->minimal_quantity;
        }

        if (!Validate::isLoadedObject($product)) {
            die(Tools::displayError('Some products are not found. Please refresh the page.'));
        }

        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }

        Hook::exec('actionBeforeCartUpdateQty', array(
            'cart' => $this,
            'product' => $product,
            'id_product_attribute' => $id_product_attribute,
            'id_customization' => $id_customization,
            'quantity' => $quantity,
            'operator' => $operator,
            'id_address_delivery' => $id_address_delivery,
            'shop' => $shop,
            'auto_add_cart_rule' => $auto_add_cart_rule,
        ));

        if ((int)$quantity <= 0) {
            return $this->deleteProduct($id_product, $id_product_attribute, (int)$id_customization, 0, $auto_add_cart_rule);
        } elseif (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_')) {
            return false;
        } else {
            /* Check if the product is already in the cart */
            $result = $this->containsProduct($id_product, $id_product_attribute, (int)$id_customization, (int)$id_address_delivery);

            /* Update quantity if product already exist */
            if ($result) {
                if ($operator == 'up') {
                    $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
							FROM '._DB_PREFIX_.'product p
							'.Product::sqlStock('p', $id_product_attribute, true, $shop).'
							WHERE p.id_product = '.$id_product;

                    $result2 = Db::getInstance()->getRow($sql);
                    $product_qty = (int)$result2['quantity'];
                    // Quantity for product pack
                    if (Pack::isPack($id_product)) {
                        $product_qty = Pack::getQuantity($id_product, $id_product_attribute);
                    }
                    $new_qty = (int)$result['quantity'] + (int)$quantity;
                    $qty = '+ '.(int)$quantity;

                    if (!Product::isAvailableWhenOutOfStock((int)$result2['out_of_stock'])) {
                        if ($new_qty > $product_qty) {
                            return false;
                        }
                    }
                } elseif ($operator == 'down') {
                    $qty = '- '.(int)$quantity;
                    $new_qty = (int)$result['quantity'] - (int)$quantity;
                    if ($new_qty < $minimal_quantity && $minimal_quantity > 1) {
                        return -1;
                    }
                } else {
                    return false;
                }

                /* Delete product from cart */
                if ($new_qty <= 0) {
                    return $this->deleteProduct((int)$id_product, (int)$id_product_attribute, (int)$id_customization, 0, $auto_add_cart_rule);
                } elseif ($new_qty < $minimal_quantity) {
                    return -1;
                } else {
                    Db::getInstance()->execute('
                        UPDATE `'._DB_PREFIX_.'cart_product`
                        SET `quantity` = `quantity` '.$qty.', `date_add` = NOW()
                        WHERE `id_product` = '.(int)$id_product.
                        (!empty($id_product_attribute) ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
                        AND `id_cart` = '.(int)$this->id.(Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery() ? ' AND `id_address_delivery` = '.(int)$id_address_delivery : '').'
                        LIMIT 1'
                    );
                }
            }
            /* Add product to the cart */
            elseif ($operator == 'up') {
                $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
						FROM '._DB_PREFIX_.'product p
						'.Product::sqlStock('p', $id_product_attribute, true, $shop).'
						WHERE p.id_product = '.$id_product;

                $result2 = Db::getInstance()->getRow($sql);

                // Quantity for product pack
                if (Pack::isPack($id_product)) {
                    $result2['quantity'] = Pack::getQuantity($id_product, $id_product_attribute);
                }

                if (!Product::isAvailableWhenOutOfStock((int)$result2['out_of_stock'])) {
                    if ((int)$quantity > $result2['quantity']) {
                        return false;
                    }
                }

                if ((int)$quantity < $minimal_quantity) {
                    return -1;
                }

                $result_add = Db::getInstance()->insert('cart_product', array(
                    'id_product' =>            (int)$id_product,
                    'id_product_attribute' =>    (int)$id_product_attribute,
                    'id_cart' =>                (int)$this->id,
                    'id_address_delivery' =>    (int)$id_address_delivery,
                    'id_shop' =>                $shop->id,
                    'quantity' =>                (int)$quantity,
                    'date_add' =>                date('Y-m-d H:i:s')
                ));

                if (!$result_add) {
                    return false;
                }
            }
        }

        // refresh cache of self::_products
        $this->_products = $this->getProducts(true);
        $this->update(false);
        $context = Context::getContext()->cloneContext();
        $context->cart = $this;
        Cache::clean('getContextualValue_*');
        if ($auto_add_cart_rule) {
            CartRule::autoAddToCart($context);
        }

        if ($product->customizable) {
            return $this->_updateCustomizationQuantity((int)$quantity, (int)$id_customization, (int)$id_product, (int)$id_product_attribute, (int)$id_address_delivery, $operator);
        } else {
            return true;
        }
    }

    /*
    ** Customization management
    */
    protected function _updateCustomizationQuantity($quantity, $id_customization, $id_product, $id_product_attribute, $id_address_delivery, $operator = 'up')
    {
        // Link customization to product combination when it is first added to cart
        if (empty($id_customization)) {
            $customization = $this->getProductCustomization($id_product, null, true);
            foreach ($customization as $field) {
                if ($field['quantity'] == 0) {
                    Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'customization`
					SET `quantity` = '.(int)$quantity.',
						`id_product_attribute` = '.(int)$id_product_attribute.',
						`id_address_delivery` = '.(int)$id_address_delivery.',
						`in_cart` = 1
					WHERE `id_customization` = '.(int)$field['id_customization']);
                }
            }
        }

        /* Deletion */
        if (!empty($id_customization) && (int)$quantity < 1) {
            return $this->_deleteCustomization((int)$id_customization, (int)$id_product, (int)$id_product_attribute);
        }

        /* Quantity update */
        if (!empty($id_customization)) {
            $result = Db::getInstance()->getRow('SELECT `quantity` FROM `'._DB_PREFIX_.'customization` WHERE `id_customization` = '.(int)$id_customization);
            if ($result && Db::getInstance()->NumRows()) {
                if ($operator == 'down' && (int)$result['quantity'] - (int)$quantity < 1) {
                    return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'customization` WHERE `id_customization` = '.(int)$id_customization);
                }

                return Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'customization`
					SET
						`quantity` = `quantity` '.($operator == 'up' ? '+ ' : '- ').(int)$quantity.',
						`id_address_delivery` = '.(int)$id_address_delivery.',
						`in_cart` = 1
					WHERE `id_customization` = '.(int)$id_customization);
            } else {
                Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'customization`
					SET `id_address_delivery` = '.(int)$id_address_delivery.',
					`in_cart` = 1
					WHERE `id_customization` = '.(int)$id_customization);
            }
        }
        // refresh cache of self::_products
        $this->_products = $this->getProducts(true);
        $this->update();
        return true;
    }

    /**
     * Add customization item to database
     *
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $index
     * @param int $type
     * @param string $field
     * @param int $quantity
     * @return bool success
     */
    public function _addCustomization($id_product, $id_product_attribute, $index, $type, $field, $quantity)
    {
        $exising_customization = Db::getInstance()->executeS('
			SELECT cu.`id_customization`, cd.`index`, cd.`value`, cd.`type` FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd
			ON cu.`id_customization` = cd.`id_customization`
			WHERE cu.id_cart = '.(int)$this->id.'
			AND cu.id_product = '.(int)$id_product.'
			AND in_cart = 0'
        );

        if ($exising_customization) {
            // If the customization field is alreay filled, delete it
            foreach ($exising_customization as $customization) {
                if ($customization['type'] == $type && $customization['index'] == $index) {
                    Db::getInstance()->execute('
						DELETE FROM `'._DB_PREFIX_.'customized_data`
						WHERE id_customization = '.(int)$customization['id_customization'].'
						AND type = '.(int)$customization['type'].'
						AND `index` = '.(int)$customization['index']);
                    if ($type == Product::CUSTOMIZE_FILE) {
                        Tools::deleteFile(_PS_UPLOAD_DIR_.$customization['value']); //by webkul use deleteFile insted of unlink
                        Tools::deleteFile(_PS_UPLOAD_DIR_.$customization['value'].'_small'); //by webkul use deleteFile insted of unlink
                    }
                    break;
                }
            }
            $id_customization = $exising_customization[0]['id_customization'];
        } else {
            Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.'customization` (`id_cart`, `id_product`, `id_product_attribute`, `quantity`)
				VALUES ('.(int)$this->id.', '.(int)$id_product.', '.(int)$id_product_attribute.', '.(int)$quantity.')'
            );
            $id_customization = Db::getInstance()->Insert_ID();
        }

        $query = 'INSERT INTO `'._DB_PREFIX_.'customized_data` (`id_customization`, `type`, `index`, `value`)
			VALUES ('.(int)$id_customization.', '.(int)$type.', '.(int)$index.', \''.pSQL($field).'\')';

        if (!Db::getInstance()->execute($query)) {
            return false;
        }
        return true;
    }

    /**
     * Check if order has already been placed
     *
     * @return bool result
     */
    public function orderExists()
    {
        $cache_id = 'Cart::orderExists_'.(int)$this->id;
        if (!Cache::isStored($cache_id)) {
            $result = (bool)Db::getInstance()->getValue('SELECT count(*) FROM `'._DB_PREFIX_.'orders` WHERE `id_cart` = '.(int)$this->id);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }

    /**
     * @deprecated 1.5.0, use Cart->removeCartRule()
     */
    public function deleteDiscount($id_cart_rule)
    {
        Tools::displayAsDeprecated();
        return $this->removeCartRule($id_cart_rule);
    }

    public function removeCartRule($id_cart_rule)
    {
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL);
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING);
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION);
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT);

        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_ALL. '-ids');
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_SHIPPING. '-ids');
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_REDUCTION. '-ids');
        Cache::clean('Cart::getCartRules_'.$this->id.'-'.CartRule::FILTER_ACTION_GIFT. '-ids');

        $result = Db::getInstance()->delete('cart_cart_rule', '`id_cart_rule` = '.(int)$id_cart_rule.' AND `id_cart` = '.(int)$this->id, 1);

        $cart_rule = new CartRule($id_cart_rule, Configuration::get('PS_LANG_DEFAULT'));
        if ((int)$cart_rule->gift_product) {
            $this->updateQty(1, $cart_rule->gift_product, $cart_rule->gift_product_attribute, null, 'down', 0, null, false);
        }

        return $result;
    }

    /**
     * Delete a product from the cart
     *
     * @param int $id_product Product ID
     * @param int $id_product_attribute Attribute ID if needed
     * @param int $id_customization Customization id
     * @return bool result
     */
    public function deleteProduct($id_product, $id_product_attribute = null, $id_customization = null, $id_address_delivery = 0, $auto_add_cart_rule = true)
    {
        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }

        if ((int)$id_customization) {
            $product_total_quantity = (int)Db::getInstance()->getValue(
                'SELECT `quantity`
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_product` = '.(int)$id_product.'
				AND `id_cart` = '.(int)$this->id.'
				AND `id_product_attribute` = '.(int)$id_product_attribute);

            $customization_quantity = (int)Db::getInstance()->getValue('
			SELECT `quantity`
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int)$this->id.'
			AND `id_product` = '.(int)$id_product.'
			AND `id_product_attribute` = '.(int)$id_product_attribute.'
			'.((int)$id_address_delivery ? 'AND `id_address_delivery` = '.(int)$id_address_delivery : ''));

            if (!$this->_deleteCustomization((int)$id_customization, (int)$id_product, (int)$id_product_attribute, (int)$id_address_delivery)) {
                return false;
            }

            // refresh cache of self::_products
            $this->_products = $this->getProducts(true);
            return ($customization_quantity == $product_total_quantity && $this->deleteProduct((int)$id_product, (int)$id_product_attribute, null, (int)$id_address_delivery));
        }

        /* Get customization quantity */
        $result = Db::getInstance()->getRow('
			SELECT SUM(`quantity`) AS \'quantity\'
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int)$this->id.'
			AND `id_product` = '.(int)$id_product.'
			AND `id_product_attribute` = '.(int)$id_product_attribute);

        if ($result === false) {
            return false;
        }

        /* If the product still possesses customization it does not have to be deleted */
        if (Db::getInstance()->NumRows() && (int)$result['quantity']) {
            return Db::getInstance()->execute('
				UPDATE `'._DB_PREFIX_.'cart_product`
				SET `quantity` = '.(int)$result['quantity'].'
				WHERE `id_cart` = '.(int)$this->id.'
				AND `id_product` = '.(int)$id_product.
                ($id_product_attribute != null ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '')
            );
        }

        /* Product deletion */
        $result = Db::getInstance()->execute('
		DELETE FROM `'._DB_PREFIX_.'cart_product`
		WHERE `id_product` = '.(int)$id_product.'
		'.(!is_null($id_product_attribute) ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
		AND `id_cart` = '.(int)$this->id.'
		'.((int)$id_address_delivery ? 'AND `id_address_delivery` = '.(int)$id_address_delivery : ''));

        if ($result) {
            $return = $this->update();
            // refresh cache of self::_products
            $this->_products = $this->getProducts(true);
            CartRule::autoRemoveFromCart();
            if ($auto_add_cart_rule) {
                CartRule::autoAddToCart();
            }

            return $return;
        }

        return false;
    }

    /**
     * Delete a customization from the cart. If customization is a Picture,
     * then the image is also deleted
     *
     * @param int $id_customization
     * @return bool result
     */
    protected function _deleteCustomization($id_customization, $id_product, $id_product_attribute, $id_address_delivery = 0)
    {
        $result = true;
        $customization = Db::getInstance()->getRow('SELECT *
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_customization` = '.(int)$id_customization);

        if ($customization) {
            $cust_data = Db::getInstance()->getRow('SELECT *
				FROM `'._DB_PREFIX_.'customized_data`
				WHERE `id_customization` = '.(int)$id_customization);

            // Delete customization picture if necessary
            if (isset($cust_data['type']) && $cust_data['type'] == 0) {
                $result &= (
                    Tools::deleteFile(_PS_UPLOAD_DIR_.$cust_data['value']) && //by webkul use deleteFile insted of unlink
                    Tools::deleteFile(_PS_UPLOAD_DIR_.$cust_data['value'].'_small') //by webkul use deleteFile insted of unlink
                );
            }

            $result &= Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'customized_data`
				WHERE `id_customization` = '.(int)$id_customization
            );

            if ($result) {
                $result &= Db::getInstance()->execute(
                    'UPDATE `'._DB_PREFIX_.'cart_product`
					SET `quantity` = `quantity` - '.(int)$customization['quantity'].'
					WHERE `id_cart` = '.(int)$this->id.'
					AND `id_product` = '.(int)$id_product.
                    ((int)$id_product_attribute ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
					AND `id_address_delivery` = '.(int)$id_address_delivery
                );
            }

            if (!$result) {
                return false;
            }

            return Db::getInstance()->execute(
                'DELETE FROM `'._DB_PREFIX_.'customization`
				WHERE `id_customization` = '.(int)$id_customization
            );
        }

        return true;
    }

    public static function getTotalCart($id_cart, $use_tax_display = false, $type = Cart::BOTH)
    {
        $cart = new Cart($id_cart);
        if (!Validate::isLoadedObject($cart)) {
            die(Tools::displayError());
        }

        $with_taxes = $use_tax_display ? $cart->_taxCalculationMethod != PS_TAX_EXC : true;
        return Tools::displayPrice($cart->getOrderTotal($with_taxes, $type), Currency::getCurrencyInstance((int)$cart->id_currency), false);
    }


    public static function getOrderTotalUsingTaxCalculationMethod($id_cart)
    {
        return Cart::getTotalCart($id_cart, true);
    }

    /**
    * This function returns the total cart amount
    *
    * Possible values for $type:
    * Cart::ONLY_PRODUCTS
    * Cart::ONLY_DISCOUNTS
    * Cart::BOTH
    * Cart::BOTH_WITHOUT_SHIPPING
    * Cart::ONLY_SHIPPING
    * Cart::ONLY_WRAPPING
    * Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING
    * Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING
    *
    * @param bool $withTaxes With or without taxes
    * @param int $type Total type
    * @param bool $use_cache Allow using cache of the method CartRule::getContextualValue
    * @return float Order total
    */
    public function getOrderTotal($with_taxes = true, $type = Cart::BOTH, $products = null, $id_carrier = null, $use_cache = true)
    {
        // Dependencies
        $address_factory    = Adapter_ServiceLocator::get('Adapter_AddressFactory');
        $price_calculator    = Adapter_ServiceLocator::get('Adapter_ProductPriceCalculator');
        $configuration        = Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');

        $ps_use_ecotax = $configuration->get('PS_USE_ECOTAX');
        $ps_round_type = $configuration->get('PS_ROUND_TYPE');
        $ps_ecotax_tax_rules_group_id = $configuration->get('PS_ECOTAX_TAX_RULES_GROUP_ID');
        $compute_precision = $configuration->get('_PS_PRICE_COMPUTE_PRECISION_');

        if (!$this->id) {
            return 0;
        }

        $type = (int)$type;
        $array_type = array(
            Cart::ONLY_PRODUCTS,
            Cart::ONLY_ROOMS,
            Cart::ONLY_ROOM_SERVICES,
            Cart::ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE,
            Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD,
            Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE,
            Cart::ONLY_CONVENIENCE_FEE,
            Cart::ONLY_STANDALONE_PRODUCTS,
            Cart::ONLY_DISCOUNTS,
            Cart::BOTH,
            Cart::BOTH_WITHOUT_SHIPPING,
            Cart::ONLY_SHIPPING,
            Cart::ONLY_WRAPPING,
            Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING,
            Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING,
            Cart::ADVANCE_PAYMENT,
            Cart::ADVANCE_PAYMENT_ONLY_PRODUCTS,
            Cart::ONLY_PRODUCTS_WITH_DEMANDS,
        );

        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::getContext()->cloneContext();
        $virtual_context->cart = $this;

        if (!in_array($type, $array_type)) {
            die(Tools::displayError());
        }

        $with_shipping = in_array($type, array(Cart::BOTH, Cart::ONLY_SHIPPING));

        // if cart rules are not used
        if ($type == Cart::ONLY_DISCOUNTS && !CartRule::isFeatureActive()) {
            return 0;
        }

        // no shipping cost if is a cart with only virtuals products
        $virtual = $this->isVirtualCart();
        if ($virtual && $type == Cart::ONLY_SHIPPING) {
            return 0;
        }

        if ($virtual && $type == Cart::BOTH) {
            $type = Cart::BOTH_WITHOUT_SHIPPING;
        }

        if ($with_shipping || $type == Cart::ONLY_DISCOUNTS) {
            if (is_null($products) && is_null($id_carrier)) {
                $shipping_fees = $this->getTotalShippingCost(null, (bool)$with_taxes);
            } else {
                $shipping_fees = $this->getPackageShippingCost((int)$id_carrier, (bool)$with_taxes, null, $products);
            }
        } else {
            $shipping_fees = 0;
        }

        if ($type == Cart::ONLY_SHIPPING) {
            return $shipping_fees;
        }

        if ($type == Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING) {
            $type = Cart::ONLY_PRODUCTS;
        }

        $param_product = true;
        if (is_null($products)) {
            $param_product = false;
            $products = $this->getProducts(false, false, null);
        }

        if ($type == Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) {
            foreach ($products as $key => $product) {
                if ($product['is_virtual']) {
                    unset($products[$key]);
                }
            }
            $type = Cart::ONLY_PRODUCTS;
        }

        $order_total = 0;
        if (Tax::excludeTaxeOption()) {
            $with_taxes = false;
        }
        $products_total = array();
        $ecotax_total = 0;
        $totalDemandsPrice = 0;
        $objCartBookingData = new HotelCartBookingData();
        $objServiceProductCartDetail = new ServiceProductCartDetail();
        $objAdvPayment = new HotelAdvancedPayment();
        foreach ($products as $product) {
            // skip products if selection os for only room or only normal products
            if ($product['booking_product']) {
                if ($type == Cart::ONLY_ROOM_SERVICES
                    || $type == Cart::ONLY_STANDALONE_PRODUCTS
                    || $type == Cart::ONLY_CONVENIENCE_FEE
                    || $type == Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD
                    || $type == Cart::ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE
                    || $type == Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE
                ) {
                    continue;
                }
            } else {
                if ($type == Cart::ONLY_ROOMS) {
                    continue;
                }

                if (Product::SELLING_PREFERENCE_WITH_ROOM_TYPE == $product['selling_preference_type']
                    || Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE == $product['selling_preference_type']
                ) {
                    if ($type == Cart::ONLY_STANDALONE_PRODUCTS && Product::SELLING_PREFERENCE_WITH_ROOM_TYPE == $product['selling_preference_type']) {
                        continue;
                    }
                    if ($product['auto_add_to_cart']) {
                        if ($type == Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD) {
                            continue;
                        }
                        if (Product::PRICE_ADDITION_TYPE_WITH_ROOM == $product['price_addition_type']) {
                            if ($type == Cart::ONLY_CONVENIENCE_FEE) {
                                continue;
                            }
                        } else {
                            if ($type == Cart::ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE
                                || $type == Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE
                            ) {
                                continue;
                            }
                        }

                    } else {
                        if ($type == Cart::ONLY_CONVENIENCE_FEE
                            || $type == Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE
                        ) {
                            continue;
                        }
                    }
                } elseif (Product::SELLING_PREFERENCE_STANDALONE == $product['selling_preference_type']
                    || Product::SELLING_PREFERENCE_HOTEL_STANDALONE == $product['selling_preference_type']
                ) {
                    if ($type == Cart::ONLY_ROOM_SERVICES
                        || $type == Cart::ONLY_CONVENIENCE_FEE
                        || $type == Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD
                        || $type == Cart::ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE
                        || $type == Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE
                    ) {
                        continue;
                    }
                }
            }
            // products refer to the cart details
            if ($virtual_context->shop->id != $product['id_shop']) {
                $virtual_context->shop = new Shop((int)$product['id_shop']);
            }

            // hotel address for tax calculation
            $id_address = Cart::getIdAddressForTaxCalculation($product['id_product']);
            if (!$address_factory->addressExists($id_address)) {
                $id_address = null;
            }

            // The $null variable below is not used,
            // but it is necessary to pass it to getProductPrice because
            // it expects a reference.
            $null = null;
            $price = $price_calculator->getProductPrice(
                (int)$product['id_product'],
                $with_taxes,
                (int)$product['id_product_attribute'],
                6,
                null,
                false,
                true,
                $product['cart_quantity'],
                false,
                (int)$this->id_customer ? (int)$this->id_customer : null,
                (int)$this->id,
                $id_address,
                $null,
                $ps_use_ecotax,
                true,
                $virtual_context,
                false
            );

            $address = $address_factory->findOrCreate($id_address, true);

            if ($with_taxes) {
                $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$product['id_product'], $virtual_context);
                $tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();
            } else {
                $id_tax_rules_group = 0;
            }

            if (in_array($ps_round_type, array(Order::ROUND_ITEM, Order::ROUND_LINE))) {
                if (!isset($products_total[$id_tax_rules_group])) {
                    $products_total[$id_tax_rules_group] = 0;
                }
            } elseif (!isset($products_total[$id_tax_rules_group.'_'.$id_address])) {
                $products_total[$id_tax_rules_group.'_'.$id_address] = 0;
            }
            if (!$product['booking_product']) {
                if (Product::SELLING_PREFERENCE_STANDALONE == $product['selling_preference_type']) {
                    if ($servicePorducts = $objServiceProductCartDetail->getServiceProductsInCart(
                        $this->id,
                        [],
                        null,
                        null,
                        null,
                        (int)$product['id_product']
                    )) {
                        foreach ($servicePorducts as $servicePorduct) {
                            if ($with_taxes) {
                                $priceAdd = $servicePorduct['unit_price_tax_incl'];
                            } else {
                                $priceAdd = $servicePorduct['unit_price_tax_excl'];
                            }
                            if ($ps_round_type == Order::ROUND_TOTAL) {
                                $products_total[$id_tax_rules_group.'_'.$id_address] += $priceAdd * (int)$servicePorduct['quantity'];
                            } else {
                                $products_total[$id_tax_rules_group] += $priceAdd * (int)$servicePorduct['quantity'];
                            }
                        }
                    }

                } else if (Product::SELLING_PREFERENCE_HOTEL_STANDALONE == $product['selling_preference_type']
                    || Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE == $product['selling_preference_type']
                ) {
                    if ($type == Cart::ONLY_ROOM_SERVICES
                        || $type == Cart::ONLY_CONVENIENCE_FEE
                        || $type == Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD
                        || $type == Cart::ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE
                        || $type == Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE
                    ) {
                        $servicePorducts = $objServiceProductCartDetail->getServiceProductsInCart(
                            $this->id,
                            [],
                            0,
                            null,
                            null,
                            (int)$product['id_product']
                        );
                    } elseif ($type == Cart::ONLY_STANDALONE_PRODUCTS) {
                        $servicePorducts = $objServiceProductCartDetail->getServiceProductsInCart(
                            $this->id,
                            [],
                            null,
                            0,
                            null,
                            (int)$product['id_product']
                        );
                    } else {
                        $servicePorducts = $objServiceProductCartDetail->getServiceProductsInCart(
                            $this->id,
                            [],
                            isset($product['id_hotel']) ? $product['id_hotel'] : null,
                            null,
                            null,
                            (int)$product['id_product']
                        );
                    }

                    if ($servicePorducts) {
                        foreach ($servicePorducts as $servicePorduct) {
                            if ($with_taxes) {
                                $priceAdd = $servicePorduct['total_price_tax_incl'];
                            } else {
                                $priceAdd = $servicePorduct['total_price_tax_excl'];
                            }

                            if ($ps_round_type == Order::ROUND_TOTAL) {
                                $products_total[$id_tax_rules_group.'_'.$id_address] += $priceAdd;
                            } else {
                                $products_total[$id_tax_rules_group] += $priceAdd;
                            }
                        }
                    }
                } else if (Product::SELLING_PREFERENCE_WITH_ROOM_TYPE == $product['selling_preference_type']) {
                    if ($servicesWithRoom = $objServiceProductCartDetail->getServiceProductsInCart(
                        $this->id,
                        [],
                        0,
                        null,
                        null,
                        (int)$product['id_product'],
                        null,
                        null,
                        0,
                        (int)$product['auto_add_to_cart'],
                        (int)$product['price_addition_type']
                    )) {
                        foreach ($servicesWithRoom as $service) {
                            if ($with_taxes) {
                                $servicePrice = $service['total_price_tax_incl'];
                            } else {
                                $servicePrice = $service['total_price_tax_excl'];
                            }

                            // Rounding as per configurations
                            if ($ps_round_type == Order::ROUND_TOTAL) {
                                $products_total[$id_tax_rules_group.'_'.$id_address] += $servicePrice;
                            } else {
                                $products_total[$id_tax_rules_group] += $servicePrice;
                            }
                        }
                    }
                }
            } else if ($roomTypesByIdProduct = $objCartBookingData->getCartInfoIdCartIdProduct($this->id, $product['id_product'])) {
                // by webkul to calculate rates of the product from hotelreservation syatem tables with feature prices....
                $totalPriceByProduct = 0;
                $priceDisplay = Group::getPriceDisplayMethod(Group::getCurrent()->id);
                foreach ($roomTypesByIdProduct as $key => $cartRoomInfo) {
                    $occupancy = array(
                        array(
                            'adults' => $cartRoomInfo['adults'],
                            'children' => $cartRoomInfo['children'],
                            'child_ages' => json_decode($cartRoomInfo['child_ages'])
                        )
                    );
                    // get the real price of the room type
                    $roomTotalPrice = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice(
                        $cartRoomInfo['id_product'],
                        $cartRoomInfo['date_from'],
                        $cartRoomInfo['date_to'],
                        $occupancy,
                        Group::getCurrent()->id,
                        $cartRoomInfo['id_cart'],
                        $cartRoomInfo['id_guest'],
                        $cartRoomInfo['id_room'],
                        0
                    );
                    if ($with_taxes) {
                        $totalPriceByProduct = $roomTotalPrice['total_price_tax_incl'];
                    } else {
                        $totalPriceByProduct = $roomTotalPrice['total_price_tax_excl'];
                    }

                    // If customer has selected advance payment of the cart
                    if ($type == Cart::ADVANCE_PAYMENT || $type == Cart::ADVANCE_PAYMENT_ONLY_PRODUCTS) {
                        $advProductPrice = $objAdvPayment->getProductMinAdvPaymentAmountByIdCart(
                            $this->id,
                            $cartRoomInfo['id_product'],
                            0,
                            0,
                            $with_taxes
                        );

                        // set advance payment  price only if it is greater than real price
                        if ($advProductPrice < $totalPriceByProduct) {
                            $totalPriceByProduct = $advProductPrice;
                        }
                    }

                    // Rounding as per configurations
                    if ($ps_round_type == Order::ROUND_TOTAL) {
                        $products_total[$id_tax_rules_group.'_'.$id_address] += Tools::processPriceRounding($totalPriceByProduct);
                    } else {
                        $products_total[$id_tax_rules_group] += Tools::processPriceRounding($totalPriceByProduct);
                    }
                }
            }

            // price of extra demands on room type in the cart
            $totalDemandsPrice += $objCartBookingData->getCartExtraDemands($this->id, $product['id_product'], 0, 0, 0, 1, 0, (int)$with_taxes);
        }
        foreach ($products_total as $key => $price) {
            $order_total += $price;
        }

        $order_total_products = $order_total + $totalDemandsPrice;

        if ($type == Cart::ONLY_DISCOUNTS) {
            $order_total = 0;
        }

        // Wrapping Fees
        $wrapping_fees = 0;

        // With PS_ATCP_SHIPWRAP on the gift wrapping cost computation calls getOrderTotal with $type === Cart::ONLY_PRODUCTS, so the flag below prevents an infinite recursion.
        $include_gift_wrapping = (!$configuration->get('PS_ATCP_SHIPWRAP') || $type !== Cart::ONLY_PRODUCTS);

        if ($this->gift && $include_gift_wrapping) {
            $wrapping_fees = Tools::convertPrice(Tools::ps_round($this->getGiftWrappingPrice($with_taxes), $compute_precision), Currency::getCurrencyInstance((int)$this->id_currency));
        }
        if ($type == Cart::ONLY_WRAPPING) {
            return $wrapping_fees;
        }

        // price of extra demands on room type in the cart
        if ($type == Cart::BOTH
            || $type == Cart::BOTH_WITHOUT_SHIPPING
            || $type == Cart::ADVANCE_PAYMENT
            || $type == Cart::ONLY_PRODUCTS_WITH_DEMANDS
        ) {
            $order_total += $totalDemandsPrice;
        }

        $order_total_discount = 0;
        $order_shipping_discount = 0;
        $advance_payment_products_discount = 0;
        if (!in_array($type, array(
            Cart::ONLY_SHIPPING,
            Cart::ONLY_PRODUCTS,
            Cart::ONLY_ROOMS,
            Cart::ADVANCE_PAYMENT_ONLY_PRODUCTS,
            Cart::ONLY_ROOM_SERVICES,
            Cart::ONLY_STANDALONE_PRODUCTS,
            Cart::ONLY_CONVENIENCE_FEE,
            Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD,
            Cart::ONLY_ROOM_SERVICES_WITHOUT_CONVENIENCE_FEE,
            Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE,
            Cart::ONLY_PRODUCTS_WITH_DEMANDS)
            ) && CartRule::isFeatureActive()
        ) {
            // First, retrieve the cart rules associated to this "getOrderTotal"
            if ($with_shipping || $type == Cart::ONLY_DISCOUNTS) {
                $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_ALL);
            } else {
                $cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);
                // Cart Rules array are merged manually in order to avoid doubles
                foreach ($this->getCartRules(CartRule::FILTER_ACTION_GIFT) as $tmp_cart_rule) {
                    $flag = false;
                    foreach ($cart_rules as $cart_rule) {
                        if ($tmp_cart_rule['id_cart_rule'] == $cart_rule['id_cart_rule']) {
                            $flag = true;
                        }
                    }
                    if (!$flag) {
                        $cart_rules[] = $tmp_cart_rule;
                    }
                }
            }

            $id_address_delivery = 0;
            if (isset($products[0])) {
                $id_address_delivery = (is_null($products) ? $this->id_address_delivery : $products[0]['id_address_delivery']);
            }
            $package = array('id_carrier' => $id_carrier, 'id_address' => $id_address_delivery, 'products' => $products);

            // Then, calculate the contextual value for each one
            $flag = false;
            foreach ($cart_rules as $cart_rule) {
                // If the cart rule offers free shipping, add the shipping cost
                if (($with_shipping || $type == Cart::ONLY_DISCOUNTS) && $cart_rule['obj']->free_shipping && !$flag) {
                    $order_shipping_discount = (float)Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_SHIPPING, ($param_product ? $package : null), $use_cache), $compute_precision);
                    $flag = true;
                }

                // If the cart rule is a free gift, then add the free gift value only if the gift is in this package
                if ((int)$cart_rule['obj']->gift_product) {
                    $in_order = false;
                    if (is_null($products)) {
                        $in_order = true;
                    } else {
                        foreach ($products as $product) {
                            if ($cart_rule['obj']->gift_product == $product['id_product'] && $cart_rule['obj']->gift_product_attribute == $product['id_product_attribute']) {
                                $in_order = true;
                            }
                        }
                    }

                    if ($in_order) {
                        $order_total_discount += $cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_GIFT, $package, $use_cache);
                    }
                }

                // If the cart rule offers a reduction, the amount is prorated (with the products in the package)
                if ($cart_rule['obj']->reduction_percent > 0 || $cart_rule['obj']->reduction_amount > 0) {
                    $order_total_discount += Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_REDUCTION, $package, $use_cache), $compute_precision);

                    if ($type == Cart::ADVANCE_PAYMENT) {
                        $advance_payment_products_discount += Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_REDUCTION, $package, $use_cache, true), $compute_precision);
                    }
                }
            }
            if ($type != Cart::ADVANCE_PAYMENT) {
                $order_total_discount = min(Tools::ps_round($order_total_discount, 2), (float)$order_total_products) + (float)$order_shipping_discount;
            }
            if ($type == Cart::ADVANCE_PAYMENT) {
                // get order total without discount
                $total_without_discount = $this->getOrderTotal() + $order_total_discount;
                // to find due amount substract advance payment without discuount from order total without discount
                // Due Amount = order total - advance payment (without discount)
                $due_amount = $total_without_discount - $order_total;
                // if discount for advance payment products is greater than due amount then only decrease the price of advance payment amount
                // otherwise discount will be applied on due amount not advance payment amount
                if ($advance_payment_products_discount > $due_amount) {
                    $order_total -= ($advance_payment_products_discount - $due_amount);
                }
                $order_total -= $order_total_discount - $advance_payment_products_discount;
            } else {
                $order_total -= $order_total_discount;
            }
        }

        if ($type == Cart::BOTH || $type == Cart::ADVANCE_PAYMENT) {
            $order_total += $shipping_fees + $wrapping_fees;
        }

        if ($order_total < 0 && $type != Cart::ONLY_DISCOUNTS) {
            return 0;
        }

        if ($type == Cart::ONLY_DISCOUNTS) {
            return $order_total_discount;
        }

        return Tools::ps_round((float)$order_total, $compute_precision);
    }

    /**
    * Get the gift wrapping price
    * @param bool $with_taxes With or without taxes
    * @return float wrapping price
    */
    public function getGiftWrappingPrice($with_taxes = true, $id_address = null)
    {
        static $address = array();

        $wrapping_fees = (float)Configuration::get('PS_GIFT_WRAPPING_PRICE');

        if ($wrapping_fees <= 0) {
            return $wrapping_fees;
        }

        if ($with_taxes) {
            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                // With PS_ATCP_SHIPWRAP, wrapping fee is by default tax included
                // so nothing to do here.
            } else {
                if (!isset($address[$this->id])) {
                    if ($id_address === null) {
                        $id_address = (int)$this->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                    }
                    try {
                        $address[$this->id] = Address::initialize($id_address);
                    } catch (Exception $e) {
                        $address[$this->id] = new Address();
                        $address[$this->id]->id_country = Configuration::get('PS_COUNTRY_DEFAULT');
                    }
                }

                $tax_manager = TaxManagerFactory::getManager($address[$this->id], (int)Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'));
                $tax_calculator = $tax_manager->getTaxCalculator();
                $wrapping_fees = $tax_calculator->addTaxes($wrapping_fees);
            }
        } elseif (Configuration::get('PS_ATCP_SHIPWRAP')) {
            // With PS_ATCP_SHIPWRAP, wrapping fee is by default tax included, so we convert it
            // when asked for the pre tax price.
            $wrapping_fees = Tools::ps_round(
                $wrapping_fees / (1 + $this->getAverageProductsTaxRate()),
                _PS_PRICE_COMPUTE_PRECISION_
            );
        }

        return $wrapping_fees;
    }

    /**
     * Get the number of packages
     *
     * @return int number of packages
     */
    public function getNbOfPackages()
    {
        static $nb_packages = array();

        if (!isset($nb_packages[$this->id])) {
            $nb_packages[$this->id] = 0;
            foreach ($this->getPackageList() as $by_address) {
                $nb_packages[$this->id] += count($by_address);
            }
        }

        return $nb_packages[$this->id];
    }

    /**
     * Get products grouped by package and by addresses to be sent individualy (one package = one shipping cost).
     *
     * @return array array(
     *                   0 => array( // First address
     *                       0 => array(  // First package
     *                           'product_list' => array(...),
     *                           'carrier_list' => array(...),
     *                           'id_warehouse' => array(...),
     *                       ),
     *                   ),
     *               );
     * @todo Add avaibility check
     */
    public function getPackageList($flush = false)
    {
        static $cache = array();
        $cache_key = (int)$this->id.'_'.(int)$this->id_address_delivery;
        if (isset($cache[$cache_key]) && $cache[$cache_key] !== false && !$flush) {
            return $cache[$cache_key];
        }

        $product_list = $this->getProducts($flush, false, null);
        // Step 1 : Get product informations (warehouse_list and carrier_list), count warehouse
        // Determine the best warehouse to determine the packages
        // For that we count the number of time we can use a warehouse for a specific delivery address
        $warehouse_count_by_address = array();

        $stock_management_active = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');

        foreach ($product_list as &$product) {
            if ((int)$product['id_address_delivery'] == 0) {
                $product['id_address_delivery'] = (int)$this->id_address_delivery;
            }

            if (!isset($warehouse_count_by_address[$product['id_address_delivery']])) {
                $warehouse_count_by_address[$product['id_address_delivery']] = array();
            }

            $product['warehouse_list'] = array();

            if ($stock_management_active &&
                (int)$product['advanced_stock_management'] == 1) {
                $warehouse_list = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute'], $this->id_shop);
                if (count($warehouse_list) == 0) {
                    $warehouse_list = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute']);
                }
                // Does the product is in stock ?
                // If yes, get only warehouse where the product is in stock

                $warehouse_in_stock = array();
                $manager = StockManagerFactory::getManager();

                foreach ($warehouse_list as $key => $warehouse) {
                    $product_real_quantities = $manager->getProductRealQuantities(
                        $product['id_product'],
                        $product['id_product_attribute'],
                        array($warehouse['id_warehouse']),
                        true
                    );

                    if ($product_real_quantities > 0 || Pack::isPack((int)$product['id_product'])) {
                        $warehouse_in_stock[] = $warehouse;
                    }
                }

                if (!empty($warehouse_in_stock)) {
                    $warehouse_list = $warehouse_in_stock;
                    $product['in_stock'] = true;
                } else {
                    $product['in_stock'] = false;
                }
            } else {
                //simulate default warehouse
                $warehouse_list = array(0 => array('id_warehouse' => 0));
                $product['in_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']) > 0;
            }

            foreach ($warehouse_list as $warehouse) {
                $product['warehouse_list'][$warehouse['id_warehouse']] = $warehouse['id_warehouse'];
                if (!isset($warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']])) {
                    $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']] = 0;
                }

                $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']]++;
            }
        }
        unset($product);

        arsort($warehouse_count_by_address);

        // Step 2 : Group product by warehouse
        $grouped_by_warehouse = array();

        foreach ($product_list as &$product) {
            if (!isset($grouped_by_warehouse[$product['id_address_delivery']])) {
                $grouped_by_warehouse[$product['id_address_delivery']] = array(
                    'in_stock' => array(),
                    'out_of_stock' => array(),
                );
            }

            $product['carrier_list'] = array();
            $id_warehouse = 0;
            foreach ($warehouse_count_by_address[$product['id_address_delivery']] as $id_war => $val) {
                if (array_key_exists((int)$id_war, $product['warehouse_list'])) {
                    $product['carrier_list'] = Tools::array_replace($product['carrier_list'], Carrier::getAvailableCarrierList(new Product($product['id_product']), $id_war, $product['id_address_delivery'], null, $this));
                    if (!$id_warehouse) {
                        $id_warehouse = (int)$id_war;
                    }
                }
            }

            if (!isset($grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse])) {
                $grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse] = array();
                $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse] = array();
            }

            if (!$this->allow_seperated_package) {
                $key = 'in_stock';
            } else {
                $key = $product['in_stock'] ? 'in_stock' : 'out_of_stock';
                $product_quantity_in_stock = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']);
                if ($product['in_stock'] && $product['cart_quantity'] > $product_quantity_in_stock) {
                    $out_stock_part = $product['cart_quantity'] - $product_quantity_in_stock;
                    $product_bis = $product;
                    $product_bis['cart_quantity'] = $out_stock_part;
                    $product_bis['in_stock'] = 0;
                    $product['cart_quantity'] -= $out_stock_part;
                    $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse][] = $product_bis;
                }
            }

            if (empty($product['carrier_list'])) {
                $product['carrier_list'] = array(0 => 0);
            }

            $grouped_by_warehouse[$product['id_address_delivery']][$key][$id_warehouse][] = $product;
        }
        unset($product);

        // Step 3 : grouped product from grouped_by_warehouse by available carriers
        $grouped_by_carriers = array();
        foreach ($grouped_by_warehouse as $id_address_delivery => $products_in_stock_list) {
            if (!isset($grouped_by_carriers[$id_address_delivery])) {
                $grouped_by_carriers[$id_address_delivery] = array(
                    'in_stock' => array(),
                    'out_of_stock' => array(),
                );
            }
            foreach ($products_in_stock_list as $key => $warehouse_list) {
                if (!isset($grouped_by_carriers[$id_address_delivery][$key])) {
                    $grouped_by_carriers[$id_address_delivery][$key] = array();
                }
                foreach ($warehouse_list as $id_warehouse => $product_list) {
                    if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse])) {
                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse] = array();
                    }
                    foreach ($product_list as $product) {
                        $package_carriers_key = implode(',', $product['carrier_list']);

                        if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key])) {
                            $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key] = array(
                                'product_list' => array(),
                                'carrier_list' => $product['carrier_list'],
                                'warehouse_list' => $product['warehouse_list']
                            );
                        }

                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key]['product_list'][] = $product;
                    }
                }
            }
        }

        $package_list = array();
        // Step 4 : merge product from grouped_by_carriers into $package to minimize the number of package
        foreach ($grouped_by_carriers as $id_address_delivery => $products_in_stock_list) {
            if (!isset($package_list[$id_address_delivery])) {
                $package_list[$id_address_delivery] = array(
                    'in_stock' => array(),
                    'out_of_stock' => array(),
                );
            }

            foreach ($products_in_stock_list as $key => $warehouse_list) {
                if (!isset($package_list[$id_address_delivery][$key])) {
                    $package_list[$id_address_delivery][$key] = array();
                }
                // Count occurance of each carriers to minimize the number of packages
                $carrier_count = array();
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    foreach ($products_grouped_by_carriers as $data) {
                        foreach ($data['carrier_list'] as $id_carrier) {
                            if (!isset($carrier_count[$id_carrier])) {
                                $carrier_count[$id_carrier] = 0;
                            }
                            $carrier_count[$id_carrier]++;
                        }
                    }
                }
                arsort($carrier_count);
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    if (!isset($package_list[$id_address_delivery][$key][$id_warehouse])) {
                        $package_list[$id_address_delivery][$key][$id_warehouse] = array();
                    }
                    foreach ($products_grouped_by_carriers as $data) {
                        foreach ($carrier_count as $id_carrier => $rate) {
                            if (array_key_exists($id_carrier, $data['carrier_list'])) {
                                if (!isset($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier])) {
                                    $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier] = array(
                                        'carrier_list' => $data['carrier_list'],
                                        'warehouse_list' => $data['warehouse_list'],
                                        'product_list' => array(),
                                    );
                                }
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'] =
                                    array_intersect($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'], $data['carrier_list']);
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'] =
                                    array_merge($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'], $data['product_list']);

                                break;
                            }
                        }
                    }
                }
            }
        }

        // Step 5 : Reduce depth of $package_list
        $final_package_list = array();
        foreach ($package_list as $id_address_delivery => $products_in_stock_list) {
            if (!isset($final_package_list[$id_address_delivery])) {
                $final_package_list[$id_address_delivery] = array();
            }

            foreach ($products_in_stock_list as $key => $warehouse_list) {
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    foreach ($products_grouped_by_carriers as $data) {
                        $final_package_list[$id_address_delivery][] = array(
                            'product_list' => $data['product_list'],
                            'carrier_list' => $data['carrier_list'],
                            'warehouse_list' => $data['warehouse_list'],
                            'id_warehouse' => $id_warehouse,
                        );
                    }
                }
            }
        }

        // Step 6 : Break $package_list hotel wise
        $orderPackage = array();
        $standaloneProduct = array();
        $objRoomType = new HotelRoomType();
        $objServiceProductCartDetail = new ServiceProductCartDetail();
        foreach ($final_package_list as $id_address => $packages) {
            foreach ($packages as $id_package => $package) {
                foreach ($package['product_list'] as $product) {
                    if ($product['booking_product']) {
                        // Changing because when we applied advance price rule it show original product price
                        $product['price_wt'] = $product['total_wt'] / $product['quantity'];
                        $product['price'] = $product['total'] / $product['quantity'];

                        $productInfo = $objRoomType->getRoomTypeInfoByIdProduct($product['id_product']);
                        $idHotel = $productInfo['id_hotel'] ? $productInfo['id_hotel'] : 0;

                        $orderPackage[$id_address][$idHotel]['product_list'][] = $product;
                        $orderPackage[$id_address][$idHotel]['id_hotel'] = $idHotel;
                        if (!isset($orderPackage[$id_address][$idHotel]['id_hotel'])) {
                            $orderPackage[$id_address][$idHotel]['id_hotel'] = $productInfo['id_hotel'];
                        }
                        if (!isset($orderPackage[$id_address][$idHotel]['carrier_list'])) {
                            $orderPackage[$id_address][$idHotel]['carrier_list'] = $product['carrier_list'];
                        } else {
                            $orderPackage[$id_address][$idHotel]['carrier_list'] = array_intersect($orderPackage[$id_address][$idHotel]['carrier_list'], $product['carrier_list']);
                        }
                        if (!isset($orderPackage[$id_address][$idHotel]['warehouse_list'])) {
                            $orderPackage[$id_address][$idHotel]['warehouse_list'] = $package['warehouse_list'];
                        }
                        if (!isset($orderPackage[$id_address][$idHotel]['id_warehouse'])) {
                            $orderPackage[$id_address][$idHotel]['id_warehouse'] = $package['id_warehouse'];
                        }
                        if (isset($package['id_carrier'])) {
                            if (!isset($orderPackage[$id_address][$idHotel]['id_carrier'])) {
                                $orderPackage[$id_address][$idHotel]['id_carrier'] = $package['id_carrier'];
                            }
                        }
                    } else {
                        if (Product::SELLING_PREFERENCE_WITH_ROOM_TYPE == $product['selling_preference_type']
                            || Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE == $product['selling_preference_type']
                        ) {
                            // get standard products which are booked for room types
                            // send id_hotel = 0 for standard products with room types
                            if ($selectedServiceProducts = $objServiceProductCartDetail->getServiceProductsInCart(
                                $this->id,
                                [],
                                0,
                                null,
                                null,
                                $product['id_product']
                            )) {
                                $array = array();
                                foreach($selectedServiceProducts as $selectedProduct) {
                                    if (isset($array[$selectedProduct['id_room_type_hotel']])) {
                                        $array[$selectedProduct['id_room_type_hotel']]['total_price_tax_excl'] += $selectedProduct['total_price_tax_excl'];
                                        $array[$selectedProduct['id_room_type_hotel']]['total_price_tax_incl'] += $selectedProduct['total_price_tax_incl'];
                                    } else {
                                        $array[$selectedProduct['id_room_type_hotel']] = array(
                                            'quantity' => 0,
                                            'id_hotel' => $selectedProduct['id_room_type_hotel'],
                                            'total_price_tax_excl' => $selectedProduct['total_price_tax_excl'],
                                            'total_price_tax_incl' => $selectedProduct['total_price_tax_incl'],
                                            'id_room_type' => $selectedProduct['id_room_type'],
                                            'id_hotel_cart_booking' => $selectedProduct['id_hotel_cart_booking'],
                                        );
                                    }
                                    if (Product::PRICE_CALCULATION_METHOD_PER_DAY == $selectedProduct['price_calculation_method']) {
                                        $numDays = HotelHelper::getNumberOfDays(
                                            $selectedProduct['date_from'],
                                            $selectedProduct['date_to']
                                        );
                                        $array[$selectedProduct['id_room_type_hotel']]['quantity'] += ($selectedProduct['quantity'] * $numDays);
                                    } else {
                                        $array[$selectedProduct['id_room_type_hotel']]['quantity'] += $selectedProduct['quantity'];

                                    }
                                }

                                if ($array) {
                                    foreach($array as $selectedProduct) {
                                        $product['cart_quantity'] = $selectedProduct['quantity'];
                                        $product['total'] = $selectedProduct['total_price_tax_excl'];
                                        $product['total_wt'] = $selectedProduct['total_price_tax_incl'];
                                        $product['price_wt'] = $selectedProduct['total_price_tax_incl'] / $selectedProduct['quantity'];
                                        $product['price'] = $selectedProduct['total_price_tax_excl'] / $selectedProduct['quantity'];
                                        $product['id_hotel'] = $selectedProduct['id_hotel'];
                                        $product['id_room_type'] = $selectedProduct['id_room_type'];
                                        $product['id_hotel_cart_booking'] = $selectedProduct['id_hotel_cart_booking'];
                                        $productinfo = $product;
                                        $productinfo['selling_preference_type'] = Product::SELLING_PREFERENCE_WITH_ROOM_TYPE;
                                        $orderPackage[$id_address][$selectedProduct['id_hotel']]['product_list'][] = $productinfo;
                                        if (!isset($orderPackage[$id_address][$selectedProduct['id_hotel']]['id_hotel'])) {
                                            $orderPackage[$id_address][$selectedProduct['id_hotel']]['id_hotel'] = $selectedProduct['id_hotel'];
                                        }
                                        if (!isset($orderPackage[$id_address][$selectedProduct['id_hotel']]['carrier_list'])) {
                                            $orderPackage[$id_address][$selectedProduct['id_hotel']]['carrier_list'] = $product['carrier_list'];
                                        } else {
                                            $orderPackage[$id_address][$selectedProduct['id_hotel']]['carrier_list'] = array_intersect($orderPackage[$id_address][$selectedProduct['id_hotel']]['carrier_list'], $product['carrier_list']);
                                        }
                                        if (!isset($orderPackage[$id_address][$selectedProduct['id_hotel']]['warehouse_list'])) {
                                            $orderPackage[$id_address][$selectedProduct['id_hotel']]['warehouse_list'] = $package['warehouse_list'];
                                        }
                                        if (!isset($orderPackage[$id_address][$selectedProduct['id_hotel']]['id_warehouse'])) {
                                            $orderPackage[$id_address][$selectedProduct['id_hotel']]['id_warehouse'] = $package['id_warehouse'];
                                        }
                                        if (isset($package['id_carrier'])) {
                                            if (!isset($orderPackage[$id_address][$selectedProduct['id_hotel']]['id_carrier'])) {
                                                $orderPackage[$id_address][$selectedProduct['id_hotel']]['id_carrier'] = $package['id_carrier'];
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (Product::SELLING_PREFERENCE_STANDALONE == $product['selling_preference_type']) {
                            if (!isset($standaloneProduct[$id_address])) {
                                $standaloneProduct[$id_address] = array(
                                    'product_list' => array(),
                                    'warehouse_list' => $package['warehouse_list'],
                                    'carrier_list' => $product['carrier_list'],
                                    'id_warehouse' => $package['id_warehouse'],
                                    'id_carrier' => isset($package['id_carrier']) ? $package['id_carrier'] : 0
                                );
                            }
                            $standaloneProduct[$id_address]['product_list'][] = $product;
                        }

                        if (Product::SELLING_PREFERENCE_HOTEL_STANDALONE == $product['selling_preference_type']
                            || Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE == $product['selling_preference_type']
                        ) {
                            if ($selectedServiceProducts = $objServiceProductCartDetail->getServiceProductsInCart(
                                $this->id,
                                [],
                                null,
                                0,
                                null,
                                (int)$product['id_product']
                            )) {
                                $array = array();
                                foreach($selectedServiceProducts as $hotelProduct) {
                                    if (isset($array[$hotelProduct['id_hotel']])) {
                                        $array[$hotelProduct['id_hotel']]['total_price_tax_excl'] += $hotelProduct['total_price_tax_excl'];
                                        $array[$hotelProduct['id_hotel']]['total_price_tax_incl'] += $hotelProduct['total_price_tax_incl'];
                                        $array[$hotelProduct['id_hotel']]['quantity'] += $hotelProduct['quantity'];
                                    } else {
                                        $array[$hotelProduct['id_hotel']] = array(
                                            'quantity' => $hotelProduct['quantity'],
                                            'id_hotel' => $hotelProduct['id_hotel'],
                                            'total_price_tax_excl' => $hotelProduct['total_price_tax_excl'],
                                            'total_price_tax_incl' => $hotelProduct['total_price_tax_incl'],
                                            'id_room_type' => $hotelProduct['id_room_type'],
                                            'id_hotel_cart_booking' => $hotelProduct['id_hotel_cart_booking'],
                                        );
                                    }
                                }

                                if ($array) {
                                    foreach($array as $hotelProduct) {
                                        $serviceProduct = $product;
                                        $serviceProduct['cart_quantity'] = $hotelProduct['quantity'];
                                        $serviceProduct['id_hotel'] = $hotelProduct['id_hotel'];
                                        $serviceProduct['total'] = $hotelProduct['total_price_tax_excl'];
                                        $serviceProduct['total_wt'] = $hotelProduct['total_price_tax_incl'];
                                        $serviceProduct['price'] = $hotelProduct['total_price_tax_excl'] / $hotelProduct['quantity'];
                                        $serviceProduct['price_wt'] = $hotelProduct['total_price_tax_incl'] / $hotelProduct['quantity'];
                                        $serviceProduct['id_room_type'] = $hotelProduct['id_room_type'];
                                        $serviceProduct['id_hotel_cart_booking'] = $hotelProduct['id_hotel_cart_booking'];
                                        // if (!empty($hotelProducts['products'])) {
                                        //     foreach($hotelProducts['products'] as $hotelProduct) {
                                        $productinfo = $serviceProduct;
                                        $productinfo['selling_preference_type'] = Product::SELLING_PREFERENCE_HOTEL_STANDALONE;
                                        $orderPackage[$id_address][$hotelProduct['id_hotel']]['product_list'][] = $productinfo;
                                        if (!isset($orderPackage[$id_address][$hotelProduct['id_hotel']]['id_hotel'])) {
                                            $orderPackage[$id_address][$hotelProduct['id_hotel']]['id_hotel'] = $hotelProduct['id_hotel'];
                                        }
                                        if (!isset($orderPackage[$id_address][$hotelProduct['id_hotel']]['carrier_list'])) {
                                            $orderPackage[$id_address][$hotelProduct['id_hotel']]['carrier_list'] = $product['carrier_list'];
                                        } else {
                                            $orderPackage[$id_address][$hotelProduct['id_hotel']]['carrier_list'] = array_intersect($orderPackage[$id_address][$hotelProduct['id_hotel']]['carrier_list'], $product['carrier_list']);
                                        }
                                        if (!isset($orderPackage[$id_address][$hotelProduct['id_hotel']]['warehouse_list'])) {
                                            $orderPackage[$id_address][$hotelProduct['id_hotel']]['warehouse_list'] = $package['warehouse_list'];
                                        }
                                        if (!isset($orderPackage[$id_address][$hotelProduct['id_hotel']]['id_warehouse'])) {
                                            $orderPackage[$id_address][$hotelProduct['id_hotel']]['id_warehouse'] = $package['id_warehouse'];
                                        }
                                        if (isset($package['id_carrier'])) {
                                            if (!isset($orderPackage[$id_address][$hotelProduct['id_hotel']]['id_carrier'])) {
                                                $orderPackage[$id_address][$hotelProduct['id_hotel']]['id_carrier'] = $package['id_carrier'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $hotelWisePackageList = array();
        $numHotels = 0;
        if (!empty($standaloneProduct)) {
            foreach ($standaloneProduct as $id_address =>  $packagesByAddress) {
                $hotelWisePackageList[$id_address][] = $packagesByAddress;
            }
        }
        foreach ($orderPackage as $id_address => $packageByAddress) {
            $numHotels += count($packageByAddress);
            foreach ($packageByAddress as $id_package => $package) {
                $hotelWisePackageList[$id_address][] = $package;
            }
        }

        $final_package_list = $hotelWisePackageList;
        // END $package_list hotel wise
        $cache[$cache_key] = $final_package_list;
        return $final_package_list;
    }

    public function getPackageIdWarehouse($package, $id_carrier = null)
    {
        if ($id_carrier === null) {
            if (isset($package['id_carrier'])) {
                $id_carrier = (int)$package['id_carrier'];
            }
        }

        if ($id_carrier == null) {
            return $package['id_warehouse'];
        }

        foreach ($package['warehouse_list'] as $id_warehouse) {
            $warehouse = new Warehouse((int)$id_warehouse);
            $available_warehouse_carriers = $warehouse->getCarriers();
            if (in_array($id_carrier, $available_warehouse_carriers)) {
                return (int)$id_warehouse;
            }
        }
        return 0;
    }

    /**
     * Get all deliveries options available for the current cart
     * @param Country $default_country
     * @param bool $flush Force flushing cache
     *
     * @return array array(
     *                   0 => array( // First address
     *                       '12,' => array(  // First delivery option available for this address
     *                           carrier_list => array(
     *                               12 => array( // First carrier for this option
     *                                   'instance' => Carrier Object,
     *                                   'logo' => <url to the carriers logo>,
     *                                   'price_with_tax' => 12.4,
     *                                   'price_without_tax' => 12.4,
     *                                   'package_list' => array(
     *                                       1,
     *                                       3,
     *                                   ),
     *                               ),
     *                           ),
     *                           is_best_grade => true, // Does this option have the biggest grade (quick shipping) for this shipping address
     *                           is_best_price => true, // Does this option have the lower price for this shipping address
     *                           unique_carrier => true, // Does this option use a unique carrier
     *                           total_price_with_tax => 12.5,
     *                           total_price_without_tax => 12.5,
     *                           position => 5, // Average of the carrier position
     *                       ),
     *                   ),
     *               );
     *               If there are no carriers available for an address, return an empty  array
     */
    public function getDeliveryOptionList(?Country $default_country = null, $flush = false)
    {
        static $cache = array();
        if (isset($cache[$this->id]) && !$flush) {
            return $cache[$this->id];
        }

        $delivery_option_list = array();
        $carriers_price = array();
        $carrier_collection = array();
        $package_list = $this->getPackageList($flush);

        // Foreach addresses
        foreach ($package_list as $id_address => $packages) {
            // Initialize vars
            $delivery_option_list[$id_address] = array();
            $carriers_price[$id_address] = array();
            $common_carriers = null;
            $best_price_carriers = array();
            $best_grade_carriers = array();
            $carriers_instance = array();

            // Get country
            if ($id_address) {
                $address = new Address($id_address);
                $country = new Country($address->id_country);
            } else {
                $country = $default_country;
            }

            // Foreach packages, get the carriers with best price, best position and best grade
            foreach ($packages as $id_package => $package) {
                // No carriers available
                if (count($packages) == 1 && count($package['carrier_list']) == 1 && current($package['carrier_list']) == 0) {
                    $cache[$this->id] = array();
                    return $cache[$this->id];
                }

                $carriers_price[$id_address][$id_package] = array();

                // Get all common carriers for each packages to the same address
                if (is_null($common_carriers)) {
                    $common_carriers = $package['carrier_list'];
                } else {
                    $common_carriers = array_intersect($common_carriers, $package['carrier_list']);
                }

                $best_price = null;
                $best_price_carrier = null;
                $best_grade = null;
                $best_grade_carrier = null;

                // Foreach carriers of the package, calculate his price, check if it the best price, position and grade
                foreach ($package['carrier_list'] as $id_carrier) {
                    if (!isset($carriers_instance[$id_carrier])) {
                        $carriers_instance[$id_carrier] = new Carrier($id_carrier);
                    }

                    $price_with_tax = $this->getPackageShippingCost((int)$id_carrier, true, $country, $package['product_list']);
                    $price_without_tax = $this->getPackageShippingCost((int)$id_carrier, false, $country, $package['product_list']);
                    if (is_null($best_price) || $price_with_tax < $best_price) {
                        $best_price = $price_with_tax;
                        $best_price_carrier = $id_carrier;
                    }
                    $carriers_price[$id_address][$id_package][$id_carrier] = array(
                        'without_tax' => $price_without_tax,
                        'with_tax' => $price_with_tax);

                    $grade = $carriers_instance[$id_carrier]->grade;
                    if (is_null($best_grade) || $grade > $best_grade) {
                        $best_grade = $grade;
                        $best_grade_carrier = $id_carrier;
                    }
                }

                $best_price_carriers[$id_package] = $best_price_carrier;
                $best_grade_carriers[$id_package] = $best_grade_carrier;
            }

            // Reset $best_price_carrier, it's now an array
            $best_price_carrier = array();
            $key = '';

            // Get the delivery option with the lower price
            foreach ($best_price_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier.',';
                if (!isset($best_price_carrier[$id_carrier])) {
                    $best_price_carrier[$id_carrier] = array(
                        'price_with_tax' => 0,
                        'price_without_tax' => 0,
                        'package_list' => array(),
                        'product_list' => array(),
                    );
                }
                $best_price_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_price_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_price_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_price_carrier[$id_carrier]['product_list'] = array_merge($best_price_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_price_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
                $real_best_price = !isset($real_best_price) || $real_best_price > $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] ?
                $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] : $real_best_price;
                $real_best_price_wt = !isset($real_best_price_wt) || $real_best_price_wt > $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] ?
                $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] : $real_best_price_wt;
            }

            // Add the delivery option with best price as best price
            $delivery_option_list[$id_address][$key] = array(
                'carrier_list' => $best_price_carrier,
                'is_best_price' => true,
                'is_best_grade' => false,
                'unique_carrier' => (count($best_price_carrier) <= 1)
            );

            // Reset $best_grade_carrier, it's now an array
            $best_grade_carrier = array();
            $key = '';

            // Get the delivery option with the best grade
            foreach ($best_grade_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier.',';
                if (!isset($best_grade_carrier[$id_carrier])) {
                    $best_grade_carrier[$id_carrier] = array(
                        'price_with_tax' => 0,
                        'price_without_tax' => 0,
                        'package_list' => array(),
                        'product_list' => array(),
                    );
                }
                $best_grade_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_grade_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_grade_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_grade_carrier[$id_carrier]['product_list'] = array_merge($best_grade_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_grade_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
            }

            // Add the delivery option with best grade as best grade
            if (!isset($delivery_option_list[$id_address][$key])) {
                $delivery_option_list[$id_address][$key] = array(
                    'carrier_list' => $best_grade_carrier,
                    'is_best_price' => false,
                    'unique_carrier' => (count($best_grade_carrier) <= 1)
                );
            }
            $delivery_option_list[$id_address][$key]['is_best_grade'] = true;

            // Get all delivery options with a unique carrier
            foreach ($common_carriers as $id_carrier) {
                $key = '';
                $package_list = array();
                $product_list = array();
                $price_with_tax = 0;
                $price_without_tax = 0;

                foreach ($packages as $id_package => $package) {
                    $key .= $id_carrier.',';
                    $price_with_tax += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                    $price_without_tax += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                    $package_list[] = $id_package;
                    $product_list = array_merge($product_list, $package['product_list']);
                }

                if (!isset($delivery_option_list[$id_address][$key])) {
                    $delivery_option_list[$id_address][$key] = array(
                        'is_best_price' => false,
                        'is_best_grade' => false,
                        'unique_carrier' => true,
                        'carrier_list' => array(
                            $id_carrier => array(
                                'price_with_tax' => $price_with_tax,
                                'price_without_tax' => $price_without_tax,
                                'instance' => $carriers_instance[$id_carrier],
                                'package_list' => $package_list,
                                'product_list' => $product_list,
                            )
                        )
                    );
                } else {
                    $delivery_option_list[$id_address][$key]['unique_carrier'] = (count($delivery_option_list[$id_address][$key]['carrier_list']) <= 1);
                }
            }
        }

        $cart_rules = CartRule::getCustomerCartRules(Context::getContext()->cookie->id_lang, Context::getContext()->cookie->id_customer, true, true, false, $this, true);

        $result = false;
        if ($this->id) {
            $result = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart = '.(int)$this->id);
        }

        $cart_rules_in_cart = array();

        if (is_array($result)) {
            foreach ($result as $row) {
                $cart_rules_in_cart[] = $row['id_cart_rule'];
            }
        }

        $total_products_wt = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $total_products = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS);

        $free_carriers_rules = array();

        $context = Context::getContext();
        foreach ($cart_rules as $cart_rule) {
            $total_price = $cart_rule['minimum_amount_tax'] ? $total_products_wt : $total_products;
            $total_price += $cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price : 0;
            $total_price += !$cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price_wt : 0;
            $condition = ($cart_rule['free_shipping'] && $cart_rule['carrier_restriction'] && $cart_rule['minimum_amount'] <= $total_price)?1:0;
            if(isset($cart_rule['code']) && !empty($cart_rule['code'])){
                $condition = ($cart_rule['free_shipping'] && $cart_rule['carrier_restriction'] && in_array($cart_rule['id_cart_rule'], $cart_rules_in_cart)
                    && $cart_rule['minimum_amount'] <= $total_price)?1:0;
            }
            if ($condition) {
                $cr = new CartRule((int)$cart_rule['id_cart_rule']);
                if (Validate::isLoadedObject($cr) &&
                    $cr->checkValidity($context, in_array((int)$cart_rule['id_cart_rule'], $cart_rules_in_cart), false, false)) {
                    $carriers = $cr->getAssociatedRestrictions('carrier', true, false);
                    if (is_array($carriers) && count($carriers) && isset($carriers['selected'])) {
                        foreach ($carriers['selected'] as $carrier) {
                            if (isset($carrier['id_carrier']) && $carrier['id_carrier']) {
                                $free_carriers_rules[] = (int)$carrier['id_carrier'];
                            }
                        }
                    }
                }
            }
        }

        // For each delivery options :
        //    - Set the carrier list
        //    - Calculate the price
        //    - Calculate the average position
        foreach ($delivery_option_list as $id_address => $delivery_option) {
            foreach ($delivery_option as $key => $value) {
                $total_price_with_tax = 0;
                $total_price_without_tax = 0;
                $position = 0;
                foreach ($value['carrier_list'] as $id_carrier => $data) {
                    $total_price_with_tax += $data['price_with_tax'];
                    $total_price_without_tax += $data['price_without_tax'];
                    $total_price_without_tax_with_rules = (in_array($id_carrier, $free_carriers_rules)) ? 0 : $total_price_without_tax;

                    if (!isset($carrier_collection[$id_carrier])) {
                        $carrier_collection[$id_carrier] = new Carrier($id_carrier);
                    }
                    $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['instance'] = $carrier_collection[$id_carrier];

                    if (Tools::file_get_contents($context->link->getMediaLink(_THEME_SHIP_DIR_.$id_carrier.'.jpg'))) { // by webkul
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = $context->link->getMediaLink(_THEME_SHIP_DIR_.$id_carrier.'.jpg'); // by webkul to get media link
                    } else {
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = false;
                    }

                    $position += $carrier_collection[$id_carrier]->position;
                }
                $delivery_option_list[$id_address][$key]['total_price_with_tax'] = $total_price_with_tax;
                $delivery_option_list[$id_address][$key]['total_price_without_tax'] = $total_price_without_tax;
                $delivery_option_list[$id_address][$key]['is_free'] = !$total_price_without_tax_with_rules ? true : false;
                $delivery_option_list[$id_address][$key]['position'] = $position / count($value['carrier_list']);
            }
        }

        // Sort delivery option list
        foreach ($delivery_option_list as &$array) {
            uasort($array, array('Cart', 'sortDeliveryOptionList'));
        }

        $cache[$this->id] = $delivery_option_list;
        return $cache[$this->id];
    }

    /**
     *
     * Sort list of option delivery by parameters define in the BO
     * @param $option1
     * @param $option2
     * @return int -1 if $option 1 must be placed before and 1 if the $option1 must be placed after the $option2
     */
    public static function sortDeliveryOptionList($option1, $option2)
    {
        static $order_by_price = null;
        static $order_way = null;
        if (is_null($order_by_price)) {
            $order_by_price = !Configuration::get('PS_CARRIER_DEFAULT_SORT');
        }
        if (is_null($order_way)) {
            $order_way = Configuration::get('PS_CARRIER_DEFAULT_ORDER');
        }

        if ($order_by_price) {
            if ($order_way) {
                return ($option1['total_price_with_tax'] < $option2['total_price_with_tax']) * 2 - 1;
            } // return -1 or 1
            else {
                return ($option1['total_price_with_tax'] >= $option2['total_price_with_tax']) * 2 - 1;
            }
        } // return -1 or 1
        elseif ($order_way) {
            return ($option1['position'] < $option2['position']) * 2 - 1;
        } // return -1 or 1
            else {
                return ($option1['position'] >= $option2['position']) * 2 - 1;
            } // return -1 or 1
    }

    public function carrierIsSelected($id_carrier, $id_address)
    {
        $delivery_option = $this->getDeliveryOption();
        $delivery_option_list = $this->getDeliveryOptionList();

        if (!isset($delivery_option[$id_address])) {
            return false;
        }

        if (!isset($delivery_option_list[$id_address][$delivery_option[$id_address]])) {
            return false;
        }

        if (!in_array($id_carrier, array_keys($delivery_option_list[$id_address][$delivery_option[$id_address]]['carrier_list']))) {
            return false;
        }

        return true;
    }

    /**
     * Get all deliveries options available for the current cart formated like Carriers::getCarriersForOrder
     * This method was wrote for retrocompatibility with 1.4 theme
     * New theme need to use Cart::getDeliveryOptionList() to generate carriers option in the checkout process
     *
     * @since 1.5.0
     *
     * @param Country $default_country
     * @param bool $flush Force flushing cache
     *
     */
    public function simulateCarriersOutput(?Country $default_country = null, $flush = false)
    {
        $delivery_option_list = $this->getDeliveryOptionList($default_country, $flush);

        // This method cannot work if there is multiple address delivery
        if (count($delivery_option_list) > 1 || empty($delivery_option_list)) {
            return array();
        }

        $carriers = array();
        foreach (reset($delivery_option_list) as $key => $option) {
            $price = $option['total_price_with_tax'];
            $price_tax_exc = $option['total_price_without_tax'];
            $name = $img = $delay = '';

            if ($option['unique_carrier']) {
                $carrier = reset($option['carrier_list']);
                if (isset($carrier['instance'])) {
                    $name = $carrier['instance']->name;
                    $delay = $carrier['instance']->delay;
                    $delay = isset($delay[Context::getContext()->language->id]) ? $delay[Context::getContext()->language->id] :
                        (isset($delay[(int)Configuration::get('PS_LANG_DEFAULT')]) ? $delay[(int)Configuration::get('PS_LANG_DEFAULT')] : '');

                }
                if (isset($carrier['logo'])) {
                    $img = $carrier['logo'];
                }
            } else {
                $nameList = array();
                foreach ($option['carrier_list'] as $carrier) {
                    $nameList[] = $carrier['instance']->name;
                }
                $name = join(' -', $nameList);
                $img = ''; // No images if multiple carriers
                $delay = '';
            }
            $carriers[] = array(
                'name' => $name,
                'img' => $img,
                'delay' => $delay,
                'price' => $price,
                'price_tax_exc' => $price_tax_exc,
                'id_carrier' => Cart::intifier($key), // Need to translate to an integer for retrocompatibility reason, in 1.4 template we used intval
                'is_module' => false,
            );
        }
        return $carriers;
    }

    public function simulateCarrierSelectedOutput($use_cache = true)
    {
        $delivery_option = $this->getDeliveryOption(null, false, $use_cache);

        if (count($delivery_option) > 1 || empty($delivery_option)) {
            return 0;
        }

        return Cart::intifier(reset($delivery_option));
    }

    /**
     * Translate a string option_delivery identifier ('24,3,') in a int (3240002000)
     *
     * The  option_delivery identifier is a list of integers separated by a ','.
     * This method replace the delimiter by a sequence of '0'.
     * The size of this sequence is fixed by the first digit of the return
     *
     * @return int
     */
    public static function intifier($string, $delimiter = ',')
    {
        $elm = explode($delimiter, $string);
        $max = max($elm);
        return strlen($max).implode(str_repeat('0', strlen($max) + 1), $elm);
    }

    /**
     * Translate a int option_delivery identifier (3240002000) in a string ('24,3,')
     */
    public static function desintifier($int, $delimiter = ',')
    {
        if (!$int)
            return;

        $delimiter_len = $int[0];
        $int = strrev(substr($int, 1));
        $elm = explode(str_repeat('0', $delimiter_len + 1), $int);
        return strrev(implode($delimiter, $elm));
    }

    /**
     * Does the cart use multiple address
     * @return bool
     */
    public function isMultiAddressDelivery()
    {
        static $cache = array();

        if (!isset($cache[$this->id])) {
            $sql = new DbQuery();
            $sql->select('count(distinct id_address_delivery)');
            $sql->from('cart_product', 'cp');
            $sql->where('id_cart = '.(int)$this->id);

            $cache[$this->id] = Db::getInstance()->getValue($sql) > 1;
        }
        return $cache[$this->id];
    }

    /**
     * Get all delivery addresses object for the current cart
     */
    public function getAddressCollection()
    {
        $collection = array();
        $cache_id = 'Cart::getAddressCollection'.(int)$this->id;
        if (!Cache::isStored($cache_id)) {
            $result = Db::getInstance()->executeS(
                'SELECT DISTINCT `id_address_delivery`
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE id_cart = '.(int)$this->id
            );
            Cache::store($cache_id, $result);
        } else {
            $result = Cache::retrieve($cache_id);
        }

        $result[] = array('id_address_delivery' => (int)$this->id_address_delivery);

        foreach ($result as $row) {
            if ((int)$row['id_address_delivery'] != 0) {
                $collection[(int)$row['id_address_delivery']] = new Address((int)$row['id_address_delivery']);
            }
        }
        return $collection;
    }

    // Get the id_address for tax calculation of a product
    public static function getIdAddressForTaxCalculation($id_product, $id_hotel = false, $id_product_room_type = false)
    {
        $id_address = 0;
        // if the product is a booking product/Room type
        if (Product::isBookingProduct($id_product)) {
            $id_address = HotelRoomType::getHotelIdAddressByIdProduct($id_product);
        } else {
            // if the product is a standard product the get the address as per the selling preference
            $sellingPreferenceType = Product::getSellingPreferenceType($id_product);

            if ($sellingPreferenceType == Product::SELLING_PREFERENCE_WITH_ROOM_TYPE) {
                if ($id_product_room_type) {
                    $objRoomType = new HotelRoomType();
                    if ($roomTypeInfo = $objRoomType->getRoomTypeInfoByIdProduct($id_product_room_type)) {
                        if ($htlAddress = HotelBranchInformation::getAddress($roomTypeInfo['id_hotel'])) {
                            $id_address = $htlAddress['id_address'];
                        }
                    }
                }
            } elseif ($sellingPreferenceType == Product::SELLING_PREFERENCE_HOTEL_STANDALONE) {
                if ($htlAddress = HotelBranchInformation::getAddress($id_hotel)) {
                    $id_address = $htlAddress['id_address'];
                }
            } elseif ($sellingPreferenceType == Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE) {
                if ($htlAddress = HotelBranchInformation::getAddress($id_hotel)) {
                    $id_address = $htlAddress['id_address'];
                } elseif ($id_product_room_type) {
                    $objRoomType = new HotelRoomType();
                    if ($roomTypeInfo = $objRoomType->getRoomTypeInfoByIdProduct($id_product_room_type)) {
                        if ($htlAddress = HotelBranchInformation::getAddress($roomTypeInfo['id_hotel'])) {
                            $id_address = $htlAddress['id_address'];
                        }
                    }
                }
            } elseif ($sellingPreferenceType == Product::SELLING_PREFERENCE_STANDALONE) {
                if (isset($id_customer)) {
                    $id_address = (int)Address::getFirstCustomerAddressId($id_customer);
                }
            }
        }

        return $id_address;
    }

    /**
    * Set the delivery option and id_carrier, if there is only one carrier
    */
    public function setDeliveryOption($delivery_option = null)
    {
        if (empty($delivery_option) || count($delivery_option) == 0) {
            $this->delivery_option = '';
            $this->id_carrier = 0;
            return;
        }
        Cache::clean('getContextualValue_*');
        $delivery_option_list = $this->getDeliveryOptionList(null, true);

        foreach ($delivery_option_list as $id_address => $options) {
            if (!isset($delivery_option[$id_address])) {
                foreach ($options as $key => $option) {
                    if ($option['is_best_price']) {
                        $delivery_option[$id_address] = $key;
                        break;
                    }
                }
            }
        }

        if (count($delivery_option) == 1) {
            $this->id_carrier = $this->getIdCarrierFromDeliveryOption($delivery_option);
        }

        $this->delivery_option = json_encode($delivery_option);
    }

    protected function getIdCarrierFromDeliveryOption($delivery_option)
    {
        $delivery_option_list = $this->getDeliveryOptionList();
        foreach ($delivery_option as $key => $value) {
            if (isset($delivery_option_list[$key]) && isset($delivery_option_list[$key][$value])) {
                if (count($delivery_option_list[$key][$value]['carrier_list']) == 1) {
                    return current(array_keys($delivery_option_list[$key][$value]['carrier_list']));
                }
            }
        }

        return 0;
    }

    /**
     * Get the delivery option selected, or if no delivery option was selected,
     * the cheapest option for each address
     *
     * @param Country|null $default_country
     * @param bool         $dontAutoSelectOptions
     * @param bool         $use_cache
     *
     * @return array|bool|mixed Delivery option
     */
    public function getDeliveryOption($default_country = null, $dontAutoSelectOptions = false, $use_cache = true)
    {
        static $cache = array();
        $cache_id = (int)(is_object($default_country) ? $default_country->id : 0).'-'.(int)$dontAutoSelectOptions;
        if (isset($cache[$cache_id]) && $use_cache) {
            return $cache[$cache_id];
        }

        $delivery_option_list = $this->getDeliveryOptionList($default_country);

        // The delivery option was selected
        if (isset($this->delivery_option) && $this->delivery_option != '') {
            $delivery_option = json_decode($this->delivery_option, true);
            if (is_array($delivery_option)) {
                $validated = false;
                foreach ($delivery_option as $key) {
                    foreach ($delivery_option_list as $id_address => $option) {
                        if (isset($delivery_option_list[$id_address][$key])) {
                            $validated = true;

                            if (!isset($delivery_option[$id_address])) {
                                $delivery_option              = array();
                                $delivery_option[$id_address] = $key;
                            }

                            break 2;
                        }
                    }
                }

                if ($validated) {
                    $cache[$cache_id] = $delivery_option;

                    return $delivery_option;
                }
            }
        }

        if ($dontAutoSelectOptions) {
            return false;
        }

        // No delivery option selected or delivery option selected is not valid, get the better for all options
        $delivery_option = array();
        foreach ($delivery_option_list as $id_address => $options) {
            foreach ($options as $key => $option) {
                if (Configuration::get('PS_CARRIER_DEFAULT') == -1 && $option['is_best_price']) {
                    $delivery_option[$id_address] = $key;
                    break;
                } elseif (Configuration::get('PS_CARRIER_DEFAULT') == -2 && $option['is_best_grade']) {
                    $delivery_option[$id_address] = $key;
                    break;
                } elseif ($option['unique_carrier'] && in_array(Configuration::get('PS_CARRIER_DEFAULT'), array_keys($option['carrier_list']))) {
                    $delivery_option[$id_address] = $key;
                    break;
                }
            }

            reset($options);
            if (!isset($delivery_option[$id_address])) {
                $delivery_option[$id_address] = key($options);
            }
        }

        $cache[$cache_id] = $delivery_option;

        return $delivery_option;
    }

    /**
    * Return shipping total for the cart
    *
    * @param array|null $delivery_option Array of the delivery option for each address
    * @param bool $use_tax
    * @param Country|null $default_country
    * @return float Shipping total
    */
    public function getTotalShippingCost($delivery_option = null, $use_tax = true, ?Country $default_country = null)
    {
        if (isset(Context::getContext()->cookie->id_country)) {
            $default_country = new Country(Context::getContext()->cookie->id_country);
        }
        if (is_null($delivery_option)) {
            $delivery_option = $this->getDeliveryOption($default_country, false, false);
        }

        $total_shipping = 0;
        $delivery_option_list = $this->getDeliveryOptionList($default_country);
        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address]) || !isset($delivery_option_list[$id_address][$key])) {
                continue;
            }
            if ($use_tax) {
                $total_shipping += $delivery_option_list[$id_address][$key]['total_price_with_tax'];
            } else {
                $total_shipping += $delivery_option_list[$id_address][$key]['total_price_without_tax'];
            }
        }

        return $total_shipping;
    }

    /**
     * Return shipping total of a specific carriers for the cart
     *
     * @param int $id_carrier
     * @param array $delivery_option Array of the delivery option for each address
     * @param bool $useTax
     * @param Country|null $default_country
     * @param array|null $delivery_option
     * @return float Shipping total
     */
    public function getCarrierCost($id_carrier, $useTax = true, ?Country $default_country = null, $delivery_option = null)
    {
        if (is_null($delivery_option)) {
            $delivery_option = $this->getDeliveryOption($default_country);
        }

        $total_shipping = 0;
        $delivery_option_list = $this->getDeliveryOptionList();


        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address]) || !isset($delivery_option_list[$id_address][$key])) {
                continue;
            }
            if (isset($delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier])) {
                if ($useTax) {
                    $total_shipping += $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['price_with_tax'];
                } else {
                    $total_shipping += $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['price_without_tax'];
                }
            }
        }

        return $total_shipping;
    }

    /**
     * @deprecated 1.5.0, use Cart->getPackageShippingCost()
     */
    public function getOrderShippingCost($id_carrier = null, $use_tax = true, ?Country $default_country = null, $product_list = null)
    {
        Tools::displayAsDeprecated();
        return $this->getPackageShippingCost((int)$id_carrier, $use_tax, $default_country, $product_list);
    }

    /**
     * Return package shipping cost
     *
     * @param int          $id_carrier      Carrier ID (default : current carrier)
     * @param bool         $use_tax
     * @param Country|null $default_country
     * @param array|null   $product_list    List of product concerned by the shipping.
     *                                      If null, all the product of the cart are used to calculate the shipping cost
     * @param int|null $id_zone
     *
     * @return float Shipping total
     */
    public function getPackageShippingCost($id_carrier = null, $use_tax = true, ?Country $default_country = null, $product_list = null, $id_zone = null)
    {
        if ($this->isVirtualCart()) {
            return 0;
        }

        if (!$default_country) {
            $default_country = Context::getContext()->country;
        }

        if (!is_null($product_list)) {
            foreach ($product_list as $key => $value) {
                if ($value['is_virtual'] == 1) {
                    unset($product_list[$key]);
                }
            }
        }

        if (is_null($product_list)) {
            $products = $this->getProducts();
        } else {
            $products = $product_list;
        }

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address_id = (int)$this->id_address_invoice;
        } elseif (count($product_list)) {
            $prod = current($product_list);
            $address_id = (int)$prod['id_address_delivery'];
        } else {
            $address_id = null;
        }
        if (!Address::addressExists($address_id)) {
            $address_id = null;
        }

        if (is_null($id_carrier) && !empty($this->id_carrier)) {
            $id_carrier = (int)$this->id_carrier;
        }

        $cache_id = 'getPackageShippingCost_'.(int)$this->id.'_'.(int)$address_id.'_'.(int)$id_carrier.'_'.(int)$use_tax.'_'.(int)$default_country->id.'_'.(int)$id_zone;
        if ($products) {
            foreach ($products as $product) {
                $cache_id .= '_'.(int)$product['id_product'].'_'.(int)$product['id_product_attribute'];
            }
        }

        if (Cache::isStored($cache_id)) {
            return Cache::retrieve($cache_id);
        }

        // Order total in default currency without fees
        $order_total = $this->getOrderTotal(true, Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING, $product_list);

        // Start with shipping cost at 0
        $shipping_cost = 0;
        // If no product added, return 0
        if (!count($products)) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        if (!isset($id_zone)) {
            // Get id zone
            if (!$this->isMultiAddressDelivery()
                && isset($this->id_address_delivery) // Be carefull, id_address_delivery is not usefull one 1.5
                && $this->id_address_delivery
                && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery
            )) {
                $id_zone = Address::getZoneById((int)$this->id_address_delivery);
            } else {
                if (!Validate::isLoadedObject($default_country)) {
                    $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));
                }

                $id_zone = (int)$default_country->id_zone;
            }
        }

        if ($id_carrier && !$this->isCarrierInRange((int)$id_carrier, (int)$id_zone)) {
            $id_carrier = '';
        }

        if (empty($id_carrier) && $this->isCarrierInRange((int)Configuration::get('PS_CARRIER_DEFAULT'), (int)$id_zone)) {
            $id_carrier = (int)Configuration::get('PS_CARRIER_DEFAULT');
        }

        $total_package_without_shipping_tax_inc = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, $product_list);
        if (empty($id_carrier)) {
            if ((int)$this->id_customer) {
                $customer = new Customer((int)$this->id_customer);
                $result = Carrier::getCarriers((int)Configuration::get('PS_LANG_DEFAULT'), true, false, (int)$id_zone, $customer->getGroups());
                unset($customer);
            } else {
                $result = Carrier::getCarriers((int)Configuration::get('PS_LANG_DEFAULT'), true, false, (int)$id_zone);
            }

            foreach ($result as $k => $row) {
                if ($row['id_carrier'] == Configuration::get('PS_CARRIER_DEFAULT')) {
                    continue;
                }

                if (!isset(self::$_carriers[$row['id_carrier']])) {
                    self::$_carriers[$row['id_carrier']] = new Carrier((int)$row['id_carrier']);
                }

                /** @var Carrier $carrier */
                $carrier = self::$_carriers[$row['id_carrier']];

                $shipping_method = $carrier->getShippingMethod();
                // Get only carriers that are compliant with shipping method
                if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight((int)$id_zone) === false)
                || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice((int)$id_zone) === false)) {
                    unset($result[$k]);
                    continue;
                }

                // If out-of-range behavior carrier is set on "Desactivate carrier"
                if ($row['range_behavior']) {
                    $check_delivery_price_by_weight = Carrier::checkDeliveryPriceByWeight($row['id_carrier'], $this->getTotalWeight(), (int)$id_zone);

                    $total_order = $total_package_without_shipping_tax_inc;
                    $check_delivery_price_by_price = Carrier::checkDeliveryPriceByPrice($row['id_carrier'], $total_order, (int)$id_zone, (int)$this->id_currency);

                    // Get only carriers that have a range compatible with cart
                    if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !$check_delivery_price_by_weight)
                    || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && !$check_delivery_price_by_price)) {
                        unset($result[$k]);
                        continue;
                    }
                }

                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping = $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), (int)$id_zone);
                } else {
                    $shipping = $carrier->getDeliveryPriceByPrice($order_total, (int)$id_zone, (int)$this->id_currency);
                }

                if (!isset($min_shipping_price)) {
                    $min_shipping_price = $shipping;
                }

                if ($shipping <= $min_shipping_price) {
                    $id_carrier = (int)$row['id_carrier'];
                    $min_shipping_price = $shipping;
                }
            }
        }

        if (empty($id_carrier)) {
            $id_carrier = Configuration::get('PS_CARRIER_DEFAULT');
        }

        if (!isset(self::$_carriers[$id_carrier])) {
            self::$_carriers[$id_carrier] = new Carrier((int)$id_carrier, Configuration::get('PS_LANG_DEFAULT'));
        }

        $carrier = self::$_carriers[$id_carrier];

        // No valid Carrier or $id_carrier <= 0 ?
        if (!Validate::isLoadedObject($carrier)) {
            Cache::store($cache_id, 0);
            return 0;
        }
        $shipping_method = $carrier->getShippingMethod();

        if (!$carrier->active) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        // Free fees if free carrier
        if ($carrier->is_free == 1) {
            Cache::store($cache_id, 0);
            return 0;
        }

        // Select carrier tax
        if ($use_tax && !Tax::excludeTaxeOption()) {
            $address = Address::initialize((int)$address_id);

            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                // With PS_ATCP_SHIPWRAP, pre-tax price is deduced
                // from post tax price, so no $carrier_tax here
                // even though it sounds weird.
                $carrier_tax = 0;
            } else {
                $carrier_tax = $carrier->getTaxesRate($address);
            }
        }

        $configuration = Configuration::getMultiple(array(
            'PS_SHIPPING_FREE_PRICE',
            'PS_SHIPPING_HANDLING',
            'PS_SHIPPING_METHOD',
            'PS_SHIPPING_FREE_WEIGHT'
        ));

        // Free fees
        $free_fees_price = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE'])) {
            $free_fees_price = Tools::convertPrice((float)$configuration['PS_SHIPPING_FREE_PRICE'], Currency::getCurrencyInstance((int)$this->id_currency));
        }
        $orderTotalwithDiscounts = $this->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalwithDiscounts >= (float)($free_fees_price) && (float)($free_fees_price) > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT'])
            && $this->getTotalWeight() >= (float)$configuration['PS_SHIPPING_FREE_WEIGHT']
            && (float)$configuration['PS_SHIPPING_FREE_WEIGHT'] > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }

        // Get shipping cost using correct method
        if ($carrier->range_behavior) {
            if (!isset($id_zone)) {
                // Get id zone
                if (isset($this->id_address_delivery)
                    && $this->id_address_delivery
                    && Customer::customerHasAddress($this->id_customer, $this->id_address_delivery)) {
                    $id_zone = Address::getZoneById((int)$this->id_address_delivery);
                } else {
                    $id_zone = (int)$default_country->id_zone;
                }
            }

            if (($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !Carrier::checkDeliveryPriceByWeight($carrier->id, $this->getTotalWeight(), (int)$id_zone))
            || ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && !Carrier::checkDeliveryPriceByPrice($carrier->id, $total_package_without_shipping_tax_inc, $id_zone, (int)$this->id_currency)
            )) {
                $shipping_cost += 0;
            } else {
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
                } else { // by price
                    $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int)$this->id_currency);
                }
            }
        } else {
            if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shipping_cost += $carrier->getDeliveryPriceByWeight($this->getTotalWeight($product_list), $id_zone);
            } else {
                $shipping_cost += $carrier->getDeliveryPriceByPrice($order_total, $id_zone, (int)$this->id_currency);
            }
        }
        // Adding handling charges
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
            $shipping_cost += (float)$configuration['PS_SHIPPING_HANDLING'];
        }

        // Additional Shipping Cost per product
        foreach ($products as $product) {
            if (!$product['is_virtual']) {
                $shipping_cost += $product['additional_shipping_cost'] * $product['cart_quantity'];
            }
        }

        $shipping_cost = Tools::convertPrice($shipping_cost, Currency::getCurrencyInstance((int)$this->id_currency));

        //get external shipping cost from module
        if ($carrier->shipping_external) {
            $module_name = $carrier->external_module_name;

            /** @var CarrierModule $module */
            $module = Module::getInstanceByName($module_name);

            if (Validate::isLoadedObject($module)) {
                if (property_exists($module, 'id_carrier')) {
                    $module->id_carrier = $carrier->id;
                }
                if ($carrier->need_range) {
                    if (method_exists($module, 'getPackageShippingCost')) {
                        $shipping_cost = $module->getPackageShippingCost($this, $shipping_cost, $products);
                    } else {
                        $shipping_cost = $module->getOrderShippingCost($this, $shipping_cost);
                    }
                } else {
                    $shipping_cost = $module->getOrderShippingCostExternal($this);
                }

                // Check if carrier is available
                if ($shipping_cost === false) {
                    Cache::store($cache_id, false);
                    return false;
                }
            } else {
                Cache::store($cache_id, false);
                return false;
            }
        }

        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            if (!$use_tax) {
                // With PS_ATCP_SHIPWRAP, we deduce the pre-tax price from the post-tax
                    // price. This is on purpose and required in Germany.
                    $shipping_cost /= (1 + $this->getAverageProductsTaxRate());
            }
        } else {
            // Apply tax
            if ($use_tax && isset($carrier_tax)) {
                $shipping_cost *= 1 + ($carrier_tax / 100);
            }
        }

        $shipping_cost = (float)Tools::ps_round((float)$shipping_cost, (Currency::getCurrencyInstance((int)$this->id_currency)->decimals * _PS_PRICE_DISPLAY_PRECISION_));
        Cache::store($cache_id, $shipping_cost);

        return $shipping_cost;
    }

    /**
    * Return cart weight
    * @return float Cart weight
    */
    public function getTotalWeight($products = null)
    {
        if (!is_null($products)) {
            $total_weight = 0;
            foreach ($products as $product) {
                if (!isset($product['weight_attribute']) || is_null($product['weight_attribute'])) {
                    $total_weight += $product['weight'] * $product['cart_quantity'];
                } else {
                    $total_weight += $product['weight_attribute'] * $product['cart_quantity'];
                }
            }
            return $total_weight;
        }

        if (!isset(self::$_totalWeight[$this->id])) {
            if (Combination::isFeatureActive()) {
                $weight_product_with_attribute = Db::getInstance()->getValue('
				SELECT SUM((p.`weight` + pa.`weight`) * cp.`quantity`) as nb
				FROM `'._DB_PREFIX_.'cart_product` cp
				LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (cp.`id_product_attribute` = pa.`id_product_attribute`)
				WHERE (cp.`id_product_attribute` IS NOT NULL AND cp.`id_product_attribute` != 0)
				AND cp.`id_cart` = '.(int)$this->id);
            } else {
                $weight_product_with_attribute = 0;
            }

            $weight_product_without_attribute = Db::getInstance()->getValue('
			SELECT SUM(p.`weight` * cp.`quantity`) as nb
			FROM `'._DB_PREFIX_.'cart_product` cp
			LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
			WHERE (cp.`id_product_attribute` IS NULL OR cp.`id_product_attribute` = 0)
			AND cp.`id_cart` = '.(int)$this->id);

            self::$_totalWeight[$this->id] = round((float)$weight_product_with_attribute + (float)$weight_product_without_attribute, 6);
        }

        return self::$_totalWeight[$this->id];
    }

    /**
     * @deprecated 1.5.0
     * @param CartRule $obj
     * @return bool|string
     */
    public function checkDiscountValidity($obj, $discounts, $order_total, $products, $check_cart_discount = false)
    {
        Tools::displayAsDeprecated();
        $context = Context::getContext()->cloneContext();
        $context->cart = $this;

        return $obj->checkValidity($context);
    }

    /**
    * Return useful informations for cart
    *
    * @return array Cart details
    */
    public function getSummaryDetails($id_lang = null, $refresh = false)
    {
        $context = Context::getContext();
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }

        $delivery = new Address((int)$this->id_address_delivery);
        $invoice = new Address((int)$this->id_address_invoice);

        // New layout system with personalization fields
        $formatted_addresses = array(
            'delivery' => AddressFormat::getFormattedLayoutData($delivery),
            'invoice' => AddressFormat::getFormattedLayoutData($invoice)
        );

        $base_total_tax_inc = $this->getOrderTotal(true);
        $base_total_tax_exc = $this->getOrderTotal(false);

        $total_tax = $base_total_tax_inc - $base_total_tax_exc;

        if ($total_tax < 0) {
            $total_tax = 0;
        }

        $currency = new Currency($this->id_currency);

        $products = $this->getProducts($refresh);

        foreach ($products as $key => &$product) {
            $product['price_without_quantity_discount'] = Product::getPriceStatic(
                $product['id_product'],
                !Product::getTaxCalculationMethod(),
                $product['id_product_attribute'],
                6,
                null,
                false,
                false
            );

            if ($product['reduction_type'] == 'amount') {
		$reduction = (!Product::getTaxCalculationMethod() ? (float)$product['price_wt'] : (float)$product['price']) - (float)$product['price_without_quantity_discount'];
                $product['reduction_formatted'] = Tools::displayPrice($reduction);
            }
        }

        $gift_products = array();
        $cart_rules = $this->getCartRules();
        $total_shipping = $this->getTotalShippingCost();
        $total_shipping_tax_exc = $this->getTotalShippingCost(null, false);
        $total_products_wt = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $total_products = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $total_rooms_wt = $this->getOrderTotal(true, Cart::ONLY_ROOMS);
        $total_rooms = $this->getOrderTotal(false, Cart::ONLY_ROOMS);
        $total_additional_services_wt = $this->getOrderTotal(true, Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD);
        $total_additional_services = $this->getOrderTotal(false, Cart::ONLY_ROOM_SERVICES_WITHOUT_AUTO_ADD);
        $total_additional_services_auto_add_wt = $this->getOrderTotal(true, Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE);
        $total_additional_services_auto_add = $this->getOrderTotal(false, Cart::ONLY_ROOM_SERVICES_WITH_AUTO_ADD_WITHOUT_CONVENIENCE_FEE);
        $convenience_fee_wt = $this->getOrderTotal(true, Cart::ONLY_CONVENIENCE_FEE);
        $convenience_fee = $this->getOrderTotal(false, Cart::ONLY_CONVENIENCE_FEE);
        $convenience_fee_tax = $convenience_fee_wt - $convenience_fee;
        $total_standalone_service_products_wt = $this->getOrderTotal(true, Cart::ONLY_STANDALONE_PRODUCTS);
        $total_standalone_service_products = $this->getOrderTotal(false, Cart::ONLY_STANDALONE_PRODUCTS);
        $total_discounts = $this->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $total_discounts_tax_exc = $this->getOrderTotal(false, Cart::ONLY_DISCOUNTS);
        $objCartBookingData = new HotelCartBookingData();
        $total_demands_wt = $objCartBookingData->getCartExtraDemands(
            $this->id,
            0,
            0,
            0,
            0,
            1,
            0,
            1
        );
        $total_demands = $objCartBookingData->getCartExtraDemands(
            $this->id,
            0,
            0,
            0,
            0,
            1,
            0,
            0
        );
        // The cart content is altered for display
        foreach ($cart_rules as &$cart_rule) {
            // If the cart rule is automatic (wihtout any code) and include free shipping, it should not be displayed as a cart rule but only set the shipping cost to 0
            if ($cart_rule['free_shipping'] && (empty($cart_rule['code']) || preg_match('/^'.CartRule::BO_ORDER_CODE_PREFIX.'[0-9]+/', $cart_rule['code']))) {

                $cart_rule['value_real'] -= $total_shipping;
                $cart_rule['value_tax_exc'] -= $total_shipping_tax_exc;
                $cart_rule['value_real'] = Tools::ps_round($cart_rule['value_real'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                if ($total_discounts > $cart_rule['value_real']) {
                    $total_discounts -= $total_shipping;
                }
                if ($total_discounts_tax_exc > $cart_rule['value_tax_exc']) {
                    $total_discounts_tax_exc -= $total_shipping_tax_exc;
                }

                // Update total shipping
                $total_shipping = 0;
                $total_shipping_tax_exc = 0;
            }

            if ($cart_rule['gift_product']) {
                foreach ($products as $key => &$product) {
                    if (empty($product['gift']) && $product['id_product'] == $cart_rule['gift_product'] && $product['id_product_attribute'] == $cart_rule['gift_product_attribute']) {
                        // Update total products
                        $total_products_wt = Tools::ps_round($total_products_wt - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $total_products = Tools::ps_round($total_products - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        if ($product['is_virtual']) {
                            $total_rooms_wt = Tools::ps_round($total_rooms_wt - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                            $total_rooms = Tools::ps_round($total_rooms - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        } else {
                            $total_normal_products_wt = Tools::ps_round($total_normal_products_wt - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                            $total_normal_products = Tools::ps_round($total_normal_products - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        }

                        // Update total discounts
                        $total_discounts = Tools::ps_round($total_discounts - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $total_discounts_tax_exc = Tools::ps_round($total_discounts_tax_exc - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update cart rule value
                        $cart_rule['value_real'] = Tools::ps_round($cart_rule['value_real'] - $product['price_wt'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'] - $product['price'], (int)$context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update product quantity
                        $product['total_wt'] = Tools::ps_round($product['total_wt'] - $product['price_wt'], (int)$currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['total'] = Tools::ps_round($product['total'] - $product['price'], (int)$currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['cart_quantity']--;

                        if (!$product['cart_quantity']) {
                            unset($products[$key]);
                        }

                        // Add a new product line
                        $gift_product = $product;
                        $gift_product['cart_quantity'] = 1;
                        $gift_product['price'] = 0;
                        $gift_product['price_wt'] = 0;
                        $gift_product['total_wt'] = 0;
                        $gift_product['total'] = 0;
                        $gift_product['gift'] = true;
                        $gift_products[] = $gift_product;

                        break; // One gift product per cart rule
                    }
                }
            }
        }

        $discountedProducts = array();
        foreach ($cart_rules as $key => &$cart_rule) {
            $product_rule_groups = $cart_rule['obj']->getProductRuleGroups();
            foreach ($product_rule_groups as $id_product_rule_group => $product_rule_group) {
                if (isset($product_rule_group['product_rules']) && $product_rule_group['product_rules']) {
                    foreach($product_rule_group['product_rules'] as $rule_products) {
                        if ($rule_products['type'] == 'products') {
                            $discountedProducts = array_merge($discountedProducts, $rule_products['values']);
                        }
                    }

                }
            }
        }

        $objHotelAdvancedPayment = new HotelAdvancedPayment();
        $total_rooms_with_services_without_discount_te = $total_rooms + $total_demands + $total_additional_services + $total_additional_services_auto_add + $total_standalone_service_products;
        $total_rooms_with_services_without_discount_ti = $total_rooms_wt + $total_demands_wt + $total_additional_services_wt + $total_additional_services_auto_add_wt + $total_standalone_service_products_wt;

        $cart_total_without_discount_te = $total_rooms_with_services_without_discount_te + $convenience_fee;
        $cart_total_without_discount_ti = $total_rooms_with_services_without_discount_ti + $convenience_fee_wt;
        $total_tax_without_discount = $cart_total_without_discount_ti - $cart_total_without_discount_te;
        if ($total_tax_without_discount < 0) {
            $total_tax_without_discount = 0;
        }

        $summary = array(
            'delivery' => $delivery,
            'delivery_state' => State::getNameById($delivery->id_state),
            'invoice' => $invoice,
            'invoice_state' => State::getNameById($invoice->id_state),
            'formattedAddresses' => $formatted_addresses,
            'products' => array_values($products),
            'gift_products' => $gift_products,
            'discounts' => array_values($cart_rules),
            'is_virtual_cart' => (int)$this->isVirtualCart(),
            'total_discounts' => $total_discounts,
            'total_discounts_tax_exc' => $total_discounts_tax_exc,
            'total_wrapping' => $this->getOrderTotal(true, Cart::ONLY_WRAPPING),
            'total_wrapping_tax_exc' => $this->getOrderTotal(false, Cart::ONLY_WRAPPING),
            'total_shipping' => $total_shipping,
            'total_shipping_tax_exc' => $total_shipping_tax_exc,
            'total_products_wt' => $total_products_wt,
            'total_products' => $total_products,
            'total_rooms_wt' => $total_rooms_wt,
            'total_rooms' => $total_rooms,
            'total_additional_services_wt' => $total_additional_services_wt,
            'total_additional_services' => $total_additional_services,
            'total_additional_services_auto_add_wt' => $total_additional_services_auto_add_wt,
            'total_additional_services_auto_add' => $total_additional_services_auto_add,
            'convenience_fee_wt' => $convenience_fee_wt,
            'convenience_fee' => $convenience_fee,
            'convenience_fee_tax' => $convenience_fee_tax,
            'total_standalone_service_products_wt' => $total_standalone_service_products_wt,
            'total_standalone_service_products' => $total_standalone_service_products,
            'total_extra_demands_wt' => $total_demands_wt,
            'total_extra_demands' => $total_demands,
            'total_price' => $base_total_tax_inc,
            'total_tax' => $total_tax,
            'total_price_without_tax' => $base_total_tax_exc,
            'is_advance_payment_active' => $objHotelAdvancedPayment->isAdvancePaymentAvailableForCurrentCart(),
            'advance_payment_amount_without_tax' => $this->getOrderTotal(false, Cart::ADVANCE_PAYMENT),
            'advance_payment_amount_with_tax' => $this->getOrderTotal(true, Cart::ADVANCE_PAYMENT),
            'discounted_products' => $discountedProducts,
            'is_multi_address_delivery' => $this->isMultiAddressDelivery() || ((int)Tools::getValue('multi-shipping') == 1),
            'free_ship' =>!$total_shipping && !count($this->getDeliveryAddressesWithoutCarriers(true, $errors)),
            'carrier' => new Carrier($this->id_carrier, $id_lang),
            'total_rooms_with_services_without_discount_te' => $total_rooms_with_services_without_discount_te,
            'total_rooms_with_services_without_discount_ti' => $total_rooms_with_services_without_discount_ti,
            'cart_total_without_discount_te' => $cart_total_without_discount_te,
            'cart_total_without_discount_ti' => $cart_total_without_discount_ti,
            'total_tax_without_discount' => $total_tax_without_discount
        );
        $hook = Hook::exec('actionCartSummary', $summary, null, true);
        if (is_array($hook)) {
            $summary = array_merge($summary, array_shift($hook));
        }

        return $summary;
    }

    public function checkQuantities($return_product = false)
    {
        if (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_')) {
            return false;
        }

        foreach ($this->getProducts() as $product) {
            if (!$this->allow_seperated_package && !$product['allow_oosp'] && StockAvailable::dependsOnStock($product['id_product']) &&
                $product['advanced_stock_management'] && (bool)Context::getContext()->customer->isLogged() && ($delivery = $this->getDeliveryOption()) && !empty($delivery)) {
                $product['stock_quantity'] = StockManager::getStockByCarrier((int)$product['id_product'], (int)$product['id_product_attribute'], $delivery);
            }
            if (!$product['active'] || (!$product['auto_add_to_cart'] && !$product['available_for_order'])
                || (!$product['allow_oosp'] && $product['stock_quantity'] < $product['cart_quantity'])) {
                return $return_product ? $product : false;
            }
        }

        return true;
    }

    public function checkProductsAccess()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return true;
        }

        foreach ($this->getProducts() as $product) {
            if (!Product::checkAccessStatic($product['id_product'], $this->id_customer)) {
                return $product['id_product'];
            }
        }

        return false;
    }


    public static function lastNoneOrderedCart($id_customer)
    {
        $sql = 'SELECT c.`id_cart`
				FROM '._DB_PREFIX_.'cart c
				WHERE NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`
									AND o.`id_customer` = '.(int)$id_customer.')
				AND c.`id_customer` = '.(int)$id_customer.'
					'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'c').'
				ORDER BY c.`date_upd` DESC';

        if (!$id_cart = Db::getInstance()->getValue($sql)) {
            return false;
        }

        return (int)$id_cart;
    }

    /**
    * Check if cart contains only virtual products
    *
    * @return bool true if is a virtual cart or false
    */
    public function isVirtualCart($strict = false)
    {
        if (!ProductDownload::isFeatureActive()) {
            return false;
        }

        if (!isset(self::$_isVirtualCart[$this->id])) {
            $products = $this->getProducts();
            if (!count($products)) {
                return false;
            }

            $is_virtual = 1;
            foreach ($products as $product) {
                if (empty($product['is_virtual'])) {
                    $is_virtual = 0;
                }
            }
            self::$_isVirtualCart[$this->id] = (int)$is_virtual;
        }

        return self::$_isVirtualCart[$this->id];
    }

    /**
     * Build cart object from provided id_order
     *
     * @param int $id_order
     * @return Cart|bool
     */
    public static function getCartByOrderId($id_order)
    {
        if ($id_cart = Cart::getCartIdByOrderId($id_order)) {
            return new Cart((int)$id_cart);
        }

        return false;
    }

    public static function getCartIdByOrderId($id_order)
    {
        $result = Db::getInstance()->getRow('SELECT `id_cart` FROM '._DB_PREFIX_.'orders WHERE `id_order` = '.(int)$id_order);
        if (!$result || empty($result) || !array_key_exists('id_cart', $result)) {
            return false;
        }
        return $result['id_cart'];
    }

    /**
     * Add customer's text
     *
     * @params int $id_product
     * @params int $index
     * @params int $type
     * @params string $textValue
     *
     * @return bool Always true
     */
    public function addTextFieldToProduct($id_product, $index, $type, $text_value)
    {
        return $this->_addCustomization($id_product, 0, $index, $type, $text_value, 0);
    }

    /**
     * Add customer's pictures
     *
     * @return bool Always true
     */
    public function addPictureToProduct($id_product, $index, $type, $file)
    {
        return $this->_addCustomization($id_product, 0, $index, $type, $file, 0);
    }

    /**
     * @deprecated 1.5.5.0
     * @param int $id_product
     * @param $index
     * @return bool
     */
    public function deletePictureToProduct($id_product, $index)
    {
        Tools::displayAsDeprecated();
        return $this->deleteCustomizationToProduct($id_product, 0);
    }

    /**
     * Remove a customer's customization
     *
     * @param int $id_product
     * @param int $index
     * @return bool
     */
    public function deleteCustomizationToProduct($id_product, $index)
    {
        $result = true;

        $cust_data = Db::getInstance()->getRow('
			SELECT cu.`id_customization`, cd.`index`, cd.`value`, cd.`type` FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd
			ON cu.`id_customization` = cd.`id_customization`
			WHERE cu.`id_cart` = '.(int)$this->id.'
			AND cu.`id_product` = '.(int)$id_product.'
			AND `index` = '.(int)$index.'
			AND `in_cart` = 0'
        );

        // Delete customization picture if necessary
        if ($cust_data['type'] == 0) {
            $result &= (
                Tools::deleteFile(_PS_UPLOAD_DIR_.$cust_data['value']) && //by webkul use deleteFile insted of unlink
                Tools::deleteFile(_PS_UPLOAD_DIR_.$cust_data['value'].'_small') //by webkul use deleteFile insted of unlink
            );
        }

        $result &= Db::getInstance()->execute('DELETE
			FROM `'._DB_PREFIX_.'customized_data`
			WHERE `id_customization` = '.(int)$cust_data['id_customization'].'
			AND `index` = '.(int)$index
        );
        return $result;
    }

    /**
     * Return custom pictures in this cart for a specified product
     *
     * @param int $id_product
     * @param int $type only return customization of this type
     * @param bool $not_in_cart only return customizations that are not in cart already
     * @return array result rows
     */
    public function getProductCustomization($id_product, $type = null, $not_in_cart = false)
    {
        if (!Customization::isFeatureActive()) {
            return array();
        }

        $result = Db::getInstance()->executeS('
			SELECT cu.id_customization, cd.index, cd.value, cd.type, cu.in_cart, cu.quantity
			FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd ON (cu.`id_customization` = cd.`id_customization`)
			WHERE cu.id_cart = '.(int)$this->id.'
			AND cu.id_product = '.(int)$id_product.
            ($type === Product::CUSTOMIZE_FILE ? ' AND type = '.(int)Product::CUSTOMIZE_FILE : '').
            ($type === Product::CUSTOMIZE_TEXTFIELD ? ' AND type = '.(int)Product::CUSTOMIZE_TEXTFIELD : '').
            ($not_in_cart ? ' AND in_cart = 0' : '')
        );
        return $result;
    }

    public static function getCustomerCarts($id_customer, $with_order = true)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *
		FROM '._DB_PREFIX_.'cart c
		WHERE c.`id_customer` = '.(int)$id_customer.'
		'.(!$with_order ? 'AND NOT EXISTS (SELECT 1 FROM '._DB_PREFIX_.'orders o WHERE o.`id_cart` = c.`id_cart`)' : '').'
		ORDER BY c.`date_add` DESC');
    }

    public static function replaceZeroByShopName($echo, $tr)
    {
        return ($echo == '0' ? Carrier::getCarrierNameFromShopName() : $echo);
    }

    public function duplicate()
    {
        if (!Validate::isLoadedObject($this)) {
            return false;
        }

        $cart = new Cart($this->id);
        $cart->id = null;
        $cart->id_shop = $this->id_shop;
        $cart->id_shop_group = $this->id_shop_group;

        if (!Customer::customerHasAddress((int)$cart->id_customer, (int)$cart->id_address_delivery)) {
            $cart->id_address_delivery = 0;
        }

        if (!Customer::customerHasAddress((int)$cart->id_customer, (int)$cart->id_address_invoice)) {
            $cart->id_address_invoice = (int)Address::getFirstCustomerAddressId((int)$cart->id_customer);
        }

        if ($cart->id_customer) {
            $cart->secure_key = Cart::$_customer->secure_key;
        }

        $cart->add();

        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $success = true;
        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'cart_product` WHERE `id_cart` = '.(int)$this->id);

        $product_gift = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT cr.`gift_product`, cr.`gift_product_attribute` FROM `'._DB_PREFIX_.'cart_rule` cr LEFT JOIN `'._DB_PREFIX_.'order_cart_rule` ocr ON (ocr.`id_order` = '.(int)$this->id.') WHERE ocr.`id_cart_rule` = cr.`id_cart_rule`');

        $id_address_delivery = Configuration::get('PS_ALLOW_MULTISHIPPING') ? $cart->id_address_delivery : 0;

        foreach ($products as $product) {
            if ($id_address_delivery) {
                if (Customer::customerHasAddress((int)$cart->id_customer, $product['id_address_delivery'])) {
                    $id_address_delivery = $product['id_address_delivery'];
                }
            }

            foreach ($product_gift as $gift) {
                if (isset($gift['gift_product']) && isset($gift['gift_product_attribute']) && (int)$gift['gift_product'] == (int)$product['id_product'] && (int)$gift['gift_product_attribute'] == (int)$product['id_product_attribute']) {
                    $product['quantity'] = (int)$product['quantity'] - 1;
                }
            }

            $success &= $cart->updateQty(
                (int)$product['quantity'],
                (int)$product['id_product'],
                (int)$product['id_product_attribute'],
                null,
                'up',
                (int)$id_address_delivery,
                new Shop((int)$cart->id_shop),
                false
            );
        }

        // Customized products
        $customs = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *
			FROM '._DB_PREFIX_.'customization c
			LEFT JOIN '._DB_PREFIX_.'customized_data cd ON cd.id_customization = c.id_customization
			WHERE c.id_cart = '.(int)$this->id
        );

        // Get datas from customization table
        $customs_by_id = array();
        foreach ($customs as $custom) {
            if (!isset($customs_by_id[$custom['id_customization']])) {
                $customs_by_id[$custom['id_customization']] = array(
                    'id_product_attribute' => $custom['id_product_attribute'],
                    'id_product' => $custom['id_product'],
                    'quantity' => $custom['quantity']
                );
            }
        }

        // Insert new customizations
        $custom_ids = array();
        foreach ($customs_by_id as $customization_id => $val) {
            Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'customization` (id_cart, id_product_attribute, id_product, `id_address_delivery`, quantity, `quantity_refunded`, `quantity_returned`, `in_cart`)
				VALUES('.(int)$cart->id.', '.(int)$val['id_product_attribute'].', '.(int)$val['id_product'].', '.(int)$id_address_delivery.', '.(int)$val['quantity'].', 0, 0, 1)'
            );
            $custom_ids[$customization_id] = Db::getInstance(_PS_USE_SQL_SLAVE_)->Insert_ID();
        }

        // Insert customized_data
        if (count($customs)) {
            $first = true;
            $sql_custom_data = 'INSERT INTO '._DB_PREFIX_.'customized_data (`id_customization`, `type`, `index`, `value`) VALUES ';
            foreach ($customs as $custom) {
                if (!$first) {
                    $sql_custom_data .= ',';
                } else {
                    $first = false;
                }

                $customized_value = $custom['value'];

                if ((int)$custom['type'] == 0) {
                    $customized_value = md5(uniqid(rand(), true));
                    Tools::copy(_PS_UPLOAD_DIR_.$custom['value'], _PS_UPLOAD_DIR_.$customized_value);
                    Tools::copy(_PS_UPLOAD_DIR_.$custom['value'].'_small', _PS_UPLOAD_DIR_.$customized_value.'_small');
                }

                $sql_custom_data .= '('.(int)$custom_ids[$custom['id_customization']].', '.(int)$custom['type'].', '.
                    (int)$custom['index'].', \''.pSQL($customized_value).'\')';
            }
            Db::getInstance()->execute($sql_custom_data);
        }

        return array('cart' => $cart, 'success' => $success);
    }

    public function getWsCartRows()
    {
        return Db::getInstance()->executeS('
			SELECT id_product, id_product_attribute, quantity, id_address_delivery
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE id_cart = '.(int)$this->id.' AND id_shop = '.(int)Context::getContext()->shop->id
        );
    }

    public function setWsCartRows($values)
    {
        if ($this->deleteAssociations()) {
            $query = 'INSERT INTO `'._DB_PREFIX_.'cart_product`(`id_cart`, `id_product`, `id_product_attribute`, `id_address_delivery`, `quantity`, `date_add`, `id_shop`) VALUES ';

            foreach ($values as $value) {
                $query .= '('.(int)$this->id.', '.(int)$value['id_product'].', '.
                    (isset($value['id_product_attribute']) ? (int)$value['id_product_attribute'] : 'NULL').', '.
                    (isset($value['id_address_delivery']) ? (int)$value['id_address_delivery'] : 0).', '.
                    (int)$value['quantity'].', NOW(), '.(int)Context::getContext()->shop->id.'),';
            }

            Db::getInstance()->execute(rtrim($query, ','));
        }

        return true;
    }

    public function setProductAddressDelivery($id_product, $id_product_attribute, $old_id_address_delivery, $new_id_address_delivery)
    {
        // Check address is linked with the customer
        if (!Customer::customerHasAddress(Context::getContext()->customer->id, $new_id_address_delivery)) {
            return false;
        }

        if ($new_id_address_delivery == $old_id_address_delivery) {
            return false;
        }

        // Checking if the product with the old address delivery exists
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = '.(int)$id_product);
        $sql->where('id_product_attribute = '.(int)$id_product_attribute);
        $sql->where('id_address_delivery = '.(int)$old_id_address_delivery);
        $sql->where('id_cart = '.(int)$this->id);
        $result = Db::getInstance()->getValue($sql);

        if ($result == 0) {
            return false;
        }

        // Checking if there is no others similar products with this new address delivery
        $sql = new DbQuery();
        $sql->select('sum(quantity) as qty');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = '.(int)$id_product);
        $sql->where('id_product_attribute = '.(int)$id_product_attribute);
        $sql->where('id_address_delivery = '.(int)$new_id_address_delivery);
        $sql->where('id_cart = '.(int)$this->id);
        $result = Db::getInstance()->getValue($sql);

        // Removing similar products with this new address delivery
        $sql = 'DELETE FROM '._DB_PREFIX_.'cart_product
			WHERE id_product = '.(int)$id_product.'
			AND id_product_attribute = '.(int)$id_product_attribute.'
			AND id_address_delivery = '.(int)$new_id_address_delivery.'
			AND id_cart = '.(int)$this->id.'
			LIMIT 1';
        Db::getInstance()->execute($sql);

        // Changing the address
        $sql = 'UPDATE '._DB_PREFIX_.'cart_product
			SET `id_address_delivery` = '.(int)$new_id_address_delivery.',
			`quantity` = `quantity` + '.(int)$result.'
			WHERE id_product = '.(int)$id_product.'
			AND id_product_attribute = '.(int)$id_product_attribute.'
			AND id_address_delivery = '.(int)$old_id_address_delivery.'
			AND id_cart = '.(int)$this->id.'
			LIMIT 1';
        Db::getInstance()->execute($sql);

        // Changing the address of the customizations
        $sql = 'UPDATE '._DB_PREFIX_.'customization
			SET `id_address_delivery` = '.(int)$new_id_address_delivery.'
			WHERE id_product = '.(int)$id_product.'
			AND id_product_attribute = '.(int)$id_product_attribute.'
			AND id_address_delivery = '.(int)$old_id_address_delivery.'
			AND id_cart = '.(int)$this->id;
        Db::getInstance()->execute($sql);

        return true;
    }

    public function duplicateProduct($id_product, $id_product_attribute, $id_address_delivery,
        $new_id_address_delivery, $quantity = 1, $keep_quantity = false)
    {
        // Check address is linked with the customer
        if (!Customer::customerHasAddress(Context::getContext()->customer->id, $new_id_address_delivery)) {
            return false;
        }

        // Checking the product do not exist with the new address
        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('cart_product', 'c');
        $sql->where('id_product = '.(int)$id_product);
        $sql->where('id_product_attribute = '.(int)$id_product_attribute);
        $sql->where('id_address_delivery = '.(int)$new_id_address_delivery);
        $sql->where('id_cart = '.(int)$this->id);
        $result = Db::getInstance()->getValue($sql);

        if ($result > 0) {
            return false;
        }

        // Duplicating cart_product line
        $sql = 'INSERT INTO '._DB_PREFIX_.'cart_product
			(`id_cart`, `id_product`, `id_shop`, `id_product_attribute`, `quantity`, `date_add`, `id_address_delivery`)
			values(
				'.(int)$this->id.',
				'.(int)$id_product.',
				'.(int)$this->id_shop.',
				'.(int)$id_product_attribute.',
				'.(int)$quantity.',
				NOW(),
				'.(int)$new_id_address_delivery.')';

        Db::getInstance()->execute($sql);

        if (!$keep_quantity) {
            $sql = new DbQuery();
            $sql->select('quantity');
            $sql->from('cart_product', 'c');
            $sql->where('id_product = '.(int)$id_product);
            $sql->where('id_product_attribute = '.(int)$id_product_attribute);
            $sql->where('id_address_delivery = '.(int)$id_address_delivery);
            $sql->where('id_cart = '.(int)$this->id);
            $duplicatedQuantity = Db::getInstance()->getValue($sql);

            if ($duplicatedQuantity > $quantity) {
                $sql = 'UPDATE '._DB_PREFIX_.'cart_product
					SET `quantity` = `quantity` - '.(int)$quantity.'
					WHERE id_cart = '.(int)$this->id.'
					AND id_product = '.(int)$id_product.'
					AND id_shop = '.(int)$this->id_shop.'
					AND id_product_attribute = '.(int)$id_product_attribute.'
					AND id_address_delivery = '.(int)$id_address_delivery;
                Db::getInstance()->execute($sql);
            }
        }

        // Checking if there is customizations
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('customization', 'c');
        $sql->where('id_product = '.(int)$id_product);
        $sql->where('id_product_attribute = '.(int)$id_product_attribute);
        $sql->where('id_address_delivery = '.(int)$id_address_delivery);
        $sql->where('id_cart = '.(int)$this->id);
        $results = Db::getInstance()->executeS($sql);

        foreach ($results as $customization) {
            // Duplicate customization
            $sql = 'INSERT INTO '._DB_PREFIX_.'customization
				(`id_product_attribute`, `id_address_delivery`, `id_cart`, `id_product`, `quantity`, `in_cart`)
				VALUES (
					'.(int)$customization['id_product_attribute'].',
					'.(int)$new_id_address_delivery.',
					'.(int)$customization['id_cart'].',
					'.(int)$customization['id_product'].',
					'.(int)$quantity.',
					'.(int)$customization['in_cart'].')';

            Db::getInstance()->execute($sql);

            // Save last insert ID before doing another query
            $last_id = (int)Db::getInstance()->Insert_ID();

            // Get data from duplicated customizations
            $sql = new DbQuery();
            $sql->select('`type`, `index`, `value`');
            $sql->from('customized_data');
            $sql->where('id_customization = '.$customization['id_customization']);
            $last_row = Db::getInstance()->getRow($sql);

            // Insert new copied data with new customization ID into customized_data table
            $last_row['id_customization'] = $last_id;
            Db::getInstance()->insert('customized_data', $last_row);
        }

        $customization_count = count($results);
        if ($customization_count > 0) {
            $sql = 'UPDATE '._DB_PREFIX_.'cart_product
				SET `quantity` = `quantity` + '.(int)$customization_count * $quantity.'
				WHERE id_cart = '.(int)$this->id.'
				AND id_product = '.(int)$id_product.'
				AND id_shop = '.(int)$this->id_shop.'
				AND id_product_attribute = '.(int)$id_product_attribute.'
				AND id_address_delivery = '.(int)$new_id_address_delivery;
            Db::getInstance()->execute($sql);
        }

        return true;
    }

    /**
     * Update products cart address delivery with the address delivery of the cart
     */
    public function setNoMultishipping()
    {
        $emptyCache = false;
        if (Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            // Upgrading quantities
            $sql = 'SELECT sum(`quantity`) as quantity, id_product, id_product_attribute, count(*) as count
					FROM `'._DB_PREFIX_.'cart_product`
					WHERE `id_cart` = '.(int)$this->id.'
						AND `id_shop` = '.(int)$this->id_shop.'
					GROUP BY id_product, id_product_attribute
					HAVING count > 1';

            foreach (Db::getInstance()->executeS($sql) as $product) {
                $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
					SET `quantity` = '.$product['quantity'].'
					WHERE  `id_cart` = '.(int)$this->id.'
						AND `id_shop` = '.(int)$this->id_shop.'
						AND id_product = '.$product['id_product'].'
						AND id_product_attribute = '.$product['id_product_attribute'];
                if (Db::getInstance()->execute($sql)) {
                    $emptyCache = true;
                }
            }

            // Merging multiple lines
            $sql = 'DELETE cp1
				FROM `'._DB_PREFIX_.'cart_product` cp1
					INNER JOIN `'._DB_PREFIX_.'cart_product` cp2
					ON (
						(cp1.id_cart = cp2.id_cart)
						AND (cp1.id_product = cp2.id_product)
						AND (cp1.id_product_attribute = cp2.id_product_attribute)
						AND (cp1.id_address_delivery <> cp2.id_address_delivery)
						AND (cp1.date_add > cp2.date_add)
					)';
            Db::getInstance()->execute($sql);
        }

        // Update delivery address for each product line
        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
		SET `id_address_delivery` = (
			SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart`
			WHERE `id_cart` = '.(int)$this->id.' AND `id_shop` = '.(int)$this->id_shop.'
		)
		WHERE `id_cart` = '.(int)$this->id.'
		'.(Configuration::get('PS_ALLOW_MULTISHIPPING') ? ' AND `id_shop` = '.(int)$this->id_shop : '');

        $cache_id = 'Cart::setNoMultishipping'.(int)$this->id.'-'.(int)$this->id_shop.((isset($this->id_address_delivery) && $this->id_address_delivery) ? '-'.(int)$this->id_address_delivery : '');
        if (!Cache::isStored($cache_id)) {
            if ($result = (bool)Db::getInstance()->execute($sql)) {
                $emptyCache = true;
            }
            Cache::store($cache_id, $result);
        }

        if (Customization::isFeatureActive()) {
            Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = (
				SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart`
				WHERE `id_cart` = '.(int)$this->id.'
			)
			WHERE `id_cart` = '.(int)$this->id);
        }

        if ($emptyCache) {
            $this->_products = null;
        }
    }

    /**
     * Set an address to all products on the cart without address delivery
     */
    public function autosetProductAddress()
    {
        $id_address_delivery = 0;

        if ((int)$this->id_address_delivery > 0) {
            $id_address_delivery = (int)$this->id_address_delivery;
        }

        if (!$id_address_delivery) {
            return;
        }

        // Update
        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
			SET `id_address_delivery` = '.(int)$id_address_delivery.'
			WHERE `id_cart` = '.(int)$this->id.'
				AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL)
				AND `id_shop` = '.(int)$this->id_shop;
        Db::getInstance()->execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = '.(int)$id_address_delivery.'
			WHERE `id_cart` = '.(int)$this->id.'
				AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL)';

        Db::getInstance()->execute($sql);
    }

    public function deleteAssociations()
    {
        return (Db::getInstance()->execute('
				DELETE FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_cart` = '.(int)$this->id) !== false);
    }

    /**
     * isGuestCartByCartId
     *
     * @param int $id_cart
     * @return bool true if cart has been made by a guest customer
     */
    public static function isGuestCartByCartId($id_cart)
    {
        if (!(int)$id_cart) {
            return false;
        }
        return (bool)Db::getInstance()->getValue('
			SELECT `is_guest`
			FROM `'._DB_PREFIX_.'customer` cu
			LEFT JOIN `'._DB_PREFIX_.'cart` ca ON (ca.`id_customer` = cu.`id_customer`)
			WHERE ca.`id_cart` = '.(int)$id_cart);
    }

    /**
     * isCarrierInRange
     *
     * Check if the specified carrier is in range
     *
     * @id_carrier int
     * @id_zone int
     */
    public function isCarrierInRange($id_carrier, $id_zone)
    {
        $carrier = new Carrier((int)$id_carrier, Configuration::get('PS_LANG_DEFAULT'));
        $shipping_method = $carrier->getShippingMethod();
        if (!$carrier->range_behavior) {
            return true;
        }

        if ($shipping_method == Carrier::SHIPPING_METHOD_FREE) {
            return true;
        }

        $check_delivery_price_by_weight = Carrier::checkDeliveryPriceByWeight(
            (int)$id_carrier,
            $this->getTotalWeight(),
            $id_zone
        );
        if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && $check_delivery_price_by_weight) {
            return true;
        }

        $check_delivery_price_by_price = Carrier::checkDeliveryPriceByPrice(
            (int)$id_carrier,
            $this->getOrderTotal(
                true,
                Cart::BOTH_WITHOUT_SHIPPING
            ),
            $id_zone,
            (int)$this->id_currency
        );
        if ($shipping_method == Carrier::SHIPPING_METHOD_PRICE && $check_delivery_price_by_price) {
            return true;
        }

        return false;
    }

    /**
     * @param bool $ignore_virtual Ignore virtual product
     * @param bool $exclusive If true, the validation is exclusive : it must be present product in stock and out of stock
     * @since 1.5.0
     *
     * @return bool false is some products from the cart are out of stock
     */
    public function isAllProductsInStock($ignore_virtual = false, $exclusive = false)
    {
        $product_out_of_stock = 0;
        $product_in_stock = 0;
        foreach ($this->getProducts() as $product) {
            if (!$exclusive) {
                if (((int)$product['quantity_available'] - (int)$product['cart_quantity']) < 0
                    && (!$ignore_virtual || !$product['is_virtual'])) {
                    return false;
                }
            } else {
                if ((int)$product['quantity_available'] <= 0
                    && (!$ignore_virtual || !$product['is_virtual'])) {
                    $product_out_of_stock++;
                }
                if ((int)$product['quantity_available'] > 0
                    && (!$ignore_virtual || !$product['is_virtual'])) {
                    $product_in_stock++;
                }

                if ($product_in_stock > 0 && $product_out_of_stock > 0) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *
     * Execute hook displayCarrierList (extraCarrier) and merge theme to the $array
     * @param array $array
     */
    public static function addExtraCarriers(&$array)
    {
        $first = true;
        $hook_extracarrier_addr = array();
        foreach (Context::getContext()->cart->getAddressCollection() as $address) {
            $hook = Hook::exec('displayCarrierList', array('address' => $address));
            $hook_extracarrier_addr[$address->id] = $hook;

            if ($first) {
                $array = array_merge(
                    $array,
                    array('HOOK_EXTRACARRIER' => $hook)
                );
                $first = false;
            }
            $array = array_merge(
                $array,
                array('HOOK_EXTRACARRIER_ADDR' => $hook_extracarrier_addr)
            );
        }
    }

    /**
     * Get all the ids of the delivery addresses without carriers
     *
     * @param bool $return_collection Return a collection
     * @param array &$error contain an error message if an error occurs
     * @return array Array of address id or of address object
     */
    public function getDeliveryAddressesWithoutCarriers($return_collection = false, &$error = array())
    {
        $addresses_without_carriers = array();
        foreach ($this->getProducts() as $product) {
            if (!in_array($product['id_address_delivery'], $addresses_without_carriers)
                && !count(Carrier::getAvailableCarrierList(new Product($product['id_product']), null, $product['id_address_delivery'], null, null, $error))) {
                $addresses_without_carriers[] = $product['id_address_delivery'];
            }
        }
        if (!$return_collection) {
            return $addresses_without_carriers;
        } else {
            $addresses_instance_without_carriers = array();
            foreach ($addresses_without_carriers as $id_address) {
                $addresses_instance_without_carriers[] = new Address($id_address);
            }
            return $addresses_instance_without_carriers;
        }
    }

    public static function getProductQtyInCart($id_cart, $id_product)
    {
        $result = Db::getInstance()->getValue("SELECT `quantity` FROM `"._DB_PREFIX_."cart_product` WHERE `id_cart`= ".$id_cart." AND `id_product`=".$id_product);

        if ($result) {
            return $result;
        }

        return 0;
    }

    // Webservice:: Get booking rows
    public function getWsCartBookings()
    {
        return Db::getInstance()->executeS('
			SELECT `id`, `date_from`, `date_to`, `id_hotel`, `id_product`
			FROM `'._DB_PREFIX_.'htl_cart_booking_data`
			WHERE id_cart = '.(int)$this->id.' ORDER BY `id` ASC'
        );
    }

    public function addWs($autodate = true, $null_values = false)
    {
        $this->id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $objShop = new Shop($this->id_shop);
        $this->id_shop_group = $objShop->id_shop_group;

        // get bookings xaml data
        $postData = trim(file_get_contents('php://input'));
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(mb_convert_encoding($postData, 'ISO-8859-1'));
        $cartData = json_decode(json_encode($xml, true));

        $this->id_address_delivery = $cartData->cart->id_address_delivery;
        $this->id_address_invoice = $cartData->cart->id_address_invoice;

        if ($this->add($autodate, $null_values)) {
            // set bookings for the cart
            $bookingRows = $cartData->cart->associations->cart_bookings->booking;
            if (isset($bookingRows->id_hotel)) {
                $bookingRows = array($bookingRows);
            }
            $this->setWsCartRooms($bookingRows);

            return true;
        }

        return false;
    }

    public function updateWs($null_values = false)
    {
        $this->id_shop = Configuration::get('PS_SHOP_DEFAULT');
        $objShop = new Shop($this->id_shop);
        $this->id_shop_group = $objShop->id_shop_group;

        // get bookings xml data
        $postData = trim(file_get_contents('php://input'));
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(mb_convert_encoding($postData, 'ISO-8859-1'));
        $cartData = json_decode(json_encode($xml, true));


        $this->id_address_delivery = $cartData->cart->id_address_delivery;
        $this->id_address_invoice = $cartData->cart->id_address_invoice;

        if ($this->update($null_values)) {
            // set bookings for the cart
            $bookingRows = $cartData->cart->associations->cart_bookings->booking;
            if (isset($bookingRows->id_hotel)) {
                $bookingRows = array($bookingRows);
            }
            $this->setWsCartRooms($bookingRows);

            return true;
        }

        return false;
    }

    public function setWsCartRooms($bookingRows)
    {
        $idCart = $this->id;
        $objCart = new Cart($idCart);
        $idGuest = $objCart->id_guest;

        // delete cart bookings
        $objCartBooking = new HotelCartBookingData();
        $objCartBooking->deleteCartBookingData($idCart);
        $this->deleteAssociations();
        $hasErrors = 0;
        // lets validate the cart booking data first
        if (isset($bookingRows) && count($bookingRows)) {
            $objRoomType = new HotelRoomType();
            $objBookingDetail = new HotelBookingDetail();
            $errors = array();
            $idCustomer = $objCart->id_customer;
            $idCurrency = $objCart->id_currency;

            $extraDemands = null;

            // We have to check the alreadt entered rooms for booking parameters with this variable as Also is sending all the rooms everytime
            // because id_guest is not managed in the API call
            $roomsAddedToCart = [];
            foreach ($bookingRows as $booking) {
                $booking = json_decode(json_encode($booking, true), true);
                if (isset($booking['extra_demands']['extra_demand'])) {
                    $extraDemands = $booking['extra_demands']['extra_demand'];
                    if (isset($extraDemands['id_global_demand'])) {
                        $extraDemands = array($extraDemands);
                    }
                    $extraDemands = json_encode($extraDemands);
                }

                // get room booking info
                $idProduct = $booking['id_product'];
                $dateFrom = $booking['date_from'];
                $dateTo = $booking['date_to'];
                $prodQty = 1;
                // change format to the database format
                $dateFrom = date("Y-m-d", strtotime($dateFrom));
                $dateTo = date("Y-m-d", strtotime($dateTo));

                if ($roomTypeInfo = $objRoomType->getRoomTypeInfoByIdProduct($idProduct)) {
                    if ($idHotel = $roomTypeInfo['id_hotel']) {
                        if (strtotime($dateFrom) >= strtotime($dateTo)) {
                            $hasErrors = 1;
                            WebserviceRequest::getInstance()->setError(500, Tools::displayError('Check-out date must be after check-in date.'), 134);
                        } elseif ($maxOrdDate = HotelOrderRestrictDate::getMaxOrderDate($idHotel)) {
                            // Check Order restrict condition before adding in to cart
                            if (strtotime('-1 day', strtotime($maxOrdDate)) < strtotime($dateFrom)
                                || strtotime($maxOrdDate) < strtotime($dateTo)
                            ) {
                                $maxOrdDate = date('d-m-Y', strtotime($maxOrdDate));
                                $hasErrors = 1;
                                WebserviceRequest::getInstance()->setError(500, Tools::displayError('You can\'t book room after date.'), 134);
                            }
                        }

                        if (!count($errors)) {
                            $numDays = HotelHelper::getNumberOfDays($dateFrom, $dateTo);
                            $prodQty = $prodQty * (int) $numDays;
                            $reqRooms = 1;

                            $bookingParams = array(
                                'date_from' => $dateFrom,
                                'date_to' => $dateTo,
                                'hotel_id' => $idHotel,
                                'id_room_type' => $idProduct,
                                'only_search_data' => 1,
                                'id_cart' => $idCart,
                                'id_guest' => $idGuest,
                            );
                            if ($hotelRoomData = $objBookingDetail->dataForFrontSearch($bookingParams)) {
                                // unset already added rooms to cart as per sepcific parametrs
                                if (isset($hotelRoomData['rm_data'][$idProduct]['data']['available']) && $hotelRoomData['rm_data'][$idProduct]['data']['available']) {
                                    foreach ($hotelRoomData['rm_data'][$idProduct]['data']['available'] as $keyRoom => $roomInfo) {
                                        if (isset(
                                            $roomsAddedToCart[strtotime($dateFrom).'_'.strtotime($dateTo).'_'.$objCart->id.'_'.$roomInfo['id_product'].'_'.$roomInfo['id_room']]
                                        )) {
                                            unset($hotelRoomData['rm_data'][$idProduct]['data']['available'][$keyRoom]);
                                            $hotelRoomData['stats']['num_avail'] -= 1;
                                        }
                                    }
                                }

                                if (isset($hotelRoomData['stats']['num_avail'])) {
                                    $totalAvailRooms = $hotelRoomData['stats']['num_avail'];
                                    if ($totalAvailRooms < $reqRooms) {
                                        $hasErrors = 1;
                                        WebserviceRequest::getInstance()->setError(500, Tools::displayError('unavailable quantity'), 134);
                                    }
                                } else {
                                    $hasErrors = 1;
                                    WebserviceRequest::getInstance()->setError(500, Tools::displayError('Rooms are unavailable. Please try with different dates'), 134);
                                }
                            } else {
                                $hasErrors = 1;
                                WebserviceRequest::getInstance()->setError(500, Tools::displayError('Rooms are unavailable. Please try with different dates'), 134);
                            }
                        }
                    } else {
                        $hasErrors = 1;
                        WebserviceRequest::getInstance()->setError(500, Tools::displayError('Rooms are unavailable.'), 134);
                    }
                } else {
                    $hasErrors = 1;
                    WebserviceRequest::getInstance()->setError(500, Tools::displayError('Rooms are unavailable.'), 134);
                }

                if ($prodQty == 0) {
                    $hasErrors = 1;
                    WebserviceRequest::getInstance()->setError(500, Tools::displayError('Null quantity.'), 134);
                } elseif (!$idProduct) {
                    $hasErrors = 1;
                    WebserviceRequest::getInstance()->setError(500, Tools::displayError('Room type not found.'), 134);
                }
                $product = new Product($idProduct, true, $objCart->id_lang);
                if (!$product->id || !$product->active) {
                    $hasErrors = 1;
                    WebserviceRequest::getInstance()->setError(500, Tools::displayError('Room type not found.'), 134);
                }
                // If no errors, process product addition
                if (!$hasErrors) {
                    $update_quantity = $objCart->updateQty($prodQty, $idProduct, 0, 0, Tools::getValue('op', 'up'), $this->id_address_delivery);
                    $chkQty = 0;
                    if ($hotelAvailRomms = $hotelRoomData['rm_data'][$idProduct]['data']['available']) {
                        foreach ($hotelAvailRomms as $key_hotel_room_info => $val_hotel_room_info) {
                            if ($chkQty < $reqRooms) {
                                $objCartBooking = new HotelCartBookingData();
                                $objCartBooking->id_cart = $objCart->id;
                                $objCartBooking->id_guest = $objCart->id_guest;
                                $objCartBooking->id_customer = $idCustomer;
                                $objCartBooking->id_currency = $idCurrency;
                                $objCartBooking->id_product = $val_hotel_room_info['id_product'];
                                $objCartBooking->id_room = $val_hotel_room_info['id_room'];
                                $objCartBooking->id_hotel = $val_hotel_room_info['id_hotel'];
                                $objCartBooking->booking_type = HotelBookingDetail::ALLOTMENT_AUTO;
                                $objCartBooking->quantity = $numDays;
                                $objCartBooking->extra_demands = $extraDemands;
                                $objCartBooking->date_from = $dateFrom;
                                $objCartBooking->date_to = $dateTo;
                                $objCartBooking->save();
                                ++$chkQty;

                                // Enter rooms which are added to cart already in the Array
                                $roomsAddedToCart[strtotime($dateFrom).'_'.strtotime($dateTo).'_'.$objCart->id.'_'.$val_hotel_room_info['id_product'].'_'.$val_hotel_room_info['id_room']] = 1;
                            } else {
                                break;
                            }
                        }
                    }
                } else {
                    // delete cart bookings
                    $objCartBooking = new HotelCartBookingData();
                    $objCartBooking->deleteCartBookingData($idCart);
                    $this->deleteAssociations();
                }
            }
        }

        return true;
    }
}

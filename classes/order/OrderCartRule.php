<?php
/*
Ramadhani
*/

class OrderCartRuleCore extends ObjectModel
{
    /** @var int */
    public $id_order_cart_rule;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_cart_rule;

    /** @var int */
    public $id_order_invoice;

    /** @var string */
    public $name;

    /** @var float value (tax incl.) of voucher */
    public $value;

    /** @var float value (tax excl.) of voucher */
    public $value_tax_excl;

    /** @var bool value : voucher gives free shipping or not */
    public $free_shipping;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'order_cart_rule',
        'primary' => 'id_order_cart_rule',
        'fields' => array(
            'id_order' =>            array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_cart_rule' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order_invoice' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'name' =>                array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true),
            'value' =>                array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'value_tax_excl' =>    array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'free_shipping' =>        array('type' => self::TYPE_BOOL, 'validate' => 'isBool')
        )
    );

    protected $webserviceParameters = array(
        'fields' => array(
            'id_order' => array('xlink_resource' => 'orders'),
        ),
    );
}

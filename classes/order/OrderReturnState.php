<?php
/*
Ramadhani
*/

class OrderReturnStateCore extends ObjectModel
{
    /** @var string Name */
    public $name;

    /** @var string Template name if there is any e-mail to send to customer */
    public $customer_template;

    /** @var string Template name if there is any e-mail to send to admin */
    public $admin_template;

    /** @var string Display state in the specified color */
    public $color;

    /** @var bool Send an e-mail to customer ? */
    public $send_email_to_customer;
    /** @var bool Send an e-mail to superadmin ? */
    public $send_email_to_superadmin;
    /** @var bool Send an e-mail to employee ? */
    public $send_email_to_employee;
    /** @var bool Send an e-mail to hotelier ? */
    public $send_email_to_hotelier;

    /** @var bool order refund denied ? */
    public $denied;

    /** @var bool order refunded ? */
    public $refunded;

    public $module_name;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'order_return_state',
        'primary' => 'id_order_return_state',
        'multilang' => true,
        'fields' => array(
            'color' => array('type' => self::TYPE_STRING, 'validate' => 'isColor'),
            'denied' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'refunded' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'send_email_to_customer' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'send_email_to_superadmin' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'send_email_to_employee' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'send_email_to_hotelier' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'module_name' => array('type' => self::TYPE_STRING),

            /* Lang fields */
            'name' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'customer_template' =>    array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isTplName', 'size' => 64),
            'admin_template' =>    array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isTplName', 'size' => 64),
        ),
    );

    const ORDER_RETRUN_FIRST_STATUS = 1;

    const ORDER_RETURN_STATE_FLAG_REFUNDED = 1;
    const ORDER_RETURN_STATE_FLAG_DENIED = 2;

    /**
    * Get all available order statuses
    *
    * @param int $id_lang Language id for status name
    * @return array Order statuses
    */
    public static function getOrderReturnStates($id_lang)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'order_return_state` ors
		LEFT JOIN `'._DB_PREFIX_.'order_return_state_lang` orsl ON (ors.`id_order_return_state` = orsl.`id_order_return_state` AND orsl.`id_lang` = '.(int)$id_lang.')
		ORDER BY ors.`id_order_return_state` ASC');
    }
}

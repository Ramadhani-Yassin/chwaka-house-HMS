<?php
/**
 * 2007-2016 PrestaShop
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dashactivity extends Module
{
    protected static $colors = array('#1F77B4', '#FF7F0E', '#2CA02C');

    protected $push_filename;

    public function __construct()
    {
        $this->name = 'dashactivity';
        $this->tab = 'dashboard';
        $this->version = '1.0.4';
        $this->author = 'PrestaShop';
        $this->push_filename = _PS_CACHE_DIR_.'push/activity';
        $this->allow_push = true;
        $this->push_time_limit = 180;

        parent::__construct();
        $this->displayName = $this->l('Dashboard Activity');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7.0.99');
    }

    public function install()
    {
        Configuration::updateValue('DASHACTIVITY_CART_ACTIVE', 30);
        Configuration::updateValue('DASHACTIVITY_CART_ABANDONED_MIN', 24);
        Configuration::updateValue('DASHACTIVITY_CART_ABANDONED_MAX', 48);
        Configuration::updateValue('DASHACTIVITY_VISITOR_ONLINE', 30);

        return (parent::install()
            && $this->registerHook('dashboardZoneOne')
            && $this->registerHook('dashboardData')
            && $this->registerHook('actionObjectOrderAddAfter')
            && $this->registerHook('actionObjectCustomerAddAfter')
            && $this->registerHook('actionObjectCustomerMessageAddAfter')
            && $this->registerHook('actionObjectCustomerThreadAddAfter')
            && $this->registerHook('actionObjectOrderReturnAddAfter')
            && $this->registerHook('actionAdminControllerSetMedia')
        );
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (get_class($this->context->controller) == 'AdminDashboardController') {
            if (method_exists($this->context->controller, 'addJquery')) {
                $this->context->controller->addJquery();
            }

            $this->context->controller->addJs($this->_path.'views/js/'.$this->name.'.js');
            $this->context->controller->addJs(
                array(
                    _PS_JS_DIR_.'date.js',
                    _PS_JS_DIR_.'tools.js'
                    ) // retro compat themes 1.5
                );
            $this->context->controller->addCSS($this->_path.'views/css/'.$this->name.'.css');
        }
    }

    public function hookDashboardZoneOne($params)
    {
        $this->context->smarty->assign($this->getConfigFieldsValues());
        $date_from =  $this->context->employee->stats_date_from;
        $date_to = $this->context->employee->stats_date_to;
        $this->context->smarty->assign(
            array(
                'dashactivity_config_form' => $this->renderConfigForm(),
                'link' => $this->context->link,
                'new_customer_filter_link' => $this->context->link->getAdminLink('AdminCustomers').'&customerFilter_a!date_add[]='.$date_from.'&customerFilter_a!date_add[]='.$date_to
            )
        );

        return $this->display(__FILE__, 'dashboard_zone_one.tpl');
    }

    public function hookDashboardData($params)
    {
        if (Tools::strlen($params['date_from']) == 10) {
            $params['date_from'] .= ' 00:00:00';
        }
        if (Tools::strlen($params['date_to']) == 10) {
            $params['date_to'] .= ' 23:59:59';
        }

        if (Configuration::get('PS_DASHBOARD_SIMULATION')) {
            $days = (strtotime($params['date_to']) - strtotime($params['date_from'])) / 3600 / 24;
            $online_visitor = rand(10, 50);
            $visits = rand(200, 2000) * $days;

            return array(
                'data_value' => array(
                    'pending_orders' => round(rand(0, 5)),
                    'return_exchanges' => round(rand(0, 5)),
                    'abandoned_cart' => round(rand(5, 50)),
                    'products_out_of_stock' => round(rand(1, 10)),
                    'new_messages' => round(rand(1, 10) * $days),
                    'new_customers' => round(rand(1, 5) * $days),
                    'online_visitor' => round($online_visitor),
                    'active_shopping_cart' => round($online_visitor / 10),
                    'new_registrations' => round(rand(1, 5) * $days),
                    'total_suscribers' => round(rand(200, 2000)),
                    'visits' => round($visits),
                    'unique_visitors' => round($visits * 0.6),
                ),
                'data_trends' => array(
                    'orders_trends' => array('way' => 'down', 'value' => 0.42),
                ),
                'data_list_small' => array(
                    'dash_traffic_source' => array(
                        '<i class="icon-circle" style="color:'.self::$colors[0].'"></i> qloapps.com' => round($visits / 2),
                        '<i class="icon-circle" style="color:'.self::$colors[1].'"></i> google.com' => round($visits / 3),
                        '<i class="icon-circle" style="color:'.self::$colors[2].'"></i> Direct Traffic' => round($visits / 4)
                    )
                ),
                'data_chart' => array(
                    'dash_trends_chart1' => array(
                        'chart_type' => 'pie_chart_trends',
                        'data' => array(
                            array(
                                'key' => 'qloapps.com',
                                'y' => round($visits / 2),
                                'color' => self::$colors[0],
                                'percent' => ($visits / 2) ? (Tools::ps_round((100 / 2), 2)) : 0,
                            ),
                            array(
                                'key' => 'google.com',
                                'y' => round($visits / 3),
                                'color' => self::$colors[1],
                                'percent' => ($visits / 3) ? (Tools::ps_round((100 / 3), 2)) : 0,
                            ),
                            array(
                                'key' => 'Direct Traffic',
                                'y' => round($visits / 4),
                                'color' => self::$colors[2],
                                'percent' => ($visits / 4) ? (Tools::ps_round((100 / 4), 2)) : 0,
                            )
                        )
                    )
                )
            );
        }

        $objGoogleAnalytics = Module::isEnabled('qlogoogleanalytics') ? Module::getInstanceByName('qlogoogleanalytics') : false;
        if (Validate::isLoadedObject($objGoogleAnalytics) && $objGoogleAnalytics->isConfigured()) {
            $visits = $unique_visitors = $online_visitor = 0;
            if ($result = $objGoogleAnalytics->requestReportData('', 'ga:visits,ga:visitors', Tools::substr($params['date_from'], 0, 10), Tools::substr($params['date_to'], 0, 10), null, null, 1, 1)) {
                $visits = $result[0]['metrics']['visits'];
                $unique_visitors = $result[0]['metrics']['visitors'];
            }
        } else {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                'SELECT COUNT(*) AS visits, COUNT(DISTINCT `id_guest`) AS unique_visitors
                FROM `'._DB_PREFIX_.'connections`
                WHERE `date_add` BETWEEN "'.pSQL($params['date_from']).'" AND "'.pSQL($params['date_to']).'"
                '.Shop::addSqlRestriction(false)
            );
            extract($row);
        }

        if ($maintenance_ips = Configuration::get('PS_MAINTENANCE_IP')) {
            $maintenance_ips = implode(',', array_map('ip2long', array_map('trim', explode(',', $maintenance_ips))));
        }

        if (Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            $sql = 'SELECT c.id_guest, c.ip_address, c.date_add, c.http_referer
                FROM `'._DB_PREFIX_.'connections` c
                LEFT JOIN `'._DB_PREFIX_.'connections_page` cp ON c.id_connections = cp.id_connections
                INNER JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
                WHERE (g.id_customer IS NULL OR g.id_customer = 0)
                    '.Shop::addSqlRestriction(false, 'c').'
                    AND cp.`time_end` IS NULL
                AND TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', cp.`time_start`)) < 900
                '.($maintenance_ips ? 'AND c.ip_address NOT IN ('.preg_replace('/[^,0-9]/', '', $maintenance_ips).')' : '').'
                GROUP BY c.id_connections
                ORDER BY c.date_add DESC';
        } else {
            $sql = 'SELECT c.id_guest, c.ip_address, c.date_add, c.http_referer, "-" as page
                FROM `'._DB_PREFIX_.'connections` c
                INNER JOIN `'._DB_PREFIX_.'guest` g ON c.id_guest = g.id_guest
                WHERE (g.id_customer IS NULL OR g.id_customer = 0)
                    '.Shop::addSqlRestriction(false, 'c').'
                    AND TIME_TO_SEC(TIMEDIFF(\''.pSQL(date('Y-m-d H:i:00', time())).'\', c.`date_add`)) < 900
                '.($maintenance_ips ? 'AND c.ip_address NOT IN ('.preg_replace('/[^,0-9]/', '', $maintenance_ips).')' : '').'
                ORDER BY c.date_add DESC';
        }
        Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $online_visitor = Db::getInstance()->NumRows();

        // Pending bookings will be those bookings which are not paid yet and not in Canceled|Refunded|Payment error state.
        $pending_orders = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(DISTINCT o.`id_order`)
			FROM `'._DB_PREFIX_.'orders` o
            LEFT JOIN `'._DB_PREFIX_.'htl_booking_detail` hbd ON (hbd.`id_order` = o.`id_order`)
			LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (o.`current_state` = os.`id_order_state`)
			WHERE (o.total_paid - o.total_paid_real) > 0
            AND o.`current_state` NOT IN ('.implode(',', array(
                Configuration::get('PS_OS_CANCELED'),
                Configuration::get('PS_OS_REFUND'),
                Configuration::get('PS_OS_ERROR')
            )).')'.Shop::addSqlRestriction(Shop::SHARE_ORDER).
            (!is_null($params['id_hotel']) ? HotelBranchInformation::addHotelRestriction($params['id_hotel'], 'hbd') : '')
        );

        // Abandoned cart are which are added to the cart between Min and Max hours conditions
        $abandoned_cart = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'cart`
			WHERE `date_upd` BETWEEN "'.pSQL(date('Y-m-d H:i:s', strtotime('-'.(int)Configuration::get('DASHACTIVITY_CART_ABANDONED_MAX').' HOUR'))).'" AND "'.pSQL(date('Y-m-d H:i:s', strtotime('-'.(int)Configuration::get('DASHACTIVITY_CART_ABANDONED_MIN').' HOUR'))).'"
			AND id_cart NOT IN (SELECT id_cart FROM `'._DB_PREFIX_.'orders`)
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
        );

        // pending refunds are the refunds requests which are not denied or refunded yet
        $return_exchanges = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'orders` o
			LEFT JOIN `'._DB_PREFIX_.'order_return` or2 ON o.id_order = or2.id_order
            LEFT JOIN `'._DB_PREFIX_.'order_return_state` ors ON (or2.state = ors.id_order_return_state)
			WHERE (ors.`denied` = 0 AND ors.`refunded` = 0)
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o')
        );

        $products_out_of_stock = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT SUM(IF(IFNULL(stock.quantity, 0) > 0, 0, 1))
			FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON p.id_product = pa.id_product
			'.Product::sqlStock('p', 'pa').'
			WHERE p.active = 1'
        );

        $new_messages = AdminStatsController::getPendingMessages();

        $active_shopping_cart = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'cart`
			WHERE date_upd > "'.pSQL(date('Y-m-d H:i:s', strtotime('-'.(int)Configuration::get('DASHACTIVITY_CART_ACTIVE').' MIN'))).'"
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
        );

        $new_customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'customer`
			WHERE `date_add` BETWEEN "'.pSQL($params['date_from']).'" AND "'.pSQL($params['date_to']).'"
            AND `deleted` = 0
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
        );

        $new_registrations = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'customer`
			WHERE `newsletter_date_add` BETWEEN "'.pSQL($params['date_from']).'" AND "'.pSQL($params['date_to']).'"
			AND newsletter = 1
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
        );

        $total_suscribers = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'customer`
			WHERE newsletter = 1
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
        );
        if (Module::isInstalled('blocknewsletter')) {
            $new_registrations += Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
				SELECT COUNT(*)
				FROM `'._DB_PREFIX_.'newsletter`
				WHERE active = 1
				AND `newsletter_date_add` BETWEEN "'.pSQL($params['date_from']).'" AND "'.pSQL($params['date_to']).'"
				'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
            );
            $total_suscribers += Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
							SELECT COUNT(*)
							FROM `'._DB_PREFIX_.'newsletter`
			WHERE active = 1
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER)
            );
        }

        return array(
            'data_value' => array(
                'pending_orders' => (int)$pending_orders,
                'return_exchanges' => (int)$return_exchanges,
                'abandoned_cart' => (int)$abandoned_cart,
                'products_out_of_stock' => (int)$products_out_of_stock,
                'new_messages' => (int)$new_messages,
                'new_customers' => (int)$new_customers,
                'online_visitor' => (int)$online_visitor,
                'active_shopping_cart' => (int)$active_shopping_cart,
                'new_registrations' => (int)$new_registrations,
                'total_suscribers' => (int)$total_suscribers,
                'visits' => (int)$visits,
                'unique_visitors' => (int)$unique_visitors,
            ),
            'data_trends' => array(
                'orders_trends' => array('way' => 'down', 'value' => 0.42),
            ),
            'data_list_small' => array(
                'dash_traffic_source' => $this->getTrafficSources($params['date_from'], $params['date_to']),
            ),
            'data_chart' => array(
                'dash_trends_chart1' => $this->getChartTrafficSource($params['date_from'], $params['date_to']),
            ),
        );
    }

    protected function getChartTrafficSource($date_from, $date_to)
    {
        $referers = $this->getReferer($date_from, $date_to);
        $return = array('chart_type' => 'pie_chart_trends', 'data' => array());
        $i = 0;

        $totalTraffic = array_sum(array_values($referers));
        foreach ($referers as $referer_name => $n) {
            $return['data'][] = array(
                'key' => $referer_name,
                'y' => $n,
                'color' => self::$colors[$i++],
                'percent' => $n ? (Tools::ps_round($n / $totalTraffic * 100, 2)) : 0,
            );
        }

        return $return;
    }

    protected function getTrafficSources($date_from, $date_to)
    {
        $referrers = $this->getReferer($date_from, $date_to, 3);
        $traffic_sources = array();
        $i = 0;
        foreach ($referrers as $referrer_name => $n) {
            $traffic_sources['<i class="icon-circle" style="color:'.self::$colors[$i++].'"></i> '.$referrer_name] = $n;
        }

        return $traffic_sources;
    }

    protected function getReferer($date_from, $date_to, $limit = 3)
    {
        $objGoogleAnalytics = Module::isEnabled('qlogoogleanalytics') ? Module::getInstanceByName('qlogoogleanalytics') : false;
        if (Validate::isLoadedObject($objGoogleAnalytics) && $objGoogleAnalytics->isConfigured()) {
            $websites = array();
            if ($result = $objGoogleAnalytics->requestReportData(
                'ga:source',
                'ga:visitors',
                Tools::substr($date_from, 0, 10),
                Tools::substr($date_to, 0, 10),
                '-ga:visitors',
                null,
                1,
                $limit
            )) {
                foreach ($result as $row) {
                    $websites[$row['dimensions']['source']] = $row['metrics']['visitors'];
                }
            }
        } else {
            $direct_link = $this->l('Direct link');
            $websites = array($direct_link => 0);

            $result = Db::getInstance()->ExecuteS('
				SELECT http_referer
				FROM '._DB_PREFIX_.'connections
				WHERE date_add BETWEEN "'.pSQL($date_from).'" AND "'.pSQL($date_to).'"
				'.Shop::addSqlRestriction().'
				LIMIT '.(int)$limit
            );
            foreach ($result as $row) {
                if (!isset($row['http_referer']) || empty($row['http_referer'])) {
                    ++$websites[$direct_link];
                } else {
                    $website = preg_replace('/^www./', '', parse_url($row['http_referer'], PHP_URL_HOST));
                    if (!isset($websites[$website])) {
                        $websites[$website] = 1;
                    } else {
                        ++$websites[$website];
                    }
                }
            }
            arsort($websites);
        }

        return $websites;
    }

    public function renderConfigForm()
    {
        $fields_form = array(
            'form' => array(
                'id_form' => 'step_carrier_general',
                'input' => array(),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right submit_dash_config',
                    'reset' => array(
                        'title' => $this->l('Cancel'),
                        'class' => 'btn btn-default cancel_dash_config',
                    )
                )
            ),
        );

        $fields_form['form']['input'][] = array(
            'label' => $this->l('Active cart'),
            'hint' => $this->l('How long (in minutes) a cart is to be considered as active after the last recorded change (default: 30 min).'),
            'name' => 'DASHACTIVITY_CART_ACTIVE',
            'type' => 'select',
            'options' => array(
                'query' => array(
                    array('id' => 15, 'name' => 15),
                    array('id' => 30, 'name' => 30),
                    array('id' => 45, 'name' => 45),
                    array('id' => 60, 'name' => 60),
                    array('id' => 90, 'name' => 90),
                    array('id' => 120, 'name' => 120),
                ),
                'id' => 'id',
                'name' => 'name',
            ),
        );
        $fields_form['form']['input'][] = array(
            'label' => $this->l('Online visitor'),
            'hint' => $this->l('How long (in minutes) a visitor is to be considered as online after their last action (default: 30 min).'),
            'name' => 'DASHACTIVITY_VISITOR_ONLINE',
            'type' => 'select',
            'options' => array(
                'query' => array(
                    array('id' => 15, 'name' => 15),
                    array('id' => 30, 'name' => 30),
                    array('id' => 45, 'name' => 45),
                    array('id' => 60, 'name' => 60),
                    array('id' => 90, 'name' => 90),
                    array('id' => 120, 'name' => 120),
                ),
                'id' => 'id',
                'name' => 'name',
            ),
        );
        $fields_form['form']['input'][] = array(
            'label' => $this->l('Abandoned cart (min)'),
            'hint' => $this->l('How long (in hours) after the last action a cart is to be considered as abandoned (default: 24 hrs).'),
            'name' => 'DASHACTIVITY_CART_ABANDONED_MIN',
            'type' => 'text',
            'suffix' => $this->l('hrs'),
        );
        $fields_form['form']['input'][] = array(
            'label' => $this->l('Abandoned cart (max)'),
            'hint' => $this->l('How long (in hours) after the last action a cart is no longer to be considered as abandoned (default: 48 hrs).'),
            'name' => 'DASHACTIVITY_CART_ABANDONED_MAX',
            'type' => 'text',
            'suffix' => $this->l('hrs'),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDashConfig';
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    // Validation of the configuration form
    public function validateDashConfig($configs)
    {
        $errors = [];

        if (!Validate::isUnsignedInt($configs['DASHACTIVITY_CART_ACTIVE'])
            || !$configs['DASHACTIVITY_CART_ACTIVE']
        ) {
            $errors[] = $this->l('Active cart must be a positive integer.');
        }

        if (!Validate::isUnsignedInt($configs['DASHACTIVITY_VISITOR_ONLINE'])
            || !$configs['DASHACTIVITY_VISITOR_ONLINE']
        ) {
            $errors[] = $this->l('Online visitor must be a positive integer.');
        }

        if (!Validate::isUnsignedFloat($configs['DASHACTIVITY_CART_ABANDONED_MIN'])
            || !$configs['DASHACTIVITY_CART_ABANDONED_MIN']
        ) {
            $errors[] = $this->l('Minimum abandoned cart must be valid hours.');
        }

        if (!Validate::isUnsignedFloat($configs['DASHACTIVITY_CART_ABANDONED_MAX'])
            || !$configs['DASHACTIVITY_CART_ABANDONED_MAX']
        ) {
            $errors[] = $this->l('Maximum abandoned cart must be valid hours.');
        }

        return $errors;
    }

    public function getConfigFieldsValues()
    {
        return array(
            'DASHACTIVITY_CART_ACTIVE' => Tools::getValue('DASHACTIVITY_CART_ACTIVE', Configuration::get('DASHACTIVITY_CART_ACTIVE')),
            'DASHACTIVITY_CART_ABANDONED_MIN' => Tools::getValue('DASHACTIVITY_CART_ABANDONED_MIN', Configuration::get('DASHACTIVITY_CART_ABANDONED_MIN')),
            'DASHACTIVITY_CART_ABANDONED_MAX' => Tools::getValue('DASHACTIVITY_CART_ABANDONED_MAX', Configuration::get('DASHACTIVITY_CART_ABANDONED_MAX')),
            'DASHACTIVITY_VISITOR_ONLINE' => Tools::getValue('DASHACTIVITY_VISITOR_ONLINE', Configuration::get('DASHACTIVITY_VISITOR_ONLINE')),
            'min_due_amount' => ('0.' . str_repeat('0', (Configuration::get('PS_PRICE_DISPLAY_PRECISION') - 1)) . '1')
        );
    }

    public function hookActionObjectCustomerMessageAddAfter($params)
    {
        return $this->hookActionObjectOrderAddAfter($params);
    }

    public function hookActionObjectCustomerThreadAddAfter($params)
    {
        return $this->hookActionObjectOrderAddAfter($params);
    }

    public function hookActionObjectCustomerAddAfter($params)
    {
        return $this->hookActionObjectOrderAddAfter($params);
    }

    public function hookActionObjectOrderReturnAddAfter($params)
    {
        return $this->hookActionObjectOrderAddAfter($params);
    }

    public function hookActionObjectOrderAddAfter($params)
    {
        Tools::changeFileMTime($this->push_filename);
    }
}

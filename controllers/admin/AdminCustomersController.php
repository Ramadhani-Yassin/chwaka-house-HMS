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

/**
 * @property Customer $object
 */
class AdminCustomersControllerCore extends AdminController
{
    protected $delete_mode;

    protected $_defaultOrderBy = 'date_add';
    protected $_defaultOrderWay = 'DESC';
    protected $can_add_customer = true;
    protected static $meaning_status = array();

    public function __construct()
    {
        $this->bootstrap = true;
        // $this->required_database = true;
        // $this->required_fields = array('newsletter','optin');
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->lang = false;
        $this->deleted = true;
        $this->explicitSelect = true;

        $this->allow_export = true;

        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->context = Context::getContext();

        $this->default_form_language = $this->context->language->id;

        $titles_array = array();
        $genders = Gender::getGenders($this->context->language->id);
        foreach ($genders as $gender) {
            /** @var Gender $gender */
            $titles_array[$gender->id_gender] = $gender->name;
        }

        $groups_array = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group) {
            $groups_array[$group['id_group']] = $group['name'];
        }

        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')';
        $this->_join .= ' LEFT JOIN '._DB_PREFIX_.'group_lang grl ON (a.id_default_group = grl.id_group AND grl.id_lang = '.(int)$this->context->language->id.')';
        $this->_join .= ' LEFT JOIN '._DB_PREFIX_.'orders o ON (a.id_customer = o.id_customer)';
        $this->_group = 'GROUP BY a.`id_customer`';

        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'title' => array(
                'title' => $this->l('Social title'),
                'filter_key' => 'a!id_gender',
                'type' => 'select',
                'list' => $titles_array,
                'filter_type' => 'int',
                'order_key' => 'gl!name'
            ),
            'firstname' => array(
                'title' => $this->l('First name'),
                'filter_key' => 'a!firstname'
            ),
            'lastname' => array(
                'title' => $this->l('Last name'),
                'filter_key' => 'a!lastname'
            ),
            'email' => array(
                'title' => $this->l('Email address'),
                'filter_key' => 'a!email'
            ),
        );

        if (Configuration::get('PS_B2B_ENABLE')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'company' => array(
                    'title' => $this->l('Company')
                ),
            ));
        }

        $this->fields_list = array_merge($this->fields_list, array(
            'default_group_name' => array(
                'title' => $this->l('Default Group'),
                'optional' => true,
                'type' => 'select',
                'list' => $groups_array,
                'filter_key' => 'a!id_default_group',
            ),
            'total_orders' => array(
                'title' => $this->l('Number of orders'),
                'type' => 'range',
                'optional' => true,
                'visible_default' => true,
                'havingFilter' => true,
                'order_key' => 'total_orders'
            ),
            'total_spent' => array(
                'title' => $this->l('Sales'),
                'type' => 'price',
                'optional' => true,
                'visible_default' => true,
                'search' => false,
                'havingFilter' => true,
                'align' => 'text-right',
                'badge_success' => true
            ),
            'phone' => array(
                'title' => $this->l('Phone'),
                'filter_key' => 'a!phone',
                'optional' => true,
                'visible_default' => false,
            ),
            'active' => array(
                'title' => $this->l('Enabled'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'a!active',
                'callback' => 'formatStatusAsLabel',
            ),
            'newsletter' => array(
                'title' => $this->l('Newsletter'),
                'align' => 'text-center',
                'type' => 'bool',
                'callback' => 'printNewsIcon',
                'orderby' => false
            ),
            'optin' => array(
                'title' => $this->l('Opt-in'),
                'align' => 'text-center',
                'type' => 'bool',
                'callback' => 'printOptinIcon',
                'orderby' => false
            ),
            'date_add' => array(
                'title' => $this->l('Registration'),
                'type' => 'date',
                'filter_key' => 'a!date_add',
                'align' => 'text-right'
            ),
            'connect' => array(
                'title' => $this->l('Last visit'),
                'type' => 'datetime',
                'search' => false,
                'havingFilter' => true
            ),
            'deleted' => array(
                'title' => $this->l('Banned'),
                'type' => 'bool',
                'displayed' => false,
                'callback' => 'getCustomerStatusLabel',
            ),
            'order_date' => array(
                'title' => $this->l('Order date'),
                'type' => 'date',
                'filter_key' => 'o!date_add',
                'displayed' => false
            )
        ));

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_CUSTOMER;
        parent::__construct();

        $this->_select = '
        a.date_add, gl.name as title, grl.name as default_group_name, COUNT(o.`id_order`) as total_orders,
        o.`date_add` as order_date, SUM(total_paid_real / conversion_rate) as total_spent, (
            SELECT c.date_add FROM '._DB_PREFIX_.'guest g
            LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest
            WHERE g.id_customer = a.id_customer
            ORDER BY c.date_add DESC
            LIMIT 1
        ) as connect';

        // Check if we can add a customer
        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP)) {
            $this->can_add_customer = false;
        }

        self::$meaning_status = array(
            'open' => $this->l('Open'),
            'closed' => $this->l('Closed'),
            'pending1' => $this->l('Pending 1'),
            'pending2' => $this->l('Pending 2')
        );
    }

    public function postProcess()
    {
        if (!$this->can_add_customer && $this->display == 'add') {
            $this->redirect_after = $this->context->link->getAdminLink('AdminCustomers');
        }

        parent::postProcess();
        // Added this to check if the filter for the banned(deleted) is used, since $this->delete = true will not display the deleted customers.
        $prefix = $this->getCookieFilterPrefix();
        $filters = $this->context->cookie->getFamily($prefix.$this->table.'Filter_');
        if (isset($filters[$prefix.$this->table.'Filter_deleted']) && $filters[$prefix.$this->table.'Filter_deleted'] == 1) {
            $this->deleted = false;
        }
    }

    public function formatStatusAsLabel($val, $row)
    {
        if ($val) {
            $str_return = $this->l('Yes');
        } else {
            $str_return = $this->l('No');
        }

        return $str_return;
    }

    public function getCustomerStatusLabel($deleted, $tr)
    {
        if ($deleted == Customer::STATUS_DELETED) {
            return $this->l('Deleted');
        } else if ($deleted == Customer::STATUS_BANNED) {
            return $this->l('Banned');
        }

        return;
    }

    public function initContent()
    {
        if ($this->action == 'select_delete') {
            $this->context->smarty->assign(array(
                'delete_form' => true,
                'url_delete' => htmlentities($_SERVER['REQUEST_URI']),
                'boxes' => $this->boxes,
            ));
        }

        if (!$this->can_add_customer && !$this->display) {
            $this->informations[] = $this->l('You have to select a shop if you want to create a customer.');
        }

        parent::initContent();
    }

    public function initToolbar()
    {
        parent::initToolbar();

        if (!$this->can_add_customer) {
            unset($this->toolbar_btn['new']);
        } elseif (!$this->display && $this->can_import) {
            $this->toolbar_btn['import'] = array(
                'href' => $this->context->link->getAdminLink('AdminImport', true).'&import_type=customers',
                'desc' => $this->l('Import')
            );
        }
    }

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {
        if ($this->action == 'export')  {
            $this->deleted = false;
        }

        parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $id_lang_shop);
        if ($this->_list) {
            foreach ($this->_list as &$row) {
                $row['badge_success'] = $row['total_spent'] > 0;
            }
        }
    }


    public function initToolbarTitle()
    {
        parent::initToolbarTitle();

        switch ($this->display) {
            case '':
            case 'list':
                array_pop($this->toolbar_title);
                $this->toolbar_title[] = $this->l('Manage your Customers');
                break;
            case 'view':
                /** @var Customer $customer */
                if (($customer = $this->loadObject(true)) && Validate::isLoadedObject($customer)) {
                    array_pop($this->toolbar_title);
                    $this->toolbar_title[] = sprintf($this->l('Information about Customer: %s'), Tools::substr($customer->firstname, 0, 1).'. '.$customer->lastname);
                }
                break;
            case 'add':
            case 'edit':
                array_pop($this->toolbar_title);
                /** @var Customer $customer */
                if (($customer = $this->loadObject(true)) && Validate::isLoadedObject($customer)) {
                    $this->toolbar_title[] = sprintf($this->l('Editing Customer: %s'), Tools::substr($customer->firstname, 0, 1).'. '.$customer->lastname);
                } else {
                    $this->toolbar_title[] = $this->l('Creating a new Customer');
                }
                break;
        }

        array_pop($this->meta_title);
        if (count($this->toolbar_title) > 0) {
            $this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display) && $this->can_add_customer) {
            $this->page_header_toolbar_btn['new_customer'] = array(
                'href' => self::$currentIndex.'&addcustomer&token='.$this->token,
                'desc' => $this->l('Add new customer', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initProcess()
    {
        parent::initProcess();

        if (Tools::isSubmit('submitGuestToCustomer') && $this->id_object) {
            if ($this->tabAccess['edit'] === 1) {
                $this->action = 'guest_to_customer';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('changeNewsletterVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === 1) {
                $this->action = 'change_newsletter_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (Tools::isSubmit('changeOptinVal') && $this->id_object) {
            if ($this->tabAccess['edit'] === 1) {
                $this->action = 'change_optin_val';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }
        }

        // When deleting, first display a form to select the type of deletion
        if ($this->action == 'delete' || $this->action == 'bulkdelete') {
            if (Tools::getValue('deleteMode') == 'real' || Tools::getValue('deleteMode') == 'deleted') {
                $this->delete_mode = Tools::getValue('deleteMode');
            } else {
                $this->action = 'select_delete';
            }
        }
    }

    public function renderList()
    {
        if ((Tools::isSubmit('submitBulkdelete'.$this->table) || Tools::isSubmit('delete'.$this->table)) && $this->tabAccess['delete'] === 1) {
            $this->tpl_list_vars = array(
                'delete_customer' => true,
                'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                'POST' => $_POST
            );
        }

        $this->_new_list_header_design = true;

        return parent::renderList();
    }

    public function renderForm()
    {
        /** @var Customer $obj */
        if (!($obj = $this->loadObject(true))) {
            return;
        } else if ($this->object->deleted == Customer::STATUS_DELETED) {
            $this->warnings[] = $this->l('This is a deleted customer and you cannot update the information of a deleted customer.');
        } else if ($this->object->deleted == Customer::STATUS_BANNED) {
            $this->warnings[] = $this->l('This is a banned customer and you cannot update the information of a banned customer.');
        }

        $genders = Gender::getGenders();
        $list_genders = array();
        foreach ($genders as $key => $gender) {
            /** @var Gender $gender */
            $list_genders[$key]['id'] = 'gender_'.$gender->id;
            $list_genders[$key]['value'] = $gender->id;
            $list_genders[$key]['label'] = $gender->name;
        }

        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();

        $groups = Group::getGroups($this->default_form_language, true);
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Customer'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->l('Social title'),
                    'name' => 'id_gender',
                    'required' => false,
                    'class' => 't',
                    'values' => $list_genders
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('First name'),
                    'name' => 'firstname',
                    'required' => true,
                    'col' => '4',
                    'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Last name'),
                    'name' => 'lastname',
                    'required' => true,
                    'col' => '4',
                    'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
                ),
                array(
                    'type' => 'text',
                    'prefix' => '<i class="icon-envelope-o"></i>',
                    'label' => $this->l('Email address'),
                    'name' => 'email',
                    'col' => '4',
                    'required' => true,
                    'autocomplete' => false
                ),
                array(
                    'type' => 'text',
                    'prefix' => '<i class="icon-phone"></i>',
                    'label' => $this->l('Phone'),
                    'name' => 'phone',
                    'col' => '4',
                    'required' =>  Configuration::get('PS_ONE_PHONE_AT_LEAST') ? true : false,
                    'autocomplete' => false
                ),
                array(
                    'type' => 'password',
                    'label' => $this->l('Password'),
                    'name' => 'passwd',
                    'col' => '4',
                    'hint' => ($obj->id ? $this->l('Leave this field blank if there\'s no change.') :
                        sprintf($this->l('Password should be at least %s characters long.'), Validate::PASSWORD_LENGTH))
                ),
                array(
                    'type' => 'birthday',
                    'label' => $this->l('Birthday'),
                    'name' => 'birthday',
                    'options' => array(
                        'days' => $days,
                        'months' => $months,
                        'years' => $years
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enabled'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'hint' => $this->l('Enable or disable customer login.')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Newsletter'),
                    'name' => 'newsletter',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'newsletter_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'newsletter_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'disabled' => (bool)!Configuration::get('PS_CUSTOMER_NWSL'),
                    'hint' => $this->l('This customer will receive your newsletter via email.'),
                    'desc' => (bool)!Configuration::get('PS_CUSTOMER_NWSL') ? sprintf($this->l('This field is disabled as option \'Enable newsletter registration\' is disabled. You can change it from %sPreferences > Customers%s page.'), '<a href="'.$this->context->link->getAdminLink('AdminCustomerPreferences').'" target="_blank">', '</a>') : '',
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Opt-in'),
                    'name' => 'optin',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'optin_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'optin_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                    'disabled' =>  (bool)!Configuration::get('PS_CUSTOMER_OPTIN'),
                    'hint' => $this->l('This customer will receive your ads via email.')
                ),
            )
        );

        // if we add a customer via fancybox (ajax), it's a customer and he doesn't need to be added to the visitor and guest groups
        if (Tools::isSubmit('submitFormAjax')) {
            $visitor_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
            $guest_group = Configuration::get('PS_GUEST_GROUP');
            foreach ($groups as $key => $g) {
                if (in_array($g['id_group'], array($visitor_group, $guest_group))) {
                    unset($groups[$key]);
                }
            }
        }

        $this->fields_form['input'] = array_merge(
            $this->fields_form['input'],
            array(
                array(
                    'type' => 'group',
                    'label' => $this->l('Group access'),
                    'name' => 'groupBox',
                    'values' => $groups,
                    'required' => true,
                    'col' => '6',
                    'hint' => $this->l('Select all the groups that you would like to apply to this customer.')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Default customer group'),
                    'name' => 'id_default_group',
                    'options' => array(
                        'query' => $groups,
                        'id' => 'id_group',
                        'name' => 'name'
                    ),
                    'col' => '4',
                    'hint' => array(
                        $this->l('This group will be the user\'s default group.'),
                        $this->l('Only the discount for the selected group will be applied to this customer.')
                    )
                )
            )
        );

        // if customer is a guest customer, password hasn't to be there
        if ($obj->id && ($obj->is_guest && $obj->id_default_group == Configuration::get('PS_GUEST_GROUP'))) {
            foreach ($this->fields_form['input'] as $k => $field) {
                if ($field['type'] == 'password') {
                    array_splice($this->fields_form['input'], $k, 1);
                }
            }
        }

        if (Configuration::get('PS_B2B_ENABLE')) {
            $risks = Risk::getRisks();

            $list_risks = array();
            foreach ($risks as $key => $risk) {
                /** @var Risk $risk */
                $list_risks[$key]['id_risk'] = (int)$risk->id;
                $list_risks[$key]['name'] = $risk->name;
            }

            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Company'),
                'name' => 'company'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('SIRET'),
                'name' => 'siret'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('APE'),
                'name' => 'ape'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Website'),
                'name' => 'website'
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Allowed outstanding amount'),
                'name' => 'outstanding_allow_amount',
                'hint' => $this->l('Valid characters:').' 0-9',
                'suffix' => $this->context->currency->sign
            );
            $this->fields_form['input'][] = array(
                'type' => 'text',
                'label' => $this->l('Maximum number of payment days'),
                'name' => 'max_payment_days',
                'hint' => $this->l('Valid characters:').' 0-9'
            );
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Risk rating'),
                'name' => 'id_risk',
                'required' => false,
                'class' => 't',
                'options' => array(
                    'query' => $list_risks,
                    'id' => 'id_risk',
                    'name' => 'name'
                ),
            );
        }

        $this->fields_form['submit'] = array(
            'title' => $this->l('Save'),
        );

        if (!Tools::getValue('liteDisplaying')) {
            $this->fields_form['buttons'] = array(
                'save-and-stay' => array(
                    'title' => $this->l('Save and stay'),
                    'name' => 'submitAdd'.$this->table.'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                )
            );
        }

        $birthday = explode('-', $this->getFieldValue($obj, 'birthday'));

        $this->fields_value = array(
            'years' => Tools::getValue('years', $this->getFieldValue($obj, 'birthday') ? $birthday[0] : 0),
            'months' => Tools::getValue('months', $this->getFieldValue($obj, 'birthday') ? $birthday[1] : 0),
            'days' => Tools::getValue('days', $this->getFieldValue($obj, 'birthday') ? $birthday[2] : 0),
        );


        // Added values of object Group
        if (!Validate::isUnsignedId($obj->id)) {
            $customer_groups = array();
        } else {
            $customer_groups = $obj->getGroups();
        }
        $customer_groups_ids = array();
        if (is_array($customer_groups)) {
            foreach ($customer_groups as $customer_group) {
                $customer_groups_ids[] = $customer_group;
            }
        }

        // if empty $carrier_groups_ids : object creation : we set the default groups
        if (empty($customer_groups_ids)) {
            $preselected = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP'));
            $customer_groups_ids = array_merge($customer_groups_ids, $preselected);
        }

        foreach ($groups as $group) {
            $this->fields_value['groupBox_'.$group['id_group']] =
                Tools::getValue('groupBox_'.$group['id_group'], in_array($group['id_group'], $customer_groups_ids));
        }

        if ($back = Tools::getValue('back')) {
            $this->tpl_form_vars['back_url'] = Tools::htmlentitiesDecodeUTF8(Tools::safeOutput(urldecode($back)));
        }

        return parent::renderForm();
    }

    public function beforeAdd($customer)
    {
        $customer->id_shop = $this->context->shop->id;
    }

    public function renderKpis()
    {
        $time = time();
        $kpis = array();

        $helper = new HelperKpi();
        $helper->id = 'box-orders';
        $helper->icon = 'icon-shopping-cart';
        $helper->color = 'color1';
        $helper->title = $this->l('Orders per Customer', null, null, false);
        $helper->subtitle = $this->l('All Time', null, null, false);
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=orders_per_customer';
        $helper->tooltip = $this->l('The average number of orders placed per customer in given period of time.', null, null, false);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-total-frequent-customers';
        $helper->icon = 'icon-star';
        $helper->color = 'color2';
        $helper->title = $this->l('Total Frequent Customers', null, null, false);
        $helper->subtitle = $this->l('1 year', null, null, false);
        $helper->href = $this->context->link->getAdminLink('AdminCustomers').'&submitResetcustomer&submitFiltercustomer=1&customerFilter_total_orders%5B0%5D='.Configuration::get('PS_KPI_FREQUENT_CUSTOMER_NB_ORDERS').'&customerFilter_o%21date_add%5B0%5D='.date('Y-m-d', strtotime('-365 day')).'&customerFilter_o%21date_add%5B1%5D='.date('Y-m-d');
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=total_frequent_customers';
        $helper->tooltip = $this->l('The total number of frequent customers in given period of time.', null, null, false);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-revenue-per-available-customer';
        $helper->icon = 'icon-dollar';
        $helper->color = 'color3';
        $helper->title = $this->l('RevPAC', null, null, false);
        $nbDaysRevPac = Validate::isUnsignedInt(Configuration::get('PS_KPI_REVPAC_NB_DAYS')) ? Configuration::get('PS_KPI_REVPAC_NB_DAYS') : 30;
        if ($nbDaysRevPac == 1) {
            $helper->subtitle = sprintf($this->l('%d Day', null, null, false), (int) $nbDaysRevPac);
        } else {
            $helper->subtitle = sprintf($this->l('%d Days', null, null, false), (int) $nbDaysRevPac);
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=revenue_per_available_customer';
        $helper->tooltip = sprintf($this->l('Revenue per Available Customer (RevPAC) in the last %s day(s).', null, null, false), $nbDaysRevPac);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-total-newsletter-registrations';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color4';
        $helper->title = $this->l('Total Newsletter Registrations', null, null, false);
        $helper->subtitle = $this->l('All Time', null, null, false);
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=total_newsletter_registrations';
        $helper->tooltip = $this->l('The total number of newsletter registrations in given period of time.', null, null, false);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-conversion-rate';
        $helper->icon = 'icon-refresh';
        $helper->color = 'color1';
        $helper->title = $this->l('Conversion Rate', null, null, false);
        $nbDaysConversionRate = Validate::isUnsignedInt(Configuration::get('PS_KPI_CONVERSION_RATE_NB_DAYS')) ? Configuration::get('PS_KPI_CONVERSION_RATE_NB_DAYS') : 30;
        if ($nbDaysConversionRate == 1) {
            $helper->subtitle = $nbDaysConversionRate.' '.$this->l('day', null, null, false);
        } else {
            $helper->subtitle = $nbDaysConversionRate.' '.$this->l('days', null, null, false);
        }
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=conversion_rate';
        $helper->tooltip = sprintf($this->l('The percentage of visitors who created a booking in last %s day(s).', null, null, false), $nbDaysConversionRate);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-total-new-customers';
        $helper->icon = 'icon-plus-circle';
        $helper->color = 'color4';
        $helper->title = $this->l('New Customers', null, null, false);
        $nbDaysNewCustomers = Validate::isUnsignedInt(Configuration::get('PS_KPI_NEW_CUSTOMERS_NB_DAYS')) ? Configuration::get('PS_KPI_NEW_CUSTOMERS_NB_DAYS') : 30;
        if ($nbDaysNewCustomers == 1) {
            $helper->subtitle = sprintf($this->l('%d Day', null, null, false), (int) $nbDaysNewCustomers);
            $date_from = date('Y-m-d');
        } else {
            $helper->subtitle = sprintf($this->l('%d Days', null, null, false), (int) $nbDaysNewCustomers);
            $date_from = date('Y-m-d', strtotime('-'.($nbDaysNewCustomers - 1).' day'));
        }
        $date_to = date('Y-m-d');
        $helper->href = $this->context->link->getAdminLink('AdminCustomers').'&submitResetcustomer&submitFiltercustomer=1&customerFilter_a!date_add[]='.$date_from.'&customerFilter_a!date_add[]='.$date_to;
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=total_new_customers';
        $helper->tooltip = sprintf($this->l('The total number of new customers who registered in last %s day(s).', null, null, false), $nbDaysNewCustomers);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-total-banned-customers';
        $helper->icon = 'icon-ban';
        $helper->color = 'color2';
        $helper->title = $this->l('Banned Customers', null, null, false);
        $helper->subtitle = $this->l('All Time', null, null, false);
        $helper->href = $this->context->link->getAdminLink('AdminCustomers').'&submitResetcustomer&submitFiltercustomer=1&customerFilter_deleted=1';
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=total_banned_customers';
        $helper->tooltip = $this->l('The total number of banned customers.', null, null, false);
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-gender';
        $helper->icon = 'icon-male';
        $helper->color = 'color3';
        $helper->title = $this->l('Customers', null, null, false);
        $helper->subtitle = $this->l('All Time', null, null, false);
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=customer_main_gender';
        $helper->tooltip = $this->l('The main gender from all the customers.', null, null, false);
        $this->kpis[] = $helper;

        return parent::renderKpis();
    }

    public function renderView()
    {
        /** @var Customer $customer */
        if (!($customer = $this->loadObject())) {
            return;
        }

        $this->context->customer = $customer;
        $gender = new Gender($customer->id_gender, $this->context->language->id);
        $gender_image = $gender->getImage();

        $customer_stats = $customer->getStats();
        $sql = 'SELECT SUM(total_paid_real) FROM '._DB_PREFIX_.'orders WHERE id_customer = %d AND valid = 1';
        if ($total_customer = Db::getInstance()->getValue(sprintf($sql, $customer->id))) {
            $sql = 'SELECT SQL_CALC_FOUND_ROWS COUNT(*) FROM '._DB_PREFIX_.'orders WHERE valid = 1 AND id_customer != '.(int)$customer->id.' GROUP BY id_customer HAVING SUM(total_paid_real) > %d';
            Db::getInstance()->getValue(sprintf($sql, (int)$total_customer));
            $count_better_customers = (int)Db::getInstance()->getValue('SELECT FOUND_ROWS()') + 1;
        } else {
            $count_better_customers = '-';
        }

        $orders = Order::getCustomerOrders($customer->id, true);
        $total_orders = count($orders);
        for ($i = 0; $i < $total_orders; $i++) {
            $orders[$i]['total_paid_real_not_formated'] = $orders[$i]['total_paid_real'];
            $orders[$i]['total_paid_real'] = Tools::displayPrice($orders[$i]['total_paid_real'], new Currency((int)$orders[$i]['id_currency']));
        }

        $messages = CustomerThread::getCustomerMessages((int)$customer->id);

        $total_messages = count($messages);
        for ($i = 0; $i < $total_messages; $i++) {
            $messages[$i]['message'] = substr(strip_tags(html_entity_decode($messages[$i]['message'], ENT_NOQUOTES, 'UTF-8')), 0, 75);
            $messages[$i]['date_add'] = Tools::displayDate($messages[$i]['date_add'], null, true);
            if (isset(self::$meaning_status[$messages[$i]['status']])) {
                $messages[$i]['status'] = self::$meaning_status[$messages[$i]['status']];
            }
        }

        $groups = $customer->getGroups();
        $total_groups = count($groups);
        for ($i = 0; $i < $total_groups; $i++) {
            $group = new Group($groups[$i]);
            $groups[$i] = array();
            $groups[$i]['id_group'] = $group->id;
            $groups[$i]['name'] = $group->name[$this->default_form_language];
        }

        $total_ok = 0;
        $total_ko = 0;
        $orders_ok = array();
        $orders_ko = array();
        foreach ($orders as $order) {
            if (!isset($order['order_state'])) {
                $order['order_state'] = $this->l('There is no status defined for this order.');
            }

            if ($order['valid']) {
                $orders_ok[] = $order;
                $total_ok += $order['total_paid_real_not_formated']/$order['conversion_rate'];
            } else {
                $orders_ko[] = $order;
                $total_ko += $order['total_paid'] / $order['conversion_rate'];
            }
        }

        $purchasedServices = array();
        $purchasedRoomTypes = array();
        if ($products = $customer->getBoughtProducts()) {
            foreach ($products as $product) {
                if ($product['is_booking_product']) {
                    $purchasedRoomTypes[] = $product;
                } else {
                    $purchasedServices[] = $product;
                }
            }
        }

        $carts = Cart::getCustomerCarts($customer->id);
        $total_carts = count($carts);
        for ($i = 0; $i < $total_carts; $i++) {
            $cart = new Cart((int)$carts[$i]['id_cart']);
            $this->context->cart = $cart;
            $currency = new Currency((int)$carts[$i]['id_currency']);
            $this->context->currency = $currency;
            $summary = $cart->getSummaryDetails();
            $carrier = new Carrier((int)$carts[$i]['id_carrier']);
            $carts[$i]['id_cart'] = sprintf('%06d', $carts[$i]['id_cart']);
            $carts[$i]['date_add'] = Tools::displayDate($carts[$i]['date_add'], null, true);
            $carts[$i]['total_price'] = Tools::displayPrice($summary['total_price'], $currency);
            $carts[$i]['name'] = $carrier->name;
        }

        $this->context->currency = Currency::getDefaultCurrency();

        $sql = 'SELECT DISTINCT cp.id_product, c.id_cart, c.id_shop, cp.id_shop AS cp_id_shop
				FROM '._DB_PREFIX_.'cart_product cp
				JOIN '._DB_PREFIX_.'cart c ON (c.id_cart = cp.id_cart)
				JOIN '._DB_PREFIX_.'product p ON (cp.id_product = p.id_product)
                AND p.booking_product=1
				WHERE c.id_customer = '.(int)$customer->id.'
					AND NOT EXISTS (
							SELECT 1
							FROM '._DB_PREFIX_.'orders o
							JOIN '._DB_PREFIX_.'order_detail od ON (o.id_order = od.id_order)
							WHERE product_id = cp.id_product AND o.valid = 1 AND o.id_customer = '.(int)$customer->id.'
						)';
        $interested = Db::getInstance()->executeS($sql);
        $total_interested = count($interested);
        for ($i = 0; $i < $total_interested; $i++) {
            $product = new Product($interested[$i]['id_product'], false, $this->default_form_language, $interested[$i]['id_shop']);
            if (!Validate::isLoadedObject($product)) {
                continue;
            }
            $interested[$i]['url'] = $this->context->link->getProductLink(
                $product->id,
                $product->link_rewrite,
                Category::getLinkRewrite($product->id_category_default, $this->default_form_language),
                null,
                null,
                $interested[$i]['cp_id_shop']
            );
            $interested[$i]['id'] = (int)$product->id;
            $interested[$i]['name'] = Tools::htmlentitiesUTF8($product->name);
        }

        $emails = $customer->getLastEmails();

        $connections = $customer->getLastConnections();
        if (!is_array($connections)) {
            $connections = array();
        }
        $total_connections = count($connections);
        for ($i = 0; $i < $total_connections; $i++) {
            $connections[$i]['http_referer'] = $connections[$i]['http_referer'] ? preg_replace('/^www./', '', parse_url($connections[$i]['http_referer'], PHP_URL_HOST)) : $this->l('Direct link');
        }

        $referrers = Referrer::getReferrers($customer->id);
        $total_referrers = count($referrers);
        for ($i = 0; $i < $total_referrers; $i++) {
            $referrers[$i]['date_add'] = Tools::displayDate($referrers[$i]['date_add'], null, true);
        }

        $customerLanguage = new Language($customer->id_lang);
        $shop = new Shop($customer->id_shop);

        $objCustomerGuestDetail = new CustomerGuestDetail();
        $customerGuests = $objCustomerGuestDetail->getCustomerGuestsByIdCustomer($customer->id);
        $this->tpl_view_vars = array(
            'customer' => $customer,
            'gender' => $gender,
            'gender_image' => $gender_image,
            // General information of the customer
            'registration_date' => Tools::displayDate($customer->date_add, null, true),
            'customer_stats' => $customer_stats,
            'last_visit' => Tools::displayDate($customer_stats['last_visit'], null, true),
            'count_better_customers' => $count_better_customers,
            'shop_is_feature_active' => Shop::isFeatureActive(),
            'name_shop' => $shop->name,
            'customer_birthday' => Tools::displayDate($customer->birthday),
            'last_update' => Tools::displayDate($customer->date_upd, null, true),
            'customer_exists' => Customer::customerExists($customer->email),
            'id_lang' => $customer->id_lang,
            'customerLanguage' => $customerLanguage,
            // Add a Private note
            'customer_note' => Tools::htmlentitiesUTF8($customer->note),
            // Messages
            'messages' => $messages,
            // Groups
            'groups' => $groups,
            // Orders
            'orders' => $orders,
            'orders_ok' => $orders_ok,
            'orders_ko' => $orders_ko,
            'total_ok' => Tools::displayPrice($total_ok, $this->context->currency->id),
            'total_ko' => Tools::displayPrice($total_ko, $this->context->currency->id),
            // Products
            'products' => $products,
            'purchasedRoomTypes' => $purchasedRoomTypes,
            'purchasedServices' => $purchasedServices,
            // Addresses
            'addresses' => $customer->getAddresses($this->default_form_language),
            // Discounts
            'discounts' => CartRule::getCustomerCartRules($this->default_form_language, $customer->id, false, false),
            // Carts
            'carts' => $carts,
            // Interested
            'interested' => $interested,
            // Emails
            'emails' => $emails,
            // Connections
            'connections' => $connections,
            // Referrers
            'referrers' => $referrers,
            'show_toolbar' => true,
            'customer_guests' => $customerGuests,
        );
        return parent::renderView();
    }

    public function processDelete()
    {
        // If customer is going to be deleted permanently then if customer has orders the change this customer as an anonymous customer
        if (Validate::isLoadedObject($objCustomer = $this->loadObject())) {
            if ($this->delete_mode == 'real' && Order::getCustomerOrders($objCustomer->id, true)) {
                $customerEmail = $objCustomer->email;
                $objCustomer->email = 'anonymous'.'-'.$objCustomer->id.'@'.Tools::link_rewrite(Configuration::get('PS_SHOP_NAME')).'_anonymous.com';
                $objCustomer->deleted = Customer::STATUS_DELETED;
                if (!$objCustomer->update()) {
                    $this->errors[] = Tools::displayError('Some error ocurred while deleting the Customer');
                    return;
                } else {
                    if ($idCustomerGuest = CustomerGuestDetail::getCustomerGuestByEmail($customerEmail, false)) {
                        $objCustomerGuestDetail = new CustomerGuestDetail($idCustomerGuest);
                        $objCustomerGuestDetail->phone = preg_replace('/[0-9]/', '0', $objCustomer->phone);
                        $objCustomerGuestDetail->email = $objCustomer->email;
                        $objCustomerGuestDetail->save();
                    }
                }

                $this->redirect_after = self::$currentIndex.'&conf=1&token='.$this->token;

                return;
            }
        } else {
            $this->errors[] = Tools::displayError('Customer not found.');
            return;
        }

        $this->_setDeletedMode();
        parent::processDelete();
    }

    protected function _setDeletedMode()
    {
        if ($this->delete_mode == 'real') {
            $this->deleted = false;
        } elseif ($this->delete_mode == 'deleted') {
            $this->deleted = true;
        } else {
            $this->errors[] = Tools::displayError('Unknown delete mode:').' '.$this->deleted;
            return;
        }
    }

    public function processExport($text_delimiter = '"')
    {
        $this->fields_list['newsletter']['callback'] = 'formatStatusAsLabel';
        $this->fields_list['optin']['callback'] = 'formatStatusAsLabel';

        return parent::processExport($text_delimiter);
    }

    protected function processBulkDelete()
    {
        // If customer is going to be deleted permanently then if customer has orders the change this customer as an anonymous customer
        if ($this->delete_mode == 'real') {
            if (is_array($this->boxes) && !empty($this->boxes)) {
                foreach ($this->boxes as $key => $idCustomer) {
                    if (Validate::isLoadedObject($objCustomer = new Customer($idCustomer))) {
                        // check if customer has orders for email change else customer will be deleted
                        if (Order::getCustomerOrders($objCustomer->id, true)) {
                            $objCustomer->email = 'anonymous'.'-'.$objCustomer->id.'@'.Tools::getShopDomain();
                            $objCustomer->deleted = Customer::STATUS_DELETED;
                            if ($objCustomer->update()) {
                                // unset the customer which is processed
                                // not processed customers will be deleted with default process if no errors are there
                                unset($this->boxes[$key]);
                            } else {
                                $this->errors[] = Tools::displayError('Some error ocurred while deleting the Customer with id').': '.$idCustomer;
                            }
                        }
                    } else {
                        $this->errors[] = Tools::displayError('Customer id').': '.$idCustomer.' '.Tools::displayError('not found.');
                    }
                }

                // if all the customers are process above then redirect with success
                if (!count($this->boxes)) {
                    $this->redirect_after = self::$currentIndex.'&conf=1&token='.$this->token;
                    return;
                }
            } else {
                $this->errors[] = Tools::displayError('Customers not found.');
                return;
            }

            // if errors are there then do not proceed for default process
            if (count($this->errors)) {
                return;
            }
        }

        $this->_setDeletedMode();
        parent::processBulkDelete();
    }

    public function processAdd()
    {
        if (Tools::getValue('submitFormAjax')) {
            $this->redirect_after = false;
        }
        // Check that the new email is not already in use
        $customer_email = trim(strval(Tools::getValue('email')));
        $customer = new Customer();
        if (trim(Tools::getValue('passwd')) == '') {
            $_POST['passwd'] = md5(time()._COOKIE_KEY_);
        }

        if (Validate::isEmail($customer_email)) {
            $customer->getByEmail($customer_email);
            if ($customer->id) {
                $this->errors[] = Tools::displayError('An account already exists for this email address:').' '.$customer_email;
                $this->display = 'edit';
                return $customer;
            } elseif (Customer::customerExists($customer_email, false, false)) {
                $this->errors[] = Tools::displayError('The email is already associated with a banned account. Please use a different one.');
                $this->display = 'edit';
            } elseif ($customer = parent::processAdd()) {
                $this->context->smarty->assign('new_customer', $customer);
                return $customer;
            }
        } else {
            $this->errors[] = Tools::displayError('Invalid email address.');
            $this->display = 'edit';
        }

        return false;
    }


    public function processUpdate()
    {
        if (Validate::isLoadedObject($this->object)) {
            if ($this->object->deleted == Customer::STATUS_DELETED) {
                $this->errors[] = $this->l('You can not update a deleted customer.');
                return false;
            } else if ($this->object->deleted == Customer::STATUS_BANNED) {
                $this->errors[] = $this->l('You can not update a banned customer.');
                return false;
            }

            $customer_email = trim(strval(Tools::getValue('email')));

            // check if e-mail already used
            if ($customer_email != $this->object->email) {
                $customer = new Customer();
                if (Validate::isEmail($customer_email)) {
                    $customer->getByEmail($customer_email);
                } else {
                    $this->errors[] = Tools::displayError('Invalid email address.');
                }

                if (($customer->id) && ($customer->id != (int)$this->object->id)) {
                    $this->errors[] = Tools::displayError('An account already exists for this email address:').' '.$customer_email;
                }
            }

            return parent::processUpdate();
        } else {
            $this->errors[] = Tools::displayError('An error occurred while loading the object.').'
				<b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        }
    }

    public function processSave()
    {
        // Check that default group is selected
        if (!is_array(Tools::getValue('groupBox')) || !in_array(Tools::getValue('id_default_group'), Tools::getValue('groupBox'))) {
            $this->errors[] = Tools::displayError('A default customer group must be selected in group box.');
        }

        $days = Tools::getValue('days');
        $months = Tools::getValue('months');
        $years = Tools::getValue('years');

        if ($days || $months || $years) {
            if (!$days) {
                $this->errors[] = Tools::displayError("Please select a valid date of birthday");
            }
            if (!$months) {
                $this->errors[] = Tools::displayError("Please select a valid year of birthday");
            }
            if (!$years) {
                $this->errors[] = Tools::displayError("Please select a valid month of birthday");
            }
        }

        $phone = Tools::getValue('phone');
        if (Configuration::get('PS_ONE_PHONE_AT_LEAST')) {
            if ($phone == '') {
                $this->errors[] = Tools::displayError('Phone number is required.');
            }
        }

        $customer = new Customer();
        $this->errors = array_merge($this->errors, $customer->validateFieldsRequiredDatabase());

        return parent::processSave();
    }

    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);

        $years = Tools::getValue('years');
        $months = Tools::getValue('months');
        $days = Tools::getValue('days');

        if ($years != '' && $months != '' && $days != '') {
            $object->birthday = (int) $years.'-'.(int) $months.'-'.(int) $days;
        } else {
            $object->birthday = '0000-00-00';
        }
    }

    protected function afterDelete($object, $old_id)
    {
        $customer = new Customer($old_id);
        $addresses = $customer->getAddresses($this->default_form_language);
        foreach ($addresses as $k => $v) {
            $address = new Address($v['id_address']);
            $address->id_customer = $object->id;
            $address->save();
        }
        return true;
    }
    /**
     * Transform a guest account into a registered customer account
     */
    public function processGuestToCustomer()
    {
        $customer = new Customer((int)Tools::getValue('id_customer'));
        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = Tools::displayError('This customer does not exist.');
        }
        if (Customer::customerExists($customer->email)) {
            $this->errors[] = Tools::displayError('This customer already exists as a non-guest.');
        } elseif ($customer->transformToCustomer(Tools::getValue('id_lang', $this->context->language->id))) {
            if ($id_order = (int)Tools::getValue('id_order')) {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders').'&id_order='.$id_order.'&vieworder&conf=3');
            } else {
                Tools::redirectAdmin(self::$currentIndex.'&'.$this->identifier.'='.$customer->id.'&viewcustomer&conf=3&token='.$this->token);
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while updating customer information.');
        }
    }

    /**
     * Toggle the newsletter flag
     */
    public function processChangeNewsletterVal()
    {
        $customer = new Customer($this->id_object);
        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = Tools::displayError('An error occurred while updating customer information.');
        }
        $customer->newsletter = $customer->newsletter ? 0 : 1;
        if (!$customer->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating customer information.');
        }
        Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
    }

    /**
     * Toggle newsletter optin flag
     */
    public function processChangeOptinVal()
    {
        $customer = new Customer($this->id_object);
        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = Tools::displayError('An error occurred while updating customer information.');
        }
        $customer->optin = $customer->optin ? 0 : 1;
        if (!$customer->update()) {
            $this->errors[] = Tools::displayError('An error occurred while updating customer information.');
        }
        Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
    }

    public function printNewsIcon($value, $customer)
    {
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab=AdminCustomers&id_customer='
            .(int)$customer['id_customer'].'&changeNewsletterVal&token='.Tools::getAdminTokenLite('AdminCustomers')).'">
				'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
            '</a>';
    }

    public function printOptinIcon($value, $customer)
    {
        return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab=AdminCustomers&id_customer='
            .(int)$customer['id_customer'].'&changeOptinVal&token='.Tools::getAdminTokenLite('AdminCustomers')).'">
				'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
            '</a>';
    }

    /**
     * @param string $token
     * @param int $id
     * @param string $name
     * @return mixed
     */
    public function displayDeleteLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');

        $customer = new Customer($id);
        $name = $customer->lastname.' '.$customer->firstname;
        $name = '\n\n'.$this->l('Name:', 'helper').' '.$name;

        $tpl->assign(array(
            'href' => self::$currentIndex.'&'.$this->identifier.'='.$id.'&delete'.$this->table.'&token='.($token != null ? $token : $this->token),
            'confirm' => $this->l('Delete the selected item?').$name,
            'action' => $this->l('Delete'),
            'id' => $id,
        ));

        return $tpl->fetch();
    }

    /**
     * add to $this->content the result of Customer::SearchByName
     * (encoded in json)
     *
     * @return void
     */
    public function ajaxProcessSearchCustomers()
    {
        $searches = explode(' ', Tools::getValue('customer_search'));
        $skip_deleted = Tools::getValue('skip_deleted');
        $customers = array();
        $searches = array_unique($searches);
        foreach ($searches as $search) {
            if (!empty($search) && $results = Customer::searchByName($search, 50, $skip_deleted)) {
                foreach ($results as $result) {
                    if ($result['active']) {
                        $customers[$result['id_customer']] = $result;
                    }
                }
            }
        }

        if (count($customers)) {
            $to_return = array(
                'customers' => $customers,
                'found' => true
            );
        } else {
            $to_return = array('found' => false);
        }

        $this->content = json_encode($to_return);
    }

    /**
     * Uodate the customer note
     *
     * @return void
     */
    public function ajaxProcessUpdateCustomerNote()
    {
        if ($this->tabAccess['edit'] === 1) {
            $note = Tools::htmlentitiesDecodeUTF8(Tools::getValue('note'));
            $customer = new Customer((int)Tools::getValue('id_customer'));
            if (!Validate::isLoadedObject($customer)) {
                die('error:update');
            }
            if (!empty($note) && !Validate::isCleanHtml($note)) {
                die('error:validation');
            }
            $customer->note = $note;
            if (!$customer->update()) {
                die('error:update');
            }
            die('ok');
        }
    }

    public function ajaxProcessVerifyCustomerEmail()
    {
        $response = array('status' => true);
        $idCustomer = Tools::getValue('id_customer', 0);
        if (($email = trim(strval(Tools::getValue('email'))))
            && ($idCustomerByEmail = Customer::customerExists($email, true, false))
            && (Validate::isLoadedObject($objCustomer = new Customer($idCustomerByEmail)))
            && $idCustomerByEmail != $idCustomer // the admin is trying to add/update the account
        ) {
            $response['status'] = false;
            if ($objCustomer->deleted) {
                $response['msg'] = Tools::displayError('This email is already associated with a banned account. Please use a different one.');
            } else {
                $response['msg'] = Tools::displayError('An account already exists for this email address.');
            }
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessInitGuestModal()
    {
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objCustomerGuestDetail = new CustomerGuestDetail((int) Tools::getValue('id_customer_guest_detail')))) {
            $this->context->smarty->assign(
                array(
                    'genders' => Gender::getGenders(),
                    'customerGuestDetail' => $objCustomerGuestDetail,
                )
            );
            $modal = array(
                'modal_id' => 'customer-guest-modal',
                'modal_class' => 'customer_guest_modal',
                'modal_title' => '<i class="icon icon-user"></i> &nbsp'.$this->l('Guest details'),
                'modal_content' => $this->context->smarty->fetch('controllers/customers/modals/_customer_guest_form.tpl'),
                'modal_actions' => array(
                    array(
                        'type' => 'button',
                        'value' => 'submitGuestInfo',
                        'class' => 'submitGuestInfoInfo btn-primary pull-right',
                        'label' => '<i class="icon-user"></i> '.$this->l('Save Guest'),
                    ),
                ),
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        $this->ajaxDie(json_encode($response));
    }


    public function ajaxProcessUpdateGuestDetails()
    {
        $response = array('hasError' => 0, 'errors' => array());
        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            if (Validate::isLoadedObject($objCustomerGuestDetail = new CustomerGuestDetail((int) Tools::getValue('id_customer_guest_detail')))) {
                $response['errors'] = $objCustomerGuestDetail->validateController();
                if (!Tools::getValue('lastname')) {
                    $response['errors']['lastname'] = Tools::displayError('lastname is required');
                }
                if (!Tools::getValue('firstname')) {
                    $response['errors']['firstname'] = Tools::displayError('firstname is required');
                }
                if (!Tools::getValue('email')) {
                    $response['errors']['email'] = Tools::displayError('email is required');
                }
                if (!Tools::getValue('phone')) {
                    $response['errors']['phone'] = Tools::displayError('phone is required');
                }
                if (!$response['errors']) {
                    $objCustomerGuestDetail->id_gender = Tools::getValue('id_gender');
                    $objCustomerGuestDetail->firstname = Tools::getValue('firstname');
                    $objCustomerGuestDetail->lastname = Tools::getValue('lastname');
                    $objCustomerGuestDetail->phone = Tools::getValue('phone');
                    if ($objCustomerGuestDetail->save()) {
                        $gender = new Gender($objCustomerGuestDetail->id_gender, $this->context->language->id);
                        $response['data']['gender'] = $gender->name;
                        $response['data']['firstname'] = $objCustomerGuestDetail->firstname;
                        $response['data']['lastname'] = $objCustomerGuestDetail->lastname ;
                        $response['data']['email'] = $objCustomerGuestDetail->email;
                        $response['data']['phone'] = $objCustomerGuestDetail->phone;
                        $response['data']['id'] = $objCustomerGuestDetail->id;
                        $response['msg'] = $this->l('Guest details are updated.');
                    } else {
                        $response['errors'][] = Tools::displayError('Unable to save guest details.');
                    }
                }
            } else {
                $response['errors'][] = Tools::displayError('Guest details not found.');
            }
        } else {
            $response['errors'][] = Tools::displayError('You do not have permission to edit this.');
        }

        if ($response['errors']) {
            $response['hasError'] = 1;
            $this->context->smarty->assign('errors', $response['errors']);
            $response['errorsHtml'] = $this->context->smarty->fetch('alerts.tpl');
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDeleteGuest()
    {
        $response = array('hasError' => 1, 'errors' => array());
        // Check tab access is allowed to edit
        if ($this->tabAccess['delete'] === 1) {
            if (Validate::isLoadedObject($objCustomerGuestDetail = new CustomerGuestDetail((int) Tools::getValue('id_customer_guest_detail')))) {
                if ($objCustomerGuestDetail->delete()) {
                    $response['hasError'] = false;
                    $response['msg'] = $this->l('Successful deletion.');
                } else {
                    $response['msg'] = $this->l('Unable to delete guest details.');
                }
            } else {
                $response['msg'] = $this->l('Guest details not found.');
            }
        } else {
            $response['msg'] = $this->l('You do not have permission to delete this.');
        }

        $this->ajaxDie(json_encode($response));
    }

    public function setMedia()
    {
        parent::setMedia();
        if ($this->loadObject(true)
            && ($this->display == 'edit' || $this->display == 'add' || $this->display == 'view')
        ) {
            $idCustomer = $this->object->id ? $this->object->id : 0;
            Media::addJSDef(
                array(
                    'customer_controller_url' => self::$currentIndex.'&token='.$this->token,
                    'id_customer' => $idCustomer,
                    'txtSomeErr' => $this->l('Some error occurred. Please try again.', null, true),
                    'confirmTxt' => $this->l('Are you sure you want to delete this guest details?', null, true)
                )
            );
            $this->addJS(_PS_JS_DIR_.'admin/customers.js');
        }
    }
}

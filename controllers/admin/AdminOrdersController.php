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
 * @property Order $object
 */
class AdminOrdersControllerCore extends AdminController
{
    public $toolbar_title;

    protected $statuses_array = array();
    protected $all_order_sources = array();
    protected $hotelsArray = array();
    protected $roomTypesArray = array();
    protected $roomsArray = array();
    protected $orderCurrenciesArray = array();


    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'order';
        $this->className = 'Order';
        $this->lang = false;
        $this->addRowAction('view');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = '
        IF('.((Configuration::get('PS_ORDER_LIST_PRICE_DISPLAY_CURRENCY') == Order::ORDER_LIST_PRICE_DISPLAY_IN_DEFAULT_CURRENCY)? 1 : 0).', (a.total_paid_tax_incl / a.conversion_rate), total_paid_tax_incl) AS total_paid_tax_incl,
        IF('.((Configuration::get('PS_ORDER_LIST_PRICE_DISPLAY_CURRENCY') == Order::ORDER_LIST_PRICE_DISPLAY_IN_DEFAULT_CURRENCY)? 1 : 0).', ((a.total_paid - a.total_paid_real) / a.conversion_rate), (a.total_paid - a.total_paid_real)) AS amount_due,
        a.source AS order_source,
        a.id_currency,
        a.id_order AS id_pdf,
        CONCAT(c.`firstname`, \' \', c.`lastname`) AS `customer`,
        osl.`name` AS `osname`, os.`color`,
        cu.iso_code AS currency,
        IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer AND so.id_order < a.id_order LIMIT 1) > 0, 0, 1) as new,
        IF(a.valid, 1, 0) badge_success,
        hbil.`hotel_name`,
        (SELECT COUNT(hbd.`id`) FROM `'._DB_PREFIX_.'htl_booking_detail` hbd WHERE hbd.`id_order` = a.`id_order`) as num_rooms,
        (SELECT SUM(hbd.`adults`) + SUM(hbd.`children`) FROM `'._DB_PREFIX_.'htl_booking_detail` hbd WHERE hbd.`id_order` = a.`id_order`) as total_guests_count,
        (SELECT CONCAT(
            SUM(hbd.`adults`),
            \' '.$this->l('Adult(s)').' \',
            IF(SUM(hbd.`children`), CONCAT(SUM(hbd.`children`), \' '.$this->l('Children').'\'), \'\')
        ) FROM `'._DB_PREFIX_.'htl_booking_detail` hbd WHERE hbd.`id_order` = a.`id_order`) as total_guests,
        (SELECT SUM(DATEDIFF(hbd.`date_to`, hbd.`date_from`)) FROM `'._DB_PREFIX_.'htl_booking_detail` hbd WHERE hbd.`id_order` = a.`id_order`) as los,
        hbd.`id_room` AS id_room_information,
        (SELECT COUNT(spod.`id_service_product_order_detail`) FROM `'._DB_PREFIX_.'service_product_order_detail` spod WHERE spod.`id_order` = a.`id_order` AND spod.`id_htl_booking_detail`=0) as num_products';

        $this->_join = '
        LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
        LEFT JOIN `'._DB_PREFIX_.'currency` cu ON (cu.`id_currency` = a.`id_currency`)
        LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = a.`current_state`)
        LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int) $this->context->language->id.')
        LEFT JOIN `'._DB_PREFIX_.'htl_booking_detail` hbd ON (hbd.`id_order` = a.`id_order`)
        LEFT JOIN `'._DB_PREFIX_.'service_product_order_detail` spod ON (spod.`id_order` = a.`id_order`)
        LEFT JOIN `'._DB_PREFIX_.'htl_branch_info_lang` hbil ON (IF(hbd.`id_hotel`, (hbil.`id` = hbd.`id_hotel`), (hbil.`id` = spod.`id_hotel`)))';

        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        $this->_group = ' GROUP BY a.`id_order`';

        // if request for resolvable overbooked orders filter comes the set condition for this
        if (Tools::getIsset('resolvable_overbooked_orders') && Tools::getValue('resolvable_overbooked_orders')) {
            $objHotelBookingDetail = new HotelBookingDetail();
            $resolvableOrders = array();
            // Overbookings information of the order
            if ($orderOverBookings = $objHotelBookingDetail->getOverbookedRooms()) {
                foreach ($orderOverBookings as $overBookedRoom) {
                    if (!in_array($overBookedRoom['id_order'], $resolvableOrders)) {
                        $resolvableOrders[$overBookedRoom['id_order']] = $overBookedRoom['id_order'];
                    }
                }
            }
            $this->_where = ' AND a.`id_order` IN ('.implode(',', $resolvableOrders).')';
        }


        $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }
        $all_order_sources = Db::getInstance()->executeS('SELECT DISTINCT(`source`) FROM  `'._DB_PREFIX_.'orders`');
        foreach ($all_order_sources as $source) {
            $this->all_order_sources[$source['source']] = $source['source'];
        }

        $hotelsArray = HotelBranchInformation::getProfileAccessedHotels($this->context->employee->id_profile, 1);
        foreach ($hotelsArray as $hotel) {
            $addressInfo = HotelBranchInformation::getAddress($hotel['id_hotel']);
            $this->hotelsArray[$hotel['id_hotel']] = $hotel['hotel_name'].', '.$addressInfo['city'];
        }

        $roomTypesArray = array();
        $objHotelRoomType = new HotelRoomType();
        foreach ($hotelsArray as $hotel) {
            $hotelRoomTypes = $objHotelRoomType->getRoomTypeByHotelId($hotel['id_hotel'], $this->context->language->id);
            foreach ($hotelRoomTypes as $room_type) {
                $this->roomTypesArray[$room_type['id_product']] = $room_type['room_type'].', '.$hotel['hotel_name'];
            }
        }

        $roomsArray = array();
        $hotelRooms = HotelRoomInformation::getHotelRoomsInfo();
        foreach ($hotelRooms as $hotelRoom) {
            $this->roomsArray[$hotelRoom['id']] = $hotelRoom['room_num'].', '.$hotelRoom['room_type_name'].', '.$hotelRoom['hotel_name'];
        }

        $orderCurrencies = Db::getInstance()->executeS('SELECT DISTINCT(`id_currency`) FROM `'._DB_PREFIX_.'orders`');
        foreach ($orderCurrencies as $orderCurrency) {
            $this->orderCurrenciesArray[$orderCurrency['id_currency']] = Currency::getCurrencyInstance($orderCurrency['id_currency'])->name;
        }

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ),
            'reference' => array(
                'title' => $this->l('Reference')
            ),
            'customer' => array(
                'title' => $this->l('Customer'),
                'havingFilter' => true,
                'optional' => true,
                'visible_default' => true
            ),
            'hotel_name' => array(
                'title' => $this->l('Hotel'),
                'type' => 'select',
                'filter_key' => 'hbd!id_hotel',
                'list' => $this->hotelsArray,
                'optional' => true,
                'class' => 'chosen',
                'visible_default' => true
            ),
            'room_type_name' => array(
                'title' => $this->l('Room type'),
                'type' => 'select',
                'filter_key' => 'hbd!id_product',
                'list' => $this->roomTypesArray,
                'optional' => false,
                'class' => 'chosen',
                'visible_default' => false,
                'displayed' => false,
            ),
            'id_room_information' => array(
                'title' => $this->l('Rooms'),
                'type' => 'select',
                'filter_key' => 'hbd!id_room',
                'list' => $this->roomsArray,
                'optional' => false,
                'class' => 'chosen',
                'visible_default' => false,
                'displayed' => false,
            ),
            'date_from' => array(
                'title' => $this->l('Check-in'),
                'filter_key' => 'hbd!date_from',
                'type'=>'date',
                'displayed' => false,
            ),
            'date_to' => array(
                'title' => $this->l('Check-out'),
                'filter_key' => 'hbd!date_to',
                'type'=>'date',
                'displayed' => false,
            ),
            'total_guests' => array(
                'title' => $this->l('Guests'),
                'type' => 'range',
                'filter_key' => 'total_guests_count',
                'optional' => true,
                'havingFilter' => true,
            ),
            'num_rooms' => array(
                'title' => $this->l('No. of rooms'),
                'align' => 'text-center',
                'type' => 'range',
                'optional' => true,
                'havingFilter' => true,
                'visible_default' => true
            ),
            'los' => array(
                'title' => $this->l('Stay period'),
                'align' => 'text-center',
                'type' => 'range',
                'havingFilter' => true,
                'optional' => true,
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
            'amount_due' => array(
                'title' => $this->l('Due Amount'),
                'align' => 'text-right',
                'type' => 'range',
                'validation' => 'isFloat',
                'currency' => true,
                'havingFilter' => true,
                'callback' => 'setOrderCurrency',
                'badge_success' => true,
                'optional' => true,
                'visible_default' => true
            ),
            'id_currency' => array(
                'title' => $this->l('Order Currency'),
                'align' => 'text-center',
                'type' => 'select',
                'multiple' => true,
                'operator' => 'or',
                'filter_key' => 'a!id_currency',
                'list' => $this->orderCurrenciesArray,
                'displayed' => false,
            ),
            'total_paid_tax_incl' => array(
                'title' => $this->l('Order Total'),
                'align' => 'text-right',
                'type' => 'range',
                'validation' => 'isFloat',
                'currency' => true,
                'callback' => 'setOrderCurrency',
                'badge_success' => true,
            ),
            'payment' => array(
                'title' => $this->l('Payment')
            ),
            'order_source' => array(
                'title' => $this->l('Order Source'),
                'type' => 'select',
                'filter_key' => 'a!source',
                'list' => $this->all_order_sources,
                'optional' => true,
                'visible_default' => true
            ),
            'osname' => array(
                'title' => $this->l('Status'),
                'type' => 'select',
                'multiple' => true,
                'operator' => 'or',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'order_key' => 'osname',
                'optional' => true,
                'visible_default' => true
            ),
            'date_add' => array(
                'title' => $this->l('Order date'),
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
                'optional' => true,
                'visible_default' => true
            ),
            'id_pdf' => array(
                'title' => $this->l('PDF'),
                'align' => 'text-center',
                'callback' => 'printPDFIcons',
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
                'optional' => true,
            ),
            'is_refunded' => array(
                'title' => $this->l('Refunded / Cancelled Rooms'),
                'filter_key' => 'hbd!is_refunded',
                'type'=>'bool',
                'displayed' => false,
            ),
            'id_status' => array(
                'title' => $this->l('Room status'),
                'filter_key' => 'hbd!id_status',
                'displayed' => false,
                'type' => 'select',
                'list' => array(
                    HotelBookingDetail::STATUS_ALLOTED => $this->l('Alloted'),
                    HotelBookingDetail::STATUS_CHECKED_IN => $this->l('Checked in'),
                    HotelBookingDetail::STATUS_CHECKED_OUT => $this->l('Checked out'),
                ),
            )
        ));

        $this->shopLinkType = 'shop';
        $this->shopShareDatas = Shop::SHARE_ORDER;

        if (Tools::isSubmit('id_order')) {
            // Save context (in order to apply cart rule)
            $order = new Order((int)Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }

        $this->bulk_actions = array(
            'updateOrderStatus' => array('text' => $this->l('Change Order Status'), 'icon' => 'icon-refresh')
        );

        // START send access query information to the admin controller
        $this->access_select = ' SELECT a.`id_order` FROM '._DB_PREFIX_.'orders a';
        $this->access_join = ' INNER JOIN '._DB_PREFIX_.'htl_booking_detail hbd ON (hbd.id_order = a.id_order)';
        if ($acsHtls = HotelBranchInformation::getProfileAccessedHotels($this->context->employee->id_profile, 1, 1)) {
            $this->access_where = ' WHERE hbd.id_hotel IN ('.implode(',', $acsHtls).')';
        }

        parent::__construct();

        // overbooking success status
        $this->_conf[51] = $this->l('Overbooking is successfully resolved');
        $this->_conf[52] = $this->l('Room in the booking is successfully reallocated');
        $this->_conf[53] = $this->l('Room in the booking is successfully swapped');
    }

    public static function setOrderCurrency($echo, $row)
    {
        if (Configuration::get('PS_ORDER_LIST_PRICE_DISPLAY_CURRENCY') == Order::ORDER_LIST_PRICE_DISPLAY_IN_DEFAULT_CURRENCY) {
            $idCurrency = Configuration::get('PS_CURRENCY_DEFAULT');
        } else {
            $idCurrency = $row['id_currency'];
        }
        return Tools::displayPrice($echo, (int)$idCurrency);
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_order'] = array(
                'href' => self::$currentIndex.'&addorder&token='.$this->token,
                'desc' => $this->l('Add new order', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderForm()
    {
        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->errors[] = $this->l('You have to select a shop before creating new orders.');
        }

        // check page is new order creation page and cart id is set
        if (Tools::isSubmit('addorder') && ($id_cart = Tools::getValue('cart_id'))) {
            // set smarty variables if new order creation process has errors
            $cart = new Cart($id_cart);
            $cart_order_exists = $cart->orderExists();
            if (!$cart_order_exists) {
                $this->context->cart = $cart;
                $this->context->currency = new Currency((int)$cart->id_currency);

                // validate cart for removing invalid data from cart before new order creation
                // remove not linked services with room types from cart if not allowed to book them
                $this->errors = array_merge(
                    $this->errors,
                    HotelCartBookingData::validateCartBookings(!Configuration::get('PS_ALLOW_ADD_ALL_SERVICES_IN_BOOKING'))
                );

                $cart_detail_data = array();
                $cart_detail_data_obj = new HotelCartBookingData();
                $objServiceProductCartDetail = new ServiceProductCartDetail();
                if ($cart_detail_data = $cart_detail_data_obj->getCartFormatedBookinInfoByIdCart((int) $id_cart)) {
                    $objRoomType = new HotelRoomType();
                    foreach ($cart_detail_data as $key => $cart_data) {
                        $cart_detail_data[$key]['room_type_info'] = $objRoomType->getRoomTypeInfoByIdProduct($cart_data['id_product']);
                    }
                    $this->context->smarty->assign('cart_detail_data', $cart_detail_data);
                }
                if ($cartHotelProduct = $objServiceProductCartDetail->getServiceProductsInCart($this->context->cart->id)) {
                    $this->context->smarty->assign('cart_hotel_product_data', $cartHotelProduct);
                }

                if (empty($cart_detail_data) && empty($cartHotelProduct)) {
                    // if no rooms added in the cart and user visits add order page then redirect to BOOK NOW page
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminHotelRoomsBooking'));
                }

                $payment_modules = array();
                foreach (PaymentModule::getInstalledPaymentModules() as $p_module) {
                    $payment_modules[] = Module::getInstanceById((int)$p_module['id_module']);
                }

                $paymentTypes = array();
                foreach ($this->getPaymentsTypes() as $paymentType) {
                    if ($paymentType['value'] != OrderPayment::PAYMENT_TYPE_REMOTE_PAYMENT) {
                        $paymentTypes[] = $paymentType;
                    }
                }

                $occupancyRequiredForBooking = false;
                if (Configuration::get('PS_BACKOFFICE_ROOM_BOOKING_TYPE') == HotelBookingDetail::PS_ROOM_UNIT_SELECTION_TYPE_OCCUPANCY) {
                    $occupancyRequiredForBooking = true;
                }

                $objHotelAdvancedPayment = new HotelAdvancedPayment();
                $this->context->smarty->assign(array(
                    'order_total' => $cart->getOrderTotal(true),
                    'is_advance_payment_active' => $objHotelAdvancedPayment->isAdvancePaymentAvailableForCurrentCart(),
                    'advance_payment_amount_without_tax' => $cart->getOrderTotal(false, Cart::ADVANCE_PAYMENT),
                    'advance_payment_amount_with_tax' => $cart->getOrderTotal(true, Cart::ADVANCE_PAYMENT),
                    'cart' => $cart,
                    'currencies' => Currency::getCurrenciesByIdShop(Context::getContext()->shop->id),
                    'langs' => Language::getLanguages(true, Context::getContext()->shop->id),
                    'payment_modules' => $payment_modules,
                    'payment_types' => $paymentTypes,
                    'PAYMENT_TYPE_PAY_AT_HOTEL' => OrderPayment::PAYMENT_TYPE_PAY_AT_HOTEL,
                    'currency' => new Currency((int)$cart->id_currency),
                    'max_child_in_room' => Configuration::get('WK_GLOBAL_MAX_CHILD_IN_ROOM'),
                    'max_child_age' => Configuration::get('WK_GLOBAL_CHILD_MAX_AGE'),
                    'occupancy_required_for_booking' => $occupancyRequiredForBooking,
                ));

            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminHotelRoomsBooking'));
            }
            $this->context->smarty->assign('is_order_created', $cart_order_exists);
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHotelRoomsBooking'));
        }

        //end
        parent::renderForm();
        unset($this->toolbar_btn['save']);
        $this->addJqueryPlugin(array('autocomplete', 'fancybox', 'typewatch'));

        $this->context->smarty->assign(array(
            'recyclable_pack' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
            'gift_wrapping' => (int)Configuration::get('PS_GIFT_WRAPPING'),
            'show_toolbar' => $this->show_toolbar,
            'toolbar_btn' => $this->toolbar_btn,
            'toolbar_scroll' => $this->toolbar_scroll,
            'PS_CATALOG_MODE' => Configuration::get('PS_CATALOG_MODE'),
            'title' => array($this->l('Orders'), $this->l('Create order')),
        ));

        $this->content .= $this->createTemplate('form.tpl')->fetch();
    }

    public function initToolbar()
    {
        if ($this->display == 'view') {
            /** @var Order $order */
            if (Validate::isLoadedObject($order = $this->loadObject())) {
                // hotel link in header
                if ($idHotel = HotelBookingDetail::getIdHotelByIdOrder($order->id)) {
                    $this->toolbar_btn['hotel'] = array(
                        'href' => $this->context->link->getAdminLink('AdminAddHotel').'&id='.$idHotel.'&updatehtl_branch_info',
                        'desc' => $this->l('View Hotel'),
                        'class' => 'icon-building',
                        'target' => true,
                    );
                }

                if (Configuration::get('PS_INVOICE') && $order->hasInvoice() && !$this->lite_display) {
                    $this->toolbar_btn['file'] = array(
                        'short' => $this->l('Invoice'),
                        'href' => $this->context->link->getAdminLink('AdminPdf').'&submitAction=generateInvoicePDF&id_order='.$order->id,
                        'desc' => $this->l('View invoice'),
                        'class' => 'icon-file-text',
                        'target' => true,
                    );
                }

                $this->toolbar_btn['print'] = array(
                    'short' => $this->l('Print'),
                    'href' => 'javascript:window.print()',
                    'desc' => $this->l('Print order'),
                    'class' => 'icon-print',
                );

                if ($this->tabAccess['edit'] === 1) {
                    if (((int) $order->isReturnable())
                        && !$order->hasCompletelyRefunded(Order::ORDER_COMPLETE_CANCELLATION_OR_REFUND_REQUEST_FLAG, 0, 0)
                    ) {
                        $orderTotalPaid = $order->getTotalPaid();
                        $orderDiscounts = $order->getCartRules();
                        $hasOrderDiscountOrPayment = ((float)$orderTotalPaid > 0 || $orderDiscounts) ? true : false;
                        $this->toolbar_btn['cancel'] = array(
                            'short' => ($hasOrderDiscountOrPayment) ? $this->l('Refund') : $this->l('Cancel'),
                            'id' => 'desc-order-standard_refund',
                            'desc' => ($hasOrderDiscountOrPayment) ? $this->l('Initiate refund') : $this->l('Cancel bookings'),
                            'class' => 'icon-exchange',
                            'target' => true,
                        );
                    }
                }
            }
        }

        $res = parent::initToolbar();
        if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP && isset($this->toolbar_btn['new']) && Shop::isFeatureActive()) {
            unset($this->toolbar_btn['new']);
        }
        return $res;
    }

    public function initToolbarTitle()
    {
        parent::initToolbarTitle();
        if ($this->display == 'add') {
            array_pop($this->toolbar_title);
            $this->toolbar_title[] = $this->l('Add new');
        } elseif ($this->display != 'view') {
            array_pop($this->toolbar_title);
            $this->toolbar_title[] = $this->l('Orders');
        }
    }

    public function ajaxProcessInitBookingDocumentsModal()
    {
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $this->context->smarty->assign(
                array(
                    'can_edit' => $this->tabAccess['edit'],
                    'current_id_lang' => $this->context->language->id,
                )
            );
            // set modal details
            $modal = array(
                'modal_id' => 'booking-documents-modal',
                'modal_class' => 'modal-md order_detail_modal',
                'modal_title' => '<i class="icon icon-file-text"></i> &nbsp'.$this->l('Documents'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_booking_documents.tpl'),
            );

            if ($this->tabAccess['edit'] === 1) {
                $modal['modal_actions'][] = array(
                    'type' => 'button',
                    'value' => 'submitDocument',
                    'class' => 'btn-primary pull-right submitDocument',
                    'label' => '<i class="icon-cloud-upload"></i> '.$this->l('Upload'),
                );
            }

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitOrderDocumentNoteModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $this->context->smarty->assign(
                array(
                    'current_index' => self::$currentIndex,
                    'order' => $objOrder,
                )
            );

            $modal = array(
                'modal_id' => 'document-note-modal',
                'modal_class' => 'order_detail_modal',
                'modal_title' => '<i class="icon icon-file"></i> &nbsp'.$this->l('Note'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_document_note.tpl'),
            );

            if ($this->tabAccess['edit'] === 1) {
                $modal['modal_actions'][] = array(
                    'type' => 'button',
                    'value' => 'submitDocumentNote',
                    'class' => 'submitDocumentNote btn-primary pull-right',
                    'label' => '<i class="icon-file"></i> '.$this->l('Save Note'),
                );
            }

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitVoucherModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $this->context->smarty->assign(
                array(
                    'current_index' => self::$currentIndex,
                    'order' => $objOrder,
                    'currency' => new Currency($objOrder->id_currency),
                    'invoices_collection' => $objOrder->getInvoicesCollection(),
                    'current_id_lang' => $this->context->language->id
                )
            );

            $modal = array(
                'modal_id' => 'voucher-modal',
                'modal_class' => 'order_detail_modal',
                'modal_title' => '<i class="icon icon-tag"></i> &nbsp'.$this->l('Voucher'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_discount_form.tpl'),
                'modal_actions' => array(
                    array(
                        'type' => 'button',
                        'value' => 'submitVoucher',
                        'class' => 'submitVoucher btn-primary pull-right',
                        'label' => '<i class="icon-tag"></i> '.$this->l('Add Voucher'),
                    ),
                ),
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitOrderPaymentModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $payment_methods = array();
            foreach (PaymentModule::getInstalledPaymentModules() as $payment) {
                $module = Module::getInstanceByName($payment['name']);
                if (Validate::isLoadedObject($module) && $module->active) {
                    $payment_methods[] = $module->displayName;
                }
            }

            // Set the order currency at first element of currencies
            if ($currencies = Currency::getCurrenciesByIdShop($objOrder->id_shop)) {
                foreach ($currencies as $key => $currency) {
                    if ($currency['id_currency'] == $objOrder->id_currency) {
                        unset($currencies[$key]);
                        array_unshift($currencies, $currency);
                        break;
                    }
                }
            }

            $this->context->smarty->assign(
                array(
                    'current_index' => self::$currentIndex,
                    'order' => $objOrder,
                    'currencies' => $currencies,
                    'payment_methods' => $payment_methods,
                    'payment_types' => $this->getPaymentsTypes(),
                    'invoices_collection' => $objOrder->getInvoicesCollection(),
                    'current_id_lang' => $this->context->language->id,
                )
            );
            $modal = array(
                'modal_id' => 'order-payment-modal',
                'modal_class' => 'order_detail_modal',
                'modal_title' => '<i class="icon icon-credit-card"></i> &nbsp'.$this->l('Order Payment'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_order_payment_form.tpl'),
                'modal_actions' => array(
                    array(
                        'type' => 'button',
                        'value' => 'submitOrderPayment',
                        'class' => 'submitOrderPayment btn-primary pull-right',
                        'label' => '<i class="icon-credit-card"></i> '.$this->l('Add Payment'),
                    ),
                ),
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitTravellerModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            // get details if booking is done for some other guest
            $customerGuestDetail = false;
            if ($id_customer_guest_detail = OrderCustomerGuestDetail::isCustomerGuestBooking($objOrder->id)) {
                $customerGuestDetail = new OrderCustomerGuestDetail($id_customer_guest_detail);
                $customerGuestDetail->gender = new Gender($customerGuestDetail->id_gender, $this->context->language->id);
            }

            if ($customerGuestDetail) {
                $this->context->smarty->assign(
                    array(
                        'genders' => Gender::getGenders(),
                        'customerGuestDetail' => $customerGuestDetail,
                    )
                );
                $modal = array(
                    'modal_id' => 'traveller-modal',
                    'modal_class' => 'order_detail_modal',
                    'modal_title' => '<i class="icon icon-user"></i> &nbsp'.$this->l('Traveller'),
                    'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_traveller_form.tpl'),
                    'modal_actions' => array(
                        array(
                            'type' => 'button',
                            'value' => 'submitTravellerInfo',
                            'class' => 'submitTravellerInfo btn-primary pull-right',
                            'label' => '<i class="icon-user"></i> '.$this->l('Save Traveller'),
                        ),
                    ),
                );

                $this->context->smarty->assign($modal);
                $response['hasError'] = 0;
                $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
            }
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitOrderPaymentDetailModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $modal = array(
                'modal_id' => 'payment-detail-modal',
                'modal_class' => 'order_detail_modal',
                'modal_title' => '<i class="icon icon-credit-card"></i> &nbsp'.$this->l('Payment Detail'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_order_payment_detail.tpl'),
                'modal_actions' => array(),
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitRoomStatusModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $htlOrderStatus = HotelBookingDetail::getAllHotelOrderStatus();
            $this->context->smarty->assign(
                array(
                    'order' => $objOrder,
                    'current_index' => self::$currentIndex,
                    'hotel_order_status' => $htlOrderStatus,
                )
            );
            $modal = array(
                'modal_id' => 'room-status-modal',
                'modal_class' => 'order_detail_modal',
                'modal_title' => '<i class="icon-bed"></i> &nbsp'.$this->l('Guest CheckIn - Checkout'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_room_status_form.tpl'),
                'modal_actions' => array(
                    array(
                        'type' => 'button',
                        'value' => 'submitRoomStatus',
                        'class' => 'submitRoomStatus btn-primary pull-right',
                        'label' => '<i class="icon-bed"></i> '.$this->l('Update Status'),
                    ),
                ),
            );

            $this->context->smarty->assign($modal);

            $response['hasError'] = 0;
            $response['STATUS_ALLOTED'] = HotelBookingDetail::STATUS_ALLOTED;
            $response['STATUS_CHECKED_IN'] = HotelBookingDetail::STATUS_CHECKED_IN;
            $response['STATUS_CHECKED_OUT'] = HotelBookingDetail::STATUS_CHECKED_OUT;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitRoomAllotmentCommentModal()
    {
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objBookingDetail = new HotelBookingDetail(Tools::getValue('id_hotel_booking')))) {
            if ($objBookingDetail->booking_type == HotelBookingDetail::ALLOTMENT_MANUAL) {
                // get booking information by order
                $this->context->smarty->assign('comment', $objBookingDetail->comment);

                $title = $objBookingDetail->room_num.' - '.$objBookingDetail->room_type_name.' ('.
                Tools::displayDate($objBookingDetail->date_from).' - '.Tools::displayDate($objBookingDetail->date_to).')';
                $modal = array(
                    'modal_id' => 'room-allotment-comment-modal',
                    'modal_class' => 'modal-md order_detail_modal',
                    'modal_title' => '<i class="icon icon-comment"></i> &nbsp'.$title,
                    'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_room_allotment_comment.tpl'),
                    'modal_actions' => array(),
                );

                $this->context->smarty->assign($modal);
                $response['hasError'] = 0;
                $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
            }
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitRoomReallocationModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $htlOrderStatus = HotelBookingDetail::getAllHotelOrderStatus();
            $this->context->smarty->assign(
                array(
                    'order' => $objOrder,
                    'currency' => new Currency($objOrder->id_currency),
                    'reallocate_form_action' => $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.$objOrder->id,
                )
            );
            $modal = array(
                'modal_id' => 'room-reallocation-modal',
                'modal_class' => 'modal-md order_detail_modal',
                'modal_title' => '<i class="icon icon-refresh"></i> &nbsp'.$this->l('Room Reallocation / Swap'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_room_reallocation.tpl'),
                'modal_actions' => null,
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitAddRoomBookingModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $this->context->smarty->assign(
                array(
                    'order' => $objOrder,
                    'invoices_collection' => $objOrder->getInvoicesCollection(),
                    'currency' => new Currency($objOrder->id_currency),
                    'max_child_age' => Configuration::get('WK_GLOBAL_CHILD_MAX_AGE'),
                )
            );
            $modal = array(
                'modal_id' => 'add-room-booking-modal',
                'modal_class' => 'modal-md order_detail_modal',
                'modal_title' => '<i class="icon icon-bed"></i> &nbsp'.$this->l('Add Room'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_add_room_booking.tpl'),
                'modal_actions' => array(
                    array(
                        'type' => 'button',
                        'value' => 'submitAddRoom',
                        'class' => 'submitAddRoom btn-primary pull-right',
                        'label' => '<i class="icon-bed"></i> '.$this->l('Add Room'),
                    ),
                ),
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitEditRoomBookingModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))
            && ($idProduct = Tools::getValue('id_product'))
            && ($idOrder = Tools::getValue('id_order'))
            && ($idRoom = Tools::getValue('id_room'))
            && ($dateFrom = Tools::getValue('date_from'))
            && ($dateTo = Tools::getValue('date_to'))
            && ($productLineData = Tools::getValue('product_line_data'))
        ) {
            $productLineData = json_decode($productLineData, true);
            if (Validate::isLoadedObject($objOrderDetail = new OrderDetail($productLineData['id_order_detail']))) {
                $productLineData['id_order_invoice'] = $objOrderDetail->id_order_invoice;
                $smartyVars = array(
                        'order' => $objOrder,
                        'data' => $productLineData,
                        'currency' => new Currency($objOrder->id_currency),
                        'max_child_age' => Configuration::get('WK_GLOBAL_CHILD_MAX_AGE'),
                        'orderEdit' => 1,
                );

                $objCurrency = new Currency($objOrder->id_currency);
                $smartyVars['orderCurrency'] = $objOrder->id_currency;
                $smartyVars['currencySign'] = $objCurrency->sign;
                $smartyVars['link'] = $this->context->link;

                $smartyVars['id_booking_detail'] = $productLineData['id'];

                $objBookingDemand = new HotelBookingDemands();

                // set context currency So that we can get prices in the order currency
                $this->context->currency = $objCurrency;

                if ($extraDemands = $objBookingDemand->getRoomTypeBookingExtraDemands(
                    $idOrder,
                    0,
                    0,
                    0,
                    0,
                    1,
                    0,
                    1,
                    $productLineData['id']
                )) {
                    $smartyVars['extraDemands'] = $extraDemands;
                }

                // get room type additional demands
                $objRoomDemands = new HotelRoomTypeDemand();
                if ($roomTypeDemands = $objRoomDemands->getRoomTypeDemands($idProduct)) {
                    foreach ($roomTypeDemands as &$demand) {
                        // if demand has advance options then set demand price as first advance option price.
                        if (isset($demand['adv_option']) && $demand['adv_option']) {
                            $demand['price'] = current($demand['adv_option'])['price'];
                        }
                    }
                    $smartyVars['roomTypeDemands'] = $roomTypeDemands;
                }

                $objServiceProductOrderDetail = new ServiceProductOrderDetail();
                if ($additionalServices = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    null,
                    null,
                    null,
                    0,
                    $productLineData['id']
                )) {
                    $smartyVars['additionalServices'] = $additionalServices[$productLineData['id']];
                }

                // get room type additional demands
                if (Configuration::get('PS_ALLOW_ADD_ALL_SERVICES_IN_BOOKING')) {
                    // get all services
                    $objProduct = new Product();
                    $hotelServiceProducts = $objProduct->getServiceProducts(true, Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE);
                    $roomTypeServiceProducts = $objProduct->getServiceProducts(true, Product::SELLING_PREFERENCE_WITH_ROOM_TYPE);
                    $serviceProducts = array_merge($roomTypeServiceProducts, $hotelServiceProducts);
                } else {
                    $objRoomTypeServiceProduct = new RoomTypeServiceProduct();
                    $serviceProducts = $objRoomTypeServiceProduct->getServiceProductsData($idProduct, 1, 0, false, 2, null);
                }

                if ($serviceProducts) {
                    foreach ($serviceProducts as $key => $servProduct) {
                        $serviceProducts[$key]['price_tax_exc'] = $servProduct['price'];
                        if (isset($additionalServices[$productLineData['id']])
                            && in_array($servProduct['id_product'], array_column($additionalServices[$productLineData['id']]['additional_services'], 'id_product'))
                        ) {
                            unset($serviceProducts[$key]);
                        }
                    }

                    $smartyVars['serviceProducts'] = $serviceProducts;
                }

                $objOrderReturn = new OrderReturn();
                $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($objOrder->id, 0, 1);
                $smartyVars['refundReqBookings'] = $refundReqBookings;

                // check if booking room type tax rule is still exist
                $objHotelBookingDetail = new HotelBookingDetail((int) $productLineData['id']);
                $objOrderDetail = new OrderDetail($objHotelBookingDetail->id_order_detail);
                if (Validate::isLoadedObject($objTaxRuleGroup = new TaxRulesGroup($objOrderDetail->id_tax_rules_group))) {
                    $smartyVars['roomTypeTaxRuleGroupExist'] = 1;
                } else {
                    $smartyVars['roomTypeTaxRuleGroupExist'] = 0;
                }

                $taxRulesGroups = TaxRulesGroup::getTaxRulesGroups(true);
                $smartyVars['taxRulesGroups'] = $taxRulesGroups;
                $smartyVars['invoices_collection'] = $objOrder->getInvoicesCollection();
                $smartyVars['customServiceAllowed'] = Configuration::get('PS_ALLOW_CREATE_CUSTOM_SERVICES_IN_BOOKING');
                $smartyVars['current_id_lang'] = $this->context->language->id;

                $this->context->smarty->assign($smartyVars);

                $modal = array(
                    'modal_id' => 'edit-room-booking-modal',
                    'modal_class' => 'modal-lg order_detail_modal',
                    'modal_title' => '<i class="icon icon-bed"></i> &nbsp'.$this->l('Edit Room'),
                    'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_edit_room_booking.tpl'),
                );

                $this->context->smarty->assign($modal);
                $response['hasError'] = 0;
                $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
            }
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitCancelRoomBookingModal()
    {
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            // get booking information by order
            $objBookingDetail = new HotelBookingDetail();
            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
            $objOrderReturn = new OrderReturn();
            $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($objOrder->id, 0, 1, 0, 1);
            if ($bookingOrderInfo = $objBookingDetail->getBookingDataByOrderId($objOrder->id)) {
                $objBookingDemand = new HotelBookingDemands();
                foreach($bookingOrderInfo as $key => $booking) {
                    if ((in_array($booking['id'], $refundReqBookings)) || $booking['is_refunded']) {
                        unset($bookingOrderInfo[$key]);
                    } else {
                        $bookingOrderInfo[$key]['total_price_tax_incl'] += $objBookingDemand->getRoomTypeBookingExtraDemands(
                            $objOrder->id,
                            $booking['id_product'],
                            $booking['id_room'],
                            $booking['date_from'],
                            $booking['date_to'],
                            0,
                            1,
                            1,
                            $booking['id']
                        );
                        $bookingOrderInfo[$key]['total_price_tax_excl'] += $objBookingDemand->getRoomTypeBookingExtraDemands(
                            $objOrder->id,
                            $booking['id_product'],
                            $booking['id_room'],
                            $booking['date_from'],
                            $booking['date_to'],
                            0,
                            1,
                            0,
                            $booking['id']
                        );
                        $bookingOrderInfo[$key]['total_price_tax_incl'] += $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            1,
                            1,
                            null,
                            null,
                            0,
                            $booking['id']
                        );
                        $bookingOrderInfo[$key]['total_price_tax_excl'] += $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            1,
                            0,
                            null,
                            null,
                            0,
                            $booking['id']
                        );
                    }
                }
            }

            $refundReqProducts = $objOrderReturn->getOrderRefundRequestedProducts($objOrder->id, 0, 1, 1);
            $hotelProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($objOrder->id, 0, 0, Product::SELLING_PREFERENCE_HOTEL_STANDALONE);
            foreach($hotelProducts as $key => $product) {
                if ((in_array($product['id_service_product_order_detail'], $refundReqProducts)) || $product['is_refunded']) {
                    unset($hotelProducts[$key]);
                }
            }
            $standaloneProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($objOrder->id, 0, 0, Product::SELLING_PREFERENCE_STANDALONE);
            foreach($standaloneProducts as $key => $product) {
                if ((in_array($product['id_service_product_order_detail'], $refundReqProducts)) || $product['is_refunded']) {
                    unset($standaloneProducts[$key]);
                }
            }

            $this->context->smarty->assign(
                array(
                    'order' => $objOrder,
                    'currency' => new Currency($objOrder->id_currency),
                    'current_index' => self::$currentIndex,
                    'bookingOrderInfo' => $bookingOrderInfo,
                    'serviceProducts' => array_merge($hotelProducts, $standaloneProducts)
                )
            );
            $modal = array(
                'modal_id' => 'cancel-room-booking-modal',
                'modal_class' => 'modal-md order_detail_modal',
                'modal_title' => '<i class="icon icon-exchange"></i> &nbsp'.$this->l('Cancel Request'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_cancel_room_bookings.tpl'),
            );

            if (count($bookingOrderInfo) || count(array_merge($hotelProducts, $standaloneProducts))) {
                $modal['modal_actions'] = array(
                    array(
                        'type' => 'button',
                        'value' => 'submitCancelBooking',
                        'class' => 'submitCancelBooking btn-primary pull-right',
                        'label' => '<i class="icon-exchange"></i> '.$this->l('Cancel Bookings'),
                    )
                );
            }

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitAddProductModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))) {
            $this->context->smarty->assign(
                array(
                    'order' => $objOrder,
                    'currency' => new Currency($objOrder->id_currency),
                    'invoices_collection' => $objOrder->getInvoicesCollection(),
                    'current_id_lang' => $this->context->language->id,
                )
            );
            $modal = array(
                'modal_id' => 'add-product-modal',
                'modal_class' => 'modal-md order_detail_modal',
                'modal_title' => '<i class="icon icon-plus-sign"></i> &nbsp'.$this->l('Add Product'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_add_product.tpl'),
                'modal_actions' => array(
                    array(
                        'type' => 'button',
                        'value' => 'submitAddProduct',
                        'class' => 'submitAddProduct btn-primary pull-right',
                        'label' => '<i class="icon-plus-sign"></i> '.$this->l('Add Product'),
                    ),
                ),
            );

            $this->context->smarty->assign($modal);
            $response['hasError'] = 0;
            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
        }

        die(Tools::jsonEncode($response));
    }

    public function ajaxProcessInitEditProductModal()
    {
        // set modal details
        $response['hasError'] = 1;
        if (Validate::isLoadedObject($objOrder = new Order(Tools::getValue('id_order')))
            && ($id_service_product_order_detail = Tools::getValue('id_service_product_order_detail'))
        ) {
            $smartyVars = array(
                'order' => $objOrder,
                'currency' => new Currency($objOrder->id_currency),
                'orderEdit' => 1,
            );
            if (ValidateCore::isLoadedObject(
                $objServiceProductOrderDetail = new ServiceProductOrderDetail(
                    $id_service_product_order_detail
                )
            )) {
                $smartyVars['ServiceProductOrderDetail'] = $objServiceProductOrderDetail;
                $smartyVars['objProduct'] = new Product($objServiceProductOrderDetail->id_product);
                $taxRate = 1;
                if ($objServiceProductOrderDetail->total_price_tax_excl > 0) {
                    $taxRate = (($objServiceProductOrderDetail->total_price_tax_incl * 100)/ $objServiceProductOrderDetail->total_price_tax_excl)-100;
                }
                $response['data'] = array(
                    'id_product' => $objServiceProductOrderDetail->id_product,
                    'id_product_option' => $objServiceProductOrderDetail->id_product_option,
                    'name' => $objServiceProductOrderDetail->name,
                    'option_name' => $objServiceProductOrderDetail->option_name,
                    'unit_price_tax_excl' => $objServiceProductOrderDetail->unit_price_tax_excl,
                    'unit_price_tax_incl' => $objServiceProductOrderDetail->unit_price_tax_incl,
                    'total_price_tax_excl' => $objServiceProductOrderDetail->total_price_tax_excl,
                    'total_price_tax_incl' => $objServiceProductOrderDetail->total_price_tax_incl,
                    'tax_rate' => $taxRate
                );

                $objCurrency = new Currency($objOrder->id_currency);
                $smartyVars['orderCurrency'] = $objOrder->id_currency;
                $smartyVars['currencySign'] = $objCurrency->sign;
                $smartyVars['link'] = $this->context->link;
                $smartyVars['invoices_collection'] = $objOrder->getInvoicesCollection();
                $smartyVars['current_id_lang'] = $this->context->language->id;

                // set context currency So that we can get prices in the order currency
                $this->context->currency = $objCurrency;
                $this->context->smarty->assign($smartyVars);

                $modal = array(
                    'modal_id' => 'edit-product-modal',
                    'modal_class' => 'modal-md order_detail_modal',
                    'modal_title' => '<i class="icon icon-edit"></i> &nbsp'.$this->l('Edit Product'),
                    'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_edit_product.tpl'),
                    'modal_actions' => array(
                        array(
                            'type' => 'button',
                            'value' => 'submitEditProduct',
                            'class' => 'submitEditProduct btn-primary pull-right',
                            'label' => '<i class="icon-edit"></i> '.$this->l('Update'),
                        ),
                    ),
                );

                $this->context->smarty->assign($modal);
                $response['hasError'] = 0;
                $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
            }
        }

        die(Tools::jsonEncode($response));
    }


    public function setMedia()
    {
        parent::setMedia();

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(_PS_JS_DIR_.'vendor/d3.v3.min.js');

        if ($this->display == 'view') {
            if ($this->loadObject()) {
                $this->addJS(_PS_JS_DIR_.'admin/orders.js');
            }
            // add js for reallocation process
            $this->addJS(_PS_JS_DIR_.'admin/reallocation.js');
            // $this->addJS(_PS_JS_DIR_.'admin/orders-product-event.js');
            // $this->addJS(_PS_JS_DIR_.'admin/orders-room-event.js');
            $this->addJS(_PS_JS_DIR_.'tools.js');
            $this->addJqueryPlugin('autocomplete');
        }
    }

    public function printPDFIcons($id_order, $tr)
    {
        static $valid_order_state = array();

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        if (!isset($valid_order_state[$order->current_state])) {
            $valid_order_state[$order->current_state] = Validate::isLoadedObject($order->getCurrentOrderState());
        }

        if (!$valid_order_state[$order->current_state]) {
            return '';
        }

        $this->context->smarty->assign(array(
            'order' => $order,
            'tr' => $tr
        ));

        return $this->createTemplate('_print_pdf_icon.tpl')->fetch();
    }

    public function printNewCustomer($id_order, $tr)
    {
        return ($tr['new'] ? $this->l('Yes') : $this->l('No'));
    }

    public function processBulkUpdateOrderStatus()
    {
        // if process to cancelled the redirect to the original order page
        if (Tools::getIsset('cancel')) {
            Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
        }

        if (Tools::isSubmit('submitUpdateOrderStatus')
            && ($id_order_state = (int)Tools::getValue('id_order_state'))) {
            if ($this->tabAccess['edit'] !== 1) {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            } else {
                $order_state = new OrderState($id_order_state);

                if (!Validate::isLoadedObject($order_state)) {
                    $this->errors[] = sprintf(Tools::displayError('Order status #%d cannot be loaded'), $id_order_state);
                } else {
                    foreach (Tools::getValue('orderBox') as $id_order) {
                        $order = new Order((int)$id_order);
                        if (!Validate::isLoadedObject($order)) {
                            $this->errors[] = sprintf(Tools::displayError('Order #%d cannot be loaded'), $id_order);
                        } else {
                            $result = $order->changeOrderStatus();
                            if (!$result['status']) {
                                if (isset($result['has_mail_error']) && $result['has_mail_error']) {
                                    $this->errors[] = sprintf(
                                        Tools::displayError('Unable to send email to the customer while changing order status for order #%d.'),
                                        $id_order
                                    );
                                } else {
                                    $this->errors[] = sprintf(Tools::displayError('Cannot change status for order #%d.'), $id_order);
                                }
                            }
                        }
                    }
                }
            }

            if (!count($this->errors)) {
                Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
            }
        }
    }

    public function renderList()
    {
        // Show all resolvable overbookings if available
        $objHotelBookingDetail = new HotelBookingDetail();
        if ($resolvableOverBookings = $objHotelBookingDetail->getOverbookedRooms(0, 0, '', '', 0, 0, 2)) {
            $this->context->smarty->assign('resolvableOverBookings', $resolvableOverBookings);
            $this->content .= $this->context->smarty->fetch('controllers/orders/_resolvable_overbookings.tpl');
        }

        if (Tools::isSubmit('submitBulkupdateOrderStatus'.$this->table)) {
            if (Tools::getIsset('cancel')) {
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
            }

            $this->tpl_list_vars['updateOrderStatus_mode'] = true;
            $this->tpl_list_vars['order_statuses'] = $this->statuses_array;
            $this->tpl_list_vars['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $this->tpl_list_vars['POST'] = $_POST;
        }

        $this->tpl_list_vars['title'] = $this->l('Orders');

        $this->_new_list_header_design = true;

        return parent::renderList();
    }

    public function getList(
        $id_lang,
        $order_by = null,
        $order_way = null,
        $start = 0,
        $limit = null,
        $id_lang_shop = false
    ){
        if ($this->action == 'export' && empty($this->_listsql)) {
            $this->_select .= ', c.`email`, c.`phone`, a.`total_paid_real`,
                orf.`cancellation_date`, orf.`cancellation_fee`,
                cy.`iso_code` AS country, st.`iso_code` as state, ad.`city`,
                CONCAT(ad.`address1`, \', \', ad.`postcode`) AS `cus_address`, hbdtl.*';

            $this->_join .= ' LEFT JOIN `'._DB_PREFIX_.'address` ad ON a.`id_customer`= ad.`id_customer` AND ad.`id_customer`!=0
                LEFT JOIN `'._DB_PREFIX_.'country` cy ON cy.`id_country`= ad.`id_country`
                LEFT JOIN `'._DB_PREFIX_.'state` st ON st.`id_state`= ad.`id_state`
                LEFT JOIN (
                    SELECT ref.`id_order`, IF(refst.refunded = 1, GROUP_CONCAT(ref.`date_upd`), NULL) AS `cancellation_date`,
                        IF(refst.refunded = 1, SUM(ref.`refunded_amount`), NULL) AS `cancellation_fee`
                    FROM `'._DB_PREFIX_.'order_return` ref
                    LEFT JOIN `'._DB_PREFIX_.'order_return_state` refst
                        ON refst.`id_order_return_state` = ref.`state` AND refst.`refunded` = 1
                    GROUP BY ref.`id_order`
                ) AS orf ON orf.`id_order` = a.`id_order`
                LEFT JOIN (
                    SELECT GROUP_CONCAT(
                        CASE
                            WHEN `id_status`='.HotelBookingDetail::STATUS_ALLOTED.' THEN \''.$this->l('Alloted').'\'
                            WHEN `id_status`='.HotelBookingDetail::STATUS_CHECKED_IN.' THEN \''.$this->l('Checked in').'\'
                            WHEN `id_status`='.HotelBookingDetail::STATUS_CHECKED_OUT.' THEN \''.$this->l('Checked out').'\'
                            ELSE \''.$this->l('Invalid status').'\'
                        END
                    ) AS `room_status`, `id_order`,
                    GROUP_CONCAT(DISTINCT(`room_type_name`)) AS `room_types`,
                    GROUP_CONCAT(`date_from`) AS `date_from`,
                    GROUP_CONCAT(`date_to`) AS `date_to`,
                    GROUP_CONCAT(`room_num`) AS `rooms`,
                    GROUP_CONCAT(`check_in`) AS `check_in_dates`,
                    GROUP_CONCAT(`check_out`) AS `check_out_dates`
                    FROM `'._DB_PREFIX_.'htl_booking_detail`
                    GROUP BY `id_order`
                ) AS hbdtl ON hbdtl.`id_order` = a.`id_order`';

            $this->fields_list = array_merge($this->fields_list, array(
                'email' => array(
                    'title' => $this->l('Email')
                ),
                'phone' => array(
                    'title' => $this->l('Phone')
                ),
                'room_types' => array(
                    'title' => $this->l('Room Types')
                ),
                'rooms' => array(
                    'title' => $this->l('Rooms')
                ),
                'currency' => array(
                    'title' => $this->l('Currency')
                ),
                'total_paid_real' => array(
                    'title' => $this->l('Amount Paid')
                ),
                'check_in_dates' => array(
                    'title' => $this->l('Check In')
                ),
                'check_out_dates' => array(
                    'title' => $this->l('Check Out')
                ),
                'date_from' => array(
                    'title' => $this->l('Date From'),
                    'filter_key' => 'hbd!date_from',
                ),
                'date_to' => array(
                    'title' => $this->l('Date To'),
                    'filter_key' => 'hbd!date_to',
                ),
                'date_add' => array(
                    'title' => $this->l('Reservation Date'),
                    'filter_key' => 'a!date_add',
                ),
                'room_status' => array(
                    'title' => $this->l('Room Status'),
                ),
                'country' => array(
                    'title' => $this->l('Country'),
                ),
                'state' => array(
                    'title' => $this->l('State'),
                ),
                'city' => array(
                    'title' => $this->l('City'),
                ),
                'cus_address' => array(
                    'title' => $this->l('Address'),
                ),
                'cancellation_date' => array(
                    'title' => $this->l('Cancellation date'),
                ),
                'cancellation_fee' => array(
                    'title' => $this->l('Cancellation fee'),
                ),
            ));

            unset($this->fields_list['id_status']);
            unset($this->fields_list['id_room_information']);
            unset($this->fields_list['room_type_name']);
            unset($this->fields_list['id_currency']);
        }

        parent::getList(
            $id_lang,
            $order_by,
            $order_way,
            $start,
            $limit,
            $id_lang_shop
        );
    }

    public function postProcess()
    {
        // Process reallocation of rooms
        if (Tools::isSubmit('realloc_allocated_rooms')) {
            if ($this->tabAccess['edit'] === 1) {
                $idOrder = Tools::getValue('id_order');
                $idHtlBookingFrom = Tools::getValue('id_htl_booking');
                $idNewRoomType = Tools::getValue('realloc_avail_room_type');
                $idRoomToReallocate = Tools::getValue('realloc_avail_rooms');
                $priceDiff = Tools::getValue('reallocation_price_diff');

                $objBookingDetail = new HotelBookingDetail();
                if ($idRoomToReallocate) {
                    // check if room is from selected room type
                    if (Validate::isLoadedObject($objRoomInfo = new HotelRoomInformation($idRoomToReallocate))) {
                        if ($objRoomInfo->id_product != $idNewRoomType) {
                            $this->errors[] = $this->l('Invalid room selected for reallocation.');
                        } elseif (!Validate::isLoadedObject($objHotelBooking = new HotelBookingDetail($idHtlBookingFrom))) {
                            $this->errors[] = $this->l('Invalid booking found for reallocation.');
                        } elseif (!$availableRooms = $objBookingDetail->getAvailableRoomsForReallocation(
                            $objHotelBooking->date_from,
                            $objHotelBooking->date_to,
                            $idNewRoomType,
                            $objHotelBooking->id_hotel
                        )) {
                            $this->errors[] = $this->l('Selected room is not available for reallocation.');
                        } elseif (!in_array($idRoomToReallocate, array_column($availableRooms, 'id_room'))) {
                            $this->errors[] = $this->l('Selected room is not available for reallocation.');
                        } elseif (!Validate::isFloat($priceDiff)) {
                            $this->errors[] = $this->l('Invalid price difference of the room types.');
                        }
                    } else {
                        $this->errors[] = $this->l('Selected room is not available for reallocation.');
                    }
                } else {
                    $this->errors[] = $this->l('Please select a room to reallocate with this room.');
                }

                if (!count($this->errors)) {
                    // Finally, reallocate the room
                    if ($objBookingDetail->reallocateBooking($idHtlBookingFrom, $idRoomToReallocate, $priceDiff)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int) $idOrder.'&vieworder&conf=52&token='.$this->token);
                    } else {
                        $this->errors[] = $this->l('Some error occured. Please try again.');
                    }
                }
            } else {
                $this->errors[] = $this->l('You do not have permission to edit this order.');
            }
        }

        // Process swap of rooms
        if (Tools::isSubmit('swap_allocated_rooms')) {
            if ($this->tabAccess['edit'] === 1) {
                $idOrder = Tools::getValue('id_order');
                $idHtlBookingFrom = Tools::getValue('id_htl_booking');
                $idHtlBookingToSwap = Tools::getValue('swap_avail_rooms');

                if (!Validate::isLoadedObject($objHotelBooking = new HotelBookingDetail($idHtlBookingFrom))) {
                    $this->errors[] = $this->l('Selected room is not available to swap.');
                } else {
                    if (!Validate::isLoadedObject($objHotelBookingTo = new HotelBookingDetail($idHtlBookingToSwap))) {
                        $this->errors[] = $this->l('Please select a room to swap with this room booking.');
                    } else {
                        if ($availableRooms = $objHotelBooking->getAvailableRoomsForSwapping(
                            $objHotelBooking->date_from,
                            $objHotelBooking->date_to,
                            $objHotelBooking->id_product,
                            $objHotelBooking->id_hotel,
                            $objHotelBooking->id_room
                        )) {
                            if (!in_array($idHtlBookingToSwap, array_column($availableRooms, 'id_hotel_booking'))) {
                                $this->errors[] = $this->l('Selected room is not available to swap.');
                            }
                        } else {
                            $this->errors[] = $this->l('Selected room is not available to swap.');
                        }
                    }
                }

                if (!count($this->errors)) {
                    $objBookingDetail = new HotelBookingDetail();
                    if ($objBookingDetail->swapBooking($idHtlBookingFrom, $idHtlBookingToSwap)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$idOrder.'&vieworder&conf=53&token='.$this->token);
                    } else {
                        $this->errors[] = $this->l('Some error occured. Please try again.');
                    }
                }
            } else {
                $this->errors[] = $this->l('You do not have permission to edit this order.');
            }
        }
        // To update order status when admin changes from order detail page
        if (Tools::isSubmit('submitbookingOrderStatus')) {
            if ($this->tabAccess['edit'] === 1) {
                $this->changeRoomStatus();
            } else {
                $this->errors[] = $this->l('You do not have permission to edit this order.');
            }
        }

        // If id_order is sent, we instanciate a new Order object
        if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0) {
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                $this->errors[] = Tools::displayError('The order cannot be found within your database.');
            }
            ShopUrl::cacheMainDomainForShop((int)$order->id_shop);
        }

        /* Update shipping number */
        if (Tools::isSubmit('submitShippingNumber') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $order_carrier = new OrderCarrier(Tools::getValue('id_order_carrier'));
                if (!Validate::isLoadedObject($order_carrier)) {
                    $this->errors[] = Tools::displayError('The order carrier ID is invalid.');
                } elseif (!Validate::isTrackingNumber(Tools::getValue('tracking_number'))) {
                    $this->errors[] = Tools::displayError('The tracking number is incorrect.');
                } else {
                    // update shipping number
                    // Keep these two following lines for backward compatibility, remove on 1.6 version
                    $order->shipping_number = Tools::getValue('tracking_number');
                    $order->update();

                    // Update order_carrier
                    $order_carrier->tracking_number = pSQL(Tools::getValue('tracking_number'));
                    if ($order_carrier->update()) {
                        // Send mail to customer
                        $customer = new Customer((int)$order->id_customer);
                        $carrier = new Carrier((int)$order->id_carrier, $order->id_lang);
                        if (!Validate::isLoadedObject($customer)) {
                            throw new PrestaShopException('Can\'t load Customer object');
                        }
                        if (!Validate::isLoadedObject($carrier)) {
                            throw new PrestaShopException('Can\'t load Carrier object');
                        }
                        $templateVars = array(
                            '{followup}' => str_replace('@', $order->shipping_number, $carrier->url),
                            '{firstname}' => $customer->firstname,
                            '{lastname}' => $customer->lastname,
                            '{id_order}' => $order->id,
                            '{shipping_number}' => $order->shipping_number,
                            '{order_name}' => $order->getUniqReference()
                        );
                        if (@Mail::Send(
                            (int)$order->id_lang,
                            'in_transit',
                            Mail::l('Package in transit', (int)$order->id_lang),
                            $templateVars,
                            $customer->email,
                            $customer->firstname.' '.$customer->lastname,
                            null,
                            null,
                            null,
                            null,
                            _PS_MAIL_DIR_,
                            true,
                            (int)$order->id_shop
                        )) {
                            Hook::exec('actionAdminOrdersTrackingNumberUpdate', array('order' => $order, 'customer' => $customer, 'carrier' => $carrier), null, false, true, false, $order->id_shop);
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                        }
                    } else {
                        $this->errors[] = Tools::displayError('The order carrier cannot be updated.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        }

        /* Change order status, add a new entry in order history and send an e-mail to the customer if needed */
        elseif (Tools::isSubmit('submitState') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $result = $order->changeOrderStatus();
                if ($result['status']) {
                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&conf=5&vieworder&token='.$this->token);
                } else {
                    $this->errors = array_merge($this->errors, $result['errors']);
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        }

        /* Add a new message for the current order and send an e-mail to the customer if needed */
        elseif (Tools::isSubmit('submitMessage') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $customer = new Customer(Tools::getValue('id_customer'));
                $message = Tools::getValue('message');
                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('The customer is invalid.');
                } elseif (!$message) {
                    $this->errors[] = Tools::displayError('The message cannot be blank.');
                } elseif (!$message) {
                    $this->errors[] = Tools::displayError('The message cannot be blank.');
                } elseif (!Validate::isCleanHtml($message)) {
                    $this->errors[] = Tools::displayError('The message is invalid.');
                } else {
                    /* Get message rules and and check fields validity */
                    $rules = call_user_func(array('Message', 'getValidationRules'), 'Message');
                    foreach ($rules['required'] as $field) {
                        if (($value = Tools::getValue($field)) == false && (string)$value != '0') {
                            if (!Tools::getValue('id_'.$this->table) || $field != 'passwd') {
                                $this->errors[] = sprintf(Tools::displayError('field %s is required.'), $field);
                            }
                        }
                    }
                    foreach ($rules['size'] as $field => $maxLength) {
                        if (Tools::getValue($field) && Tools::strlen(Tools::getValue($field)) > $maxLength) {
                            $this->errors[] = sprintf(Tools::displayError('field %1$s is too long (%2$d chars max).'), $field, $maxLength);
                        }
                    }
                    foreach ($rules['validate'] as $field => $function) {
                        if (Tools::getValue($field)) {
                            if (!Validate::{$function}(htmlentities(Tools::getValue($field), ENT_COMPAT, 'UTF-8'))) {
                                $this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $field);
                            }
                        }
                    }

                    if (!count($this->errors)) {
                        //check if a thread already exist
                        $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $order->id);
                        if (!$id_customer_thread) {
                            $customer_thread = new CustomerThread();
                            $customer_thread->id_contact = (int)Configuration::get('PS_MAIL_EMAIL_MESSAGE');
                            $customer_thread->id_customer = (int)$order->id_customer;
                            $customer_thread->user_name = $customer->firstname.' '.$customer->lastname;
                            $customer_thread->phone = $customer->phone;
                            $customer_thread->subject = $order->reference;
                            $customer_thread->id_employee = (int)$this->context->employee->id;
                            $customer_thread->id_shop = (int)$this->context->shop->id;
                            $customer_thread->id_order = (int)$order->id;
                            $customer_thread->id_lang = (int)$this->context->language->id;
                            $customer_thread->email = $customer->email;
                            $customer_thread->status = CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN;
                            $customer_thread->token = Tools::passwdGen(12);
                            $customer_thread->add();
                        } else {
                            $customer_thread = new CustomerThread((int)$id_customer_thread);
                        }

                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = (int)$this->context->employee->id;
                        $customer_message->message = $message;
                        $customer_message->private = !Tools::getValue('visibility');

                        if (!$customer_message->add()) {
                            $this->errors[] = Tools::displayError('An error occurred while saving the message.');
                        } elseif ($customer_message->private) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&vieworder&conf=11&token='.$this->token);
                        } else {
                            $message = $customer_message->message;
                            if (Configuration::get('PS_MAIL_TYPE', null, null, $order->id_shop) != Mail::TYPE_TEXT) {
                                $message = Tools::nl2br($customer_message->message);
                            }

                            $varsTpl = array(
                                '{lastname}' => $customer->lastname,
                                '{firstname}' => $customer->firstname,
                                '{id_order}' => $order->id,
                                '{order_name}' => $order->getUniqReference(),
                                '{message}' => $message
                            );
                            if (@Mail::Send(
                                (int)$order->id_lang,
                                'order_merchant_comment',
                                Mail::l('New message regarding your booking', (int)$order->id_lang),
                                $varsTpl,
                                $customer->email,
                                $customer->firstname.' '.$customer->lastname,
                                null,
                                null,
                                null,
                                null,
                                _PS_MAIL_DIR_,
                                true,
                                (int)$order->id_shop
                            )) {
                                Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=11'.'&token='.$this->token);
                            }
                        }
                        $this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        }

        /* booking refunds from order */
        elseif (Tools::isSubmit('initiateRefund') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $objOrderReturn = new OrderReturn();
                $bookings = Tools::getValue('id_htl_booking');
                if ($bookings && count($bookings)) {
                    $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($order->id, 0, 1, 0, 1);
                    foreach ($bookings as $idHtlBooking) {
                        $objBookingDetail = new HotelBookingDetail($idHtlBooking);
                        if ((in_array($idHtlBooking, $refundReqBookings)) || $objBookingDetail->is_refunded) {
                            $this->errors[] = Tools::displayError('Wrong bookings found for cancelation.');
                            break;
                        }
                    }
                }

                $idServiceProductOrderDetails = Tools::getValue('id_service_product_order_detail');
                if ($idServiceProductOrderDetails) {
                    $refundReqProducts = $objOrderReturn->getOrderRefundRequestedProducts($order->id, 0, 1, 1);
                    foreach ($idServiceProductOrderDetails as $idServiceProductOrderDetail) {
                        $objServiceProductOrderDetail = new ServiceProductOrderDetail($idServiceProductOrderDetail);
                        if ((in_array($idServiceProductOrderDetail, $refundReqProducts)) || $objServiceProductOrderDetail->is_refunded) {
                            $this->errors[] = Tools::displayError('Wrong products found for cancelation.');
                            break;
                        }
                    }
                }

                if (!$bookings && !$idServiceProductOrderDetails) {
                    $this->errors[] = Tools::displayError('No booking/products has been selected.');
                }

                if (!$refundReason = Tools::getValue('cancellation_reason')) {
                    $this->errors[] = Tools::displayError('Please enter a reason for the booking cancellation.');
                }

                if (!count($this->errors)) {
                    // create refund request
                    $objOrderReturn = new OrderReturn();
                    $objOrderReturn->id_customer = $order->id_customer;
                    $objOrderReturn->id_order = $order->id;
                    $objOrderReturn->state = 0;
                    $objOrderReturn->by_admin = 1;
                    $objOrderReturn->question = $refundReason;
                    $objOrderReturn->refunded_amount = 0;
                    $objOrderReturn->save();
                    if ($objOrderReturn->id) {
                        if ($bookings) {
                            foreach ($bookings as $idHtlBooking) {
                                $objHtlBooking = new HotelBookingDetail($idHtlBooking);
                                $numDays = HotelHelper::getNumberOfDays(
                                    $objHtlBooking->date_from,
                                    $objHtlBooking->date_to
                                );
                                $objOrderReturnDetail = new OrderReturnDetail();
                                $objOrderReturnDetail->id_order_return = $objOrderReturn->id;
                                $objOrderReturnDetail->id_order_detail = $objHtlBooking->id_order_detail;
                                $objOrderReturnDetail->product_quantity = $numDays;
                                $objOrderReturnDetail->id_htl_booking = $idHtlBooking;
                                $objOrderReturnDetail->refunded_amount = 0;
                                if (!$order->getCartRules() && $order->getTotalPaid() <= 0) {
                                    $objOrderReturnDetail->id_customization = 1;
                                }
                                $objOrderReturnDetail->save();
                            }
                        }

                        if ($idServiceProductOrderDetails) {
                            foreach ($idServiceProductOrderDetails as $idServiceProductOrderDetail) {
                                $objServiceProductOrderDetail = new ServiceProductOrderDetail($idServiceProductOrderDetail);
                                $objOrderReturnDetail = new OrderReturnDetail();
                                $objOrderReturnDetail->id_order_return = $objOrderReturn->id;
                                $objOrderReturnDetail->id_order_detail = $objServiceProductOrderDetail->id_order_detail;
                                $objOrderReturnDetail->product_quantity = $objServiceProductOrderDetail->quantity;
                                $objOrderReturnDetail->id_service_product_order_detail = $idServiceProductOrderDetail;
                                $objOrderReturnDetail->refunded_amount = 0;
                                if (!$order->getCartRules() && $order->getTotalPaid() <= 0) {
                                    $objOrderReturnDetail->id_customization = 1;
                                }
                                $objOrderReturnDetail->save();
                            }
                        }
                    }

                    // Change order refund state
                    // Emails to customer, superadmin and employees on refund request state change
                    $objOrderReturn->changeIdOrderReturnState(Configuration::get('PS_ORS_PENDING'));
                    // process direct cancellations
                    if (!$order->getCartRules() && $order->getTotalPaid() <= 0) {
                        // Process refund in booking tables
                        if ($bookings) {
                            foreach ($bookings as $idHtlBooking) {
                                $objHtlBooking = new HotelBookingDetail($idHtlBooking);
                                if (!$objHtlBooking->processRefundInBookingTables()) {
                                    $this->errors[] = Tools::displayError('An error occurred while cancelling the booking.');
                                }
                            }
                        }
                        if ($idServiceProductOrderDetails) {
                            foreach ($idServiceProductOrderDetails as $idServiceProductOrderDetail) {
                                $objServiceProductOrderDetail = new ServiceProductOrderDetail($idServiceProductOrderDetail);
                                if (!$objServiceProductOrderDetail->processRefundInTables()) {
                                    $this->errors[] = Tools::displayError('An error occurred while cancelling the product.');
                                }
                            }
                        }

                        // As object order is already changed in processRefundInBookingTables
                        $order = new Order($order->id);
                        // complete the booking refund directly in the refund request
                        $objOrderReturn->changeIdOrderReturnState(Configuration::get('PS_ORS_REFUNDED'));

                        // if all bookings are getting cancelled/Refunded then Cancel/Refund the order also
                        $idOrderState = $order->getOrderCompleteRefundStatus();

                        if ($idOrderState) {
                            $objOrderHistory = new OrderHistory();
                            $objOrderHistory->id_order = (int)$order->id;

                            $useExistingPayment = false;
                            if (!$order->hasInvoice()) {
                                $useExistingPayment = true;
                            }

                            $objOrderHistory->changeIdOrderState($idOrderState, $order, $useExistingPayment);
                            $objOrderHistory->addWithemail();
                        }
                    }
                }

                // Redirect if no errors
                if (!count($this->errors)) {
                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=3&token='.$this->token);
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif (Tools::isSubmit('messageReaded')) {
            Message::markAsReaded(Tools::getValue('messageReaded'), $this->context->employee->id);
        } elseif (Tools::isSubmit('submitAddPayment') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $amount = str_replace(',', '.', Tools::getValue('payment_amount'));
                $currency = new Currency(Tools::getValue('payment_currency'));
                $payment_type = Tools::getValue('payment_type');
                $order_has_invoice = $order->hasInvoice();
                if ($order_has_invoice) {
                    $order_invoice = new OrderInvoice(Tools::getValue('payment_invoice'));
                } else {
                    $order_invoice = null;
                }

                if (!Validate::isLoadedObject($order)) {
                    $this->errors[] = Tools::displayError('The order cannot be found');
                } elseif (!Validate::isNegativePrice($amount) || !(float)$amount) {
                    $this->errors[] = Tools::displayError('The amount is invalid.');
                } elseif (!Validate::isGenericName(Tools::getValue('payment_method'))) {
                    $this->errors[] = Tools::displayError('The selected payment method is invalid.');
                } elseif (!Validate::isString(Tools::getValue('payment_transaction_id'))) {
                    $this->errors[] = Tools::displayError('The transaction ID is invalid.');
                } elseif (!Validate::isLoadedObject($currency)) {
                    $this->errors[] = Tools::displayError('The selected currency is invalid.');
                } elseif ($order_has_invoice && !Validate::isLoadedObject($order_invoice)) {
                    $this->errors[] = Tools::displayError('The invoice is invalid.');
                } elseif (!Validate::isDate(Tools::getValue('payment_date'))) {
                    $this->errors[] = Tools::displayError('The date is invalid');
                } elseif (!Validate::isUnsignedInt($payment_type)) {
                    $this->errors[] = Tools::displayError('Payment source is invalid');
                // Amount cannot be less than paid amount by guest in negative price
                } elseif ($amount < 0) {
                    if ($currency->id == $order->id_currency) {
                        if (($order->total_paid_real + $amount) < 0) {
                            $this->errors[] = sprintf(Tools::displayError('Amount cannot be less than -%s'), Tools::displayPrice($order->total_paid_real, new Currency($order->id_currency)));
                        }
                    } else {
                        $convertedAmount = Tools::ps_round(Tools::convertPriceFull($amount, $currency, new Currency($order->id_currency)), 6);
                        $convertedPaidAmount = Tools::ps_round(Tools::convertPriceFull($order->total_paid_real, new Currency($order->id_currency), $currency), 6);
                        if (($order->total_paid_real + $convertedAmount) < 0) {
                            $this->errors[] = sprintf(Tools::displayError('Amount cannot be less than -%s (-%s)'), Tools::displayPrice($convertedPaidAmount, $currency), Tools::displayPrice($order->total_paid_real, new Currency($order->id_currency)));
                        }
                    }
                }

                if (!count($this->errors)) {
                    if (!$order->addOrderPayment(
                        $amount,
                        Tools::getValue('payment_method'),
                        Tools::getValue('payment_transaction_id'),
                        $currency,
                        Tools::getValue('payment_date'),
                        $order_invoice,
                        $payment_type
                    )) {
                        if (!validate::isPrice($order->total_paid_real)) {
                            $this->errors[] = Tools::displayError('Order total payments cannot be less than 0.');
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred during payment.');
                        }
                    } else {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif (Tools::isSubmit('submitEditNote')) {
            $note = Tools::getValue('note');
            $order_invoice = new OrderInvoice((int)Tools::getValue('id_order_invoice'));
            if (Validate::isLoadedObject($order_invoice)) {
                if (Validate::isCleanHtml($note)) {
                    if ($this->tabAccess['edit'] === 1) {
                        $order_invoice->note = $note;
                        if ($order_invoice->save()) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order_invoice->id_order.'&vieworder&conf=4&token='.$this->token);
                        } else {
                            $this->errors[] = Tools::displayError('The invoice note was not saved.');
                        }
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('Invalid note found.');
                }
            } else {
                $this->errors[] = Tools::displayError('The invoice for edit note was unable to load. ');
            }
        } elseif (Tools::isSubmit('submitAddOrder') && ($id_cart = Tools::getValue('id_cart'))) {
            if ($this->tabAccess['add'] === 1) {
                $objCart = new Cart($id_cart);
                if (Validate::isLoadedObject($objCart)) {
                    $this->context->cart = $objCart;
                    $this->context->currency = new Currency((int)$objCart->id_currency);
                    $this->context->customer = new Customer((int) $objCart->id_customer);

                    $this->errors = HotelCartBookingData::validateCartBookings(!Configuration::get('PS_ALLOW_ADD_ALL_SERVICES_IN_BOOKING'));

                    // Remove cart rules and add if any changes
                    CartRule::autoRemoveFromCart($this->context);
                    CartRule::autoAddToCart($this->context);

                    $orderTotal = $objCart->getOrderTotal(true, Cart::BOTH);
                    if ($objCart->is_advance_payment) {
                        $advancePaymentAmount = $objCart->getOrderTotal(true, Cart::ADVANCE_PAYMENT);
                    }

                    // Validate data if required
                    if ($orderTotal > 0) {
                        $moduleName = trim(Tools::getValue('payment_module_name'));
                        $paymentType = Tools::getValue('payment_type');
                        $paymentTransactionId = trim(Tools::getValue('payment_transaction_id'));
                        $isFullPayment = Tools::getValue('is_full_payment');
                        $paymentAmount = trim(Tools::getValue('payment_amount'));

                        if (!$isFullPayment) {
                            if ($paymentAmount == '') {
                                $this->errors[] = Tools::displayError('Please enter valid Payment amount of the order.');
                            } elseif ($paymentAmount && !Validate::isPrice($paymentAmount)) {
                                $this->errors[] = Tools::displayError('Payment amount is invalid. Please enter valid amount.');
                            } else {
                                $paymentAmount = (float) $paymentAmount;
                            }
                        } else {
                            $paymentAmount = $orderTotal;
                        }

                        if ($paymentAmount >= 0) {
                            if (!$moduleName) {
                                $this->errors[] = Tools::displayError('Please enter Payment method.');
                            } elseif ($moduleName && !Validate::isGenericName($moduleName)) {
                                $this->errors[] = Tools::displayError('Payment method is invalid. Please enter a valid payment method.');
                            }
                        }

                        if ($paymentAmount > 0) {
                            if (!$paymentType) {
                                $this->errors[] = Tools::displayError('Please select a Payment source.');
                            } elseif ($paymentType && !Validate::isUnsignedInt($paymentType)) {
                                $this->errors[] = Tools::displayError('Payment source is invalid. Please select payment source.');
                            }

                            if ($paymentTransactionId && !Validate::isString($paymentTransactionId)) {
                                $this->errors[] = Tools::displayError('Payment amount is invalid. Please enter correct amount.');
                            }
                        }
                    }

                    if (!count($this->errors)) {

                        // Set payment module details
                        $objPaymentModule = new BoOrder();
                        $objPaymentModule->displayName = $moduleName;

                        if ($orderTotal > 0) {
                            $objPaymentModule->payment_type = $paymentType;

                            // Set order state
                            if ($isFullPayment) {
                                $idOrderState = Configuration::get('PS_OS_PAYMENT_ACCEPTED');
                            } else {
                                if ($paymentAmount <= 0) {
                                    $idOrderState = Configuration::get('PS_OS_AWAITING_PAYMENT');
                                } elseif (($paymentAmount >= $orderTotal)) {
                                    $idOrderState = Configuration::get('PS_OS_PAYMENT_ACCEPTED');
                                } else {
                                    $idOrderState = Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED');
                                }
                            }

                            // Set amount paid
                            $amountPaid = $isFullPayment ? $orderTotal : $paymentAmount;

                            // Set transaction ID
                            $extraVars = $paymentTransactionId ? array('transaction_id' => $paymentTransactionId) : array();
                        } else {
                            $objPaymentModule = new FreeOrder();
                            // Set order state
                            if ($objCart->is_advance_payment) {
                                if ($advancePaymentAmount <= $orderTotal) {
                                    $idOrderState = Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED');
                                } else {
                                    $idOrderState = Configuration::get('PS_OS_PAYMENT_ACCEPTED');
                                }
                            } else {
                                $idOrderState = Configuration::get('PS_OS_PAYMENT_ACCEPTED');
                            }

                            // Set amount paid
                            $amountPaid = 0;

                            // Set transaction ID
                            $extraVars = null;
                        }

                        $amountPaid = Tools::ps_round($amountPaid, 6);
                        $objEmployee = new Employee($this->context->cookie->id_employee);
                        $sendMails = Tools::getValue('send_mails', true);

                        $objPaymentModule->validateOrder(
                            $objCart->id,
                            $idOrderState,
                            $amountPaid,
                            $objPaymentModule->displayName,
                            $this->l('Manual order -- Employee:').' '.substr($objEmployee->firstname, 0, 1).'. '.$objEmployee->lastname,
                            $extraVars,
                            null,
                            false,
                            $objCart->secure_key,
                            null,
                            $sendMails
                        );

                        if (isset($this->context->cookie->id_cart)) {
                            unset($this->context->cookie->id_cart);
                        }
                        if (isset($this->context->cookie->id_guest)) {
                            unset($this->context->cookie->id_guest);
                        }

                        if ($objPaymentModule->currentOrder) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$objPaymentModule->currentOrder.'&vieworder'.'&token='.$this->token.'&conf=3');
                        }
                    }
                } else {
                    $this->errors[] = Tools::displayError('Cart can not be loaded.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif ((Tools::isSubmit('submitAddressShipping') || Tools::isSubmit('submitAddressInvoice')) && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $address = new Address(Tools::getValue('id_address'));
                if (Validate::isLoadedObject($address)) {
                    // Update the address on order
                    if (Tools::isSubmit('submitAddressShipping')) {
                        $order->id_address_delivery = $address->id;
                    } elseif (Tools::isSubmit('submitAddressInvoice')) {
                        $order->id_address_invoice = $address->id;
                    }
                    $order->update();
                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = Tools::displayError('This address can\'t be loaded');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif (Tools::isSubmit('submitChangeCurrency') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                if (Tools::getValue('new_currency') != $order->id_currency && !$order->valid) {
                    $old_currency = new Currency($order->id_currency);
                    $currency = new Currency(Tools::getValue('new_currency'));
                    if (!Validate::isLoadedObject($currency)) {
                        throw new PrestaShopException('Can\'t load Currency object');
                    }

                    // Update order detail amount
                    foreach ($order->getOrderDetailList() as $row) {
                        $order_detail = new OrderDetail($row['id_order_detail']);
                        $fields = array(
                            'ecotax',
                            'product_price',
                            'reduction_amount',
                            'total_shipping_price_tax_excl',
                            'total_shipping_price_tax_incl',
                            'total_price_tax_incl',
                            'total_price_tax_excl',
                            'product_quantity_discount',
                            'purchase_supplier_price',
                            'reduction_amount',
                            'reduction_amount_tax_incl',
                            'reduction_amount_tax_excl',
                            'unit_price_tax_incl',
                            'unit_price_tax_excl',
                            'original_product_price'

                        );
                        foreach ($fields as $field) {
                            $order_detail->{$field} = Tools::convertPriceFull($order_detail->{$field}, $old_currency, $currency);
                        }

                        $order_detail->update();
                        $order_detail->updateTaxAmount($order);
                    }

                    $id_order_carrier = (int)$order->getIdOrderCarrier();
                    if ($id_order_carrier) {
                        $order_carrier = $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
                        $order_carrier->shipping_cost_tax_excl = (float)Tools::convertPriceFull($order_carrier->shipping_cost_tax_excl, $old_currency, $currency);
                        $order_carrier->shipping_cost_tax_incl = (float)Tools::convertPriceFull($order_carrier->shipping_cost_tax_incl, $old_currency, $currency);
                        $order_carrier->update();
                    }

                    // update Order Cart rules
                    if ($orderVouchers = $order->getCartRules()) {
                        $fields = array(
                            'value',
                            'value_tax_excl',
                        );
                        foreach($orderVouchers as $orderVoucher) {
                            $objOrderCartRule = new OrderCartRuleCore($orderVoucher['id_order_cart_rule']);
                            foreach ($fields as $field) {
                                $objOrderCartRule->{$field} = Tools::convertPriceFull($objOrderCartRule->{$field}, $old_currency, $currency);
                            }
                            $objOrderCartRule->update();
                        }
                    }

                    // Update order && order_invoice amount
                    $fields = array(
                        'total_discounts',
                        'total_discounts_tax_incl',
                        'total_discounts_tax_excl',
                        'total_discount_tax_excl',
                        'total_discount_tax_incl',
                        'total_paid',
                        'total_paid_tax_incl',
                        'total_paid_tax_excl',
                        'total_paid_real',
                        'total_products',
                        'total_products_wt',
                        'total_shipping',
                        'total_shipping_tax_incl',
                        'total_shipping_tax_excl',
                        'total_wrapping',
                        'total_wrapping_tax_incl',
                        'total_wrapping_tax_excl',
                        'advance_paid_amount',
                    );

                    $invoices = $order->getInvoicesCollection();
                    if ($invoices) {
                        foreach ($invoices as $invoice) {
                            foreach ($fields as $field) {
                                if (isset($invoice->$field)) {
                                    $invoice->{$field} = Tools::convertPriceFull($invoice->{$field}, $old_currency, $currency);
                                }
                            }
                            $invoice->save();
                        }
                    }

                    foreach ($fields as $field) {
                        if (isset($order->$field)) {
                            $order->{$field} = Tools::convertPriceFull($order->{$field}, $old_currency, $currency);
                        }
                    }

                    // Update currency in order
                    $order->id_currency = $currency->id;
                    // Update exchange rate
                    $order->conversion_rate = (float)$currency->conversion_rate;
                    $order->update();

                    // update rooms bookings prices (htl_booking_detail)
                    $objHtlBookingDetail = new HotelBookingDetail();
                    if ($orderRoomBookings = $objHtlBookingDetail->getBookingDataByOrderId($order->id)) {
                        $fields = array(
                            'total_price_tax_excl',
                            'total_price_tax_incl',
                            'total_paid_amount',
                        );
                        foreach ($orderRoomBookings as $roomBooking) {
                            $objHtlBookingDetail = new HotelBookingDetail($roomBooking['id']);
                            foreach ($fields as $field) {
                                $objHtlBookingDetail->{$field} = Tools::convertPriceFull(
                                    $objHtlBookingDetail->{$field},
                                    $old_currency,
                                    $currency
                                );
                            }
                            $objHtlBookingDetail->update();
                        }
                    }

                    // update rooms bookings demands (htl_booking_demands)
                    $objBookingDemand = new HotelBookingDemands();
                    if ($orderBookingDemands = $objBookingDemand->getRoomTypeBookingExtraDemands($order->id, 0, 0, 0, 0, 0)) {
                        $fields = array(
                            'unit_price_tax_excl',
                            'unit_price_tax_incl',
                            'total_price_tax_excl',
                            'total_price_tax_incl',
                        );

                        $vatAddress = new Address((int)$order->id_address_tax);
                        $idLang = (int) $order->id_lang;
                        foreach ($orderBookingDemands as $bookingDemand) {
                            $objBookingDemand = new HotelBookingDemands($bookingDemand['id_booking_demand']);
                            foreach ($fields as $field) {
                                $objBookingDemand->{$field} = Tools::convertPriceFull(
                                    $objBookingDemand->{$field},
                                    $old_currency,
                                    $currency
                                );
                            }

                            // update Tax of bookings demands
                            if ($objBookingDemand->save()) {
                                $taxManager = TaxManagerFactory::getManager(
                                    $vatAddress,
                                    $objBookingDemand->id_tax_rules_group
                                );
                                $taxCalculator = $taxManager->getTaxCalculator();
                                $objBookingDemand->tax_computation_method = (int)$taxCalculator->computation_method;
                                $objBookingDemand->tax_calculator = $taxCalculator;

                                // Now save tax details of the extra demand with replace = 1
                                $objBookingDemand->setBookingDemandTaxDetails(1);
                            }
                        }
                    }

                    $fields = array(
                        'unit_price_tax_excl',
                        'unit_price_tax_incl',
                        'total_price_tax_excl',
                        'total_price_tax_incl',
                    );
                    // Update order service product prices
                    $objRoomTypeServProdOrderDtl = new ServiceProductOrderDetail();
                    if ($orderServiceProducts = $objRoomTypeServProdOrderDtl->getRoomTypeServiceProducts(
                        $order->id,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        null,
                        null
                    )) {
                        foreach ($orderServiceProducts as $htlBokingServices) {
                            if (isset($htlBokingServices['additional_services']) && $htlBokingServices['additional_services']) {
                                foreach ($htlBokingServices['additional_services'] as $serviceProduct) {
                                    $objRoomTypeServProdOrderDtl = new ServiceProductOrderDetail($serviceProduct['id_service_product_order_detail']);
                                    foreach ($fields as $field) {
                                        $objRoomTypeServProdOrderDtl->{$field} = Tools::convertPriceFull(
                                            $objRoomTypeServProdOrderDtl->{$field},
                                            $old_currency,
                                            $currency
                                        );
                                    }

                                    $objRoomTypeServProdOrderDtl->save();
                                }
                            }
                        }
                    }

                    if ($hotelProducts = $objRoomTypeServProdOrderDtl->getServiceProductsInOrder($order->id, 0, 0, Product::SELLING_PREFERENCE_HOTEL_STANDALONE)) {
                        foreach ($hotelProducts as $serviceProduct) {
                            $objRoomTypeServProdOrderDtl = new ServiceProductOrderDetail($serviceProduct['id_service_product_order_detail']);
                            foreach ($fields as $field) {
                                $objRoomTypeServProdOrderDtl->{$field} = Tools::convertPriceFull(
                                    $objRoomTypeServProdOrderDtl->{$field},
                                    $old_currency,
                                    $currency
                                );
                            }

                            $objRoomTypeServProdOrderDtl->save();
                        }
                    }

                    if ($standaloneProducts = $objRoomTypeServProdOrderDtl->getServiceProductsInOrder($order->id, 0, 0, Product::SELLING_PREFERENCE_STANDALONE)) {
                        foreach ($standaloneProducts as $serviceProduct) {
                            $objRoomTypeServProdOrderDtl = new ServiceProductOrderDetail($serviceProduct['id_service_product_order_detail']);
                            foreach ($fields as $field) {
                                $objRoomTypeServProdOrderDtl->{$field} = Tools::convertPriceFull(
                                    $objRoomTypeServProdOrderDtl->{$field},
                                    $old_currency,
                                    $currency
                                );
                            }

                            $objRoomTypeServProdOrderDtl->save();
                        }
                    }


                    // update Order refund prices (order_return and order_return_detail)
                    if ($orderReturns = OrderReturn::getOrdersReturn($order->id_customer, $order->id)) {
                        $fields = array(
                            'refunded_amount',
                        );
                        foreach ($orderReturns as $orderReturn) {
                            $objOrderReturn = new OrderReturn($orderReturn['id_order_return']);
                            foreach ($fields as $field) {
                                $objOrderReturn->{$field} = Tools::convertPriceFull(
                                    $objOrderReturn->{$field},
                                    $old_currency,
                                    $currency
                                );
                            }
                            $objOrderReturn->update();
                        }
                    }

                    if ($orderReturnDetails = OrderReturn::getOrdersReturnDetail($order->id)) {
                        $fields = array(
                            'refunded_amount',
                        );
                        foreach ($orderReturnDetails as $orderReturnDetail) {
                            $objOrderReturnDetail = new OrderReturnDetail($orderReturnDetail['id_order_return_detail']);
                            foreach ($fields as $field) {
                                $objOrderReturnDetail->{$field} = Tools::convertPriceFull(
                                    $objOrderReturnDetail->{$field},
                                    $old_currency,
                                    $currency
                                );
                            }
                            $objOrderReturnDetail->update();
                        }
                    }

                    // If everything is updated, then redirect to view order with success message
                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = Tools::displayError('You cannot change the currency.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif (Tools::isSubmit('submitGenerateInvoice') && isset($order)) {
            if (!Configuration::get('PS_INVOICE', null, null, $order->id_shop)) {
                $this->errors[] = Tools::displayError('Invoice management has been disabled.');
            } elseif ($order->hasInvoice()) {
                $this->errors[] = Tools::displayError('This order already has an invoice.');
            } else {
                $order->setInvoice(true);
                Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
            }
        } elseif (Tools::isSubmit('submitDeleteVoucher') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                $order_cart_rule = new OrderCartRule(Tools::getValue('id_order_cart_rule'));
                if (Validate::isLoadedObject($order_cart_rule) && $order_cart_rule->id_order == $order->id) {
                    if ($order_cart_rule->id_order_invoice) {
                        $order_invoice = new OrderInvoice($order_cart_rule->id_order_invoice);
                        if (!Validate::isLoadedObject($order_invoice)) {
                            throw new PrestaShopException('Can\'t load Order Invoice object');
                        }

                        // Update amounts of Order Invoice
                        $order_invoice->total_discount_tax_excl = ($order_invoice->total_discount_tax_excl - $order_cart_rule->value_tax_excl) > 0 ? ($order_invoice->total_discount_tax_excl - $order_cart_rule->value_tax_excl) : 0;
                        $order_invoice->total_discount_tax_incl = ($order_invoice->total_discount_tax_incl - $order_cart_rule->value) > 0 ? ($order_invoice->total_discount_tax_incl - $order_cart_rule->value) : 0;

                        $order_invoice->total_paid_tax_excl += $order_cart_rule->value_tax_excl;
                        $order_invoice->total_paid_tax_incl += $order_cart_rule->value;

                        // Update Order Invoice
                        $order_invoice->update();
                    }

                    if ($order_cart_rule->delete()) {
                        // Update amounts of order
                        $order->total_discounts = ($order->total_discounts - $order_cart_rule->value) > 0 ? ($order->total_discounts - $order_cart_rule->value) : 0;
                        $order->total_discounts_tax_incl = ($order->total_discounts_tax_incl - $order_cart_rule->value) > 0 ? ($order->total_discounts_tax_incl - $order_cart_rule->value) : 0;
                        $order->total_discounts_tax_excl = ($order->total_discounts_tax_excl - $order_cart_rule->value_tax_excl) > 0 ? ($order->total_discounts_tax_excl - $order_cart_rule->value_tax_excl) : 0;

                        $order->total_paid = Tools::ps_round(
                            $order->getOrderTotal(),
                            _PS_PRICE_COMPUTE_PRECISION_
                        );
                        $order->total_paid_tax_incl = Tools::ps_round(
                            $order->getOrderTotal(),
                            _PS_PRICE_COMPUTE_PRECISION_
                        );
                        $order->total_paid_tax_excl = Tools::ps_round(
                            $order->getOrderTotal(false),
                            _PS_PRICE_COMPUTE_PRECISION_
                        );
                        $order->update();
                    }

                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                } else {
                    $this->errors[] = Tools::displayError('You cannot edit this cart rule.');
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif (Tools::isSubmit('submitNewVoucher') && isset($order)) {
            if ($this->tabAccess['edit'] === 1) {
                if (!Tools::getValue('discount_name')) {
                    $this->errors[] = Tools::displayError('You must specify a name in order to create a new discount.');
                } elseif ((float)Tools::getValue('discount_value') <= 0) {
                    $this->errors[] = Tools::displayError('The discount value is invalid.');
                } else {
                    if ($order->hasInvoice()) {
                        // If the discount is for only one invoice
                        if (!Tools::isSubmit('discount_all_invoices')) {
                            $order_invoice = new OrderInvoice(Tools::getValue('discount_invoice'));
                            if (!Validate::isLoadedObject($order_invoice)) {
                                throw new PrestaShopException('Can\'t load Order Invoice object');
                            }
                        }
                    }

                    $cart_rules = array();
                    $discount_value = (float)str_replace(',', '.', Tools::getValue('discount_value'));
                    switch (Tools::getValue('discount_type')) {
                        // Percent type
                        case 1:
                            if ($discount_value <= 100) {
                                if (isset($order_invoice)) {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($order_invoice->total_paid_tax_incl * $discount_value / 100, _PS_PRICE_COMPUTE_PRECISION_);
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($order_invoice->total_paid_tax_excl * $discount_value / 100, _PS_PRICE_COMPUTE_PRECISION_);

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                } elseif ($order->hasInvoice()) {
                                    $order_invoices_collection = $order->getInvoicesCollection();
                                    foreach ($order_invoices_collection as $order_invoice) {
                                        /** @var OrderInvoice $order_invoice */
                                        $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($order_invoice->total_paid_tax_incl * $discount_value / 100, _PS_PRICE_COMPUTE_PRECISION_);
                                        $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($order_invoice->total_paid_tax_excl * $discount_value / 100, _PS_PRICE_COMPUTE_PRECISION_);

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                    }
                                } else {
                                    $cart_rules[0]['value_tax_incl'] = Tools::ps_round($order->total_paid_tax_incl * $discount_value / 100, _PS_PRICE_COMPUTE_PRECISION_);
                                    $cart_rules[0]['value_tax_excl'] = Tools::ps_round($order->total_paid_tax_excl * $discount_value / 100, _PS_PRICE_COMPUTE_PRECISION_);
                                }
                            } else {
                                $this->errors[] = Tools::displayError('The discount value is invalid.');
                            }
                            break;
                        // Amount type
                        case 2:
                            if (isset($order_invoice)) {
                                if ($discount_value > $order_invoice->total_paid_tax_incl) {
                                    $this->errors[] = Tools::displayError('The discount value is greater than the order invoice total.');
                                } else {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($discount_value, _PS_PRICE_COMPUTE_PRECISION_);
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), _PS_PRICE_COMPUTE_PRECISION_);

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $order_invoices_collection = $order->getInvoicesCollection();
                                foreach ($order_invoices_collection as $order_invoice) {
                                    /** @var OrderInvoice $order_invoice */
                                    if ($discount_value > $order_invoice->total_paid_tax_incl) {
                                        $this->errors[] = Tools::displayError('The discount value is greater than the order invoice total.').$order_invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop).')';
                                    } else {
                                        $cart_rules[$order_invoice->id]['value_tax_incl'] = Tools::ps_round($discount_value, _PS_PRICE_COMPUTE_PRECISION_);
                                        $cart_rules[$order_invoice->id]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), _PS_PRICE_COMPUTE_PRECISION_);

                                        // Update OrderInvoice
                                        $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                    }
                                }
                            } else {
                                if ($discount_value > $order->total_paid_tax_incl) {
                                    $this->errors[] = Tools::displayError('The discount value is greater than the order total.');
                                } else {
                                    $cart_rules[0]['value_tax_incl'] = Tools::ps_round($discount_value, _PS_PRICE_COMPUTE_PRECISION_);
                                    $cart_rules[0]['value_tax_excl'] = Tools::ps_round($discount_value / (1 + ($order->getTaxesAverageUsed() / 100)), _PS_PRICE_COMPUTE_PRECISION_);
                                }
                            }
                            break;
                        // Free shipping type
                        case 3:
                            if (isset($order_invoice)) {
                                if ($order_invoice->total_shipping_tax_incl > 0) {
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = $order_invoice->total_shipping_tax_incl;
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = $order_invoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } elseif ($order->hasInvoice()) {
                                $order_invoices_collection = $order->getInvoicesCollection();
                                foreach ($order_invoices_collection as $order_invoice) {
                                    /** @var OrderInvoice $order_invoice */
                                    if ($order_invoice->total_shipping_tax_incl <= 0) {
                                        continue;
                                    }
                                    $cart_rules[$order_invoice->id]['value_tax_incl'] = $order_invoice->total_shipping_tax_incl;
                                    $cart_rules[$order_invoice->id]['value_tax_excl'] = $order_invoice->total_shipping_tax_excl;

                                    // Update OrderInvoice
                                    $this->applyDiscountOnInvoice($order_invoice, $cart_rules[$order_invoice->id]['value_tax_incl'], $cart_rules[$order_invoice->id]['value_tax_excl']);
                                }
                            } else {
                                $cart_rules[0]['value_tax_incl'] = $order->total_shipping_tax_incl;
                                $cart_rules[0]['value_tax_excl'] = $order->total_shipping_tax_excl;
                            }
                            break;
                        default:
                            $this->errors[] = Tools::displayError('The discount type is invalid.');
                    }

                    if (!count($this->errors)) {
                        $res = true;
                        foreach ($cart_rules as &$cart_rule) {
                            $cartRuleObj = new CartRule();
                            $cartRuleObj->date_from = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order->date_add)));
                            $cartRuleObj->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
                            $cartRuleObj->name[Configuration::get('PS_LANG_DEFAULT')] = Tools::getValue('discount_name');
                            $cartRuleObj->quantity = 0;
                            $cartRuleObj->quantity_per_user = 1;
                            if (Tools::getValue('discount_type') == 1) {
                                $cartRuleObj->reduction_percent = $discount_value;
                            } elseif (Tools::getValue('discount_type') == 2) {
                                $cartRuleObj->reduction_amount = $cart_rule['value_tax_excl'];
                            } elseif (Tools::getValue('discount_type') == 3) {
                                $cartRuleObj->free_shipping = 1;
                            }
                            $cartRuleObj->active = 0;
                            if ($res = $cartRuleObj->add()) {
                                $cart_rule['id'] = $cartRuleObj->id;
                                $cart_rule['free_shipping'] = $cartRuleObj->free_shipping;
                            } else {
                                break;
                            }
                        }

                        if ($res) {
                            foreach ($cart_rules as $id_order_invoice => $cart_rule) {
                                // Create OrderCartRule
                                $order_cart_rule = new OrderCartRule();
                                $order_cart_rule->id_order = $order->id;
                                $order_cart_rule->id_cart_rule = $cart_rule['id'];
                                $order_cart_rule->id_order_invoice = $id_order_invoice;
                                $order_cart_rule->name = Tools::getValue('discount_name');
                                $order_cart_rule->value = $cart_rule['value_tax_incl'];
                                $order_cart_rule->value_tax_excl = $cart_rule['value_tax_excl'];
                                $order_cart_rule->free_shipping = $cart_rule['free_shipping'];
                                $res &= $order_cart_rule->add();

                                $order->total_discounts = Tools::ps_round($order->total_discounts + $order_cart_rule->value, _PS_PRICE_COMPUTE_PRECISION_);
                                $order->total_discounts_tax_incl = Tools::ps_round($order->total_discounts_tax_incl + $order_cart_rule->value, _PS_PRICE_COMPUTE_PRECISION_);
                                $order->total_discounts_tax_excl = Tools::ps_round($order->total_discounts_tax_excl + $order_cart_rule->value_tax_excl, _PS_PRICE_COMPUTE_PRECISION_);

                                $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                                $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                                $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);
                            }

                            // Update Order
                            $res &= $order->update();
                        }

                        if ($res) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=4&token='.$this->token);
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred during the OrderCartRule creation');
                        }
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif (Tools::isSubmit('sendStateEmail') && Tools::getValue('sendStateEmail') > 0 && Tools::getValue('id_order') > 0) {
            if ($this->tabAccess['edit'] === 1) {
                $order_state = new OrderState((int)Tools::getValue('sendStateEmail'));

                if (!Validate::isLoadedObject($order_state)) {
                    $this->errors[] = Tools::displayError('An error occurred while loading order status.');
                } else {
                    $history = new OrderHistory((int)Tools::getValue('id_order_history'));

                    $carrier = new Carrier($order->id_carrier, $order->id_lang);
                    $templateVars = array();

                    if ($history->sendEmail($order, $templateVars)) {
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=10&token='.$this->token);
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the e-mail to the customer.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            }
        } elseif(Tools::getIsset('resolve_overbooking') && Tools::getValue('resolve_overbooking')) {
            $objHotelBookingDetail = new HotelBookingDetail();
            // resolve an overbooking manually
            if ($objHotelBookingDetail->resolveOverbookings(Tools::getValue('resolve_overbooking'))) {
                if (Tools::getValue('id_order')) {
                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.$order->id.'&vieworder&conf=51&token='.$this->token);
                } else {
                    Tools::redirectAdmin(self::$currentIndex.'&conf=51&token='.$this->token);
                }
            } else {
                $this->errors = Tools::displayError('An error occurred while resolving overbooking.');
            }
        // Set old orders address as current order address
        } elseif (Tools::getValue('action') && Tools::getValue('action') == 'set_old_orders_address') {
            if ($idOrder = Tools::getValue('id_order')) {
                if (Validate::isLoadedObject($objOrder = new Order($idOrder))) {
                    $idAddressInvoice = $objOrder->id_address_invoice;
                    if ($idAddressInvoice && Validate::isLoadedObject(new Address($idAddressInvoice))) {
                        // get all customer orders and set old orders address as current order address
                        if ($customerOrders = Order::getCustomerOrders($objOrder->id_customer)) {
                            foreach ($customerOrders as $order) {
                                $objOrder = new Order($order['id_order']);
                                $objOrder->id_address_invoice = $idAddressInvoice;
                                $objOrder->update();
                            }
                        }
                        Tools::redirectAdmin(self::$currentIndex.'&id_order='.$idOrder.'&vieworder&conf=6&token='.$this->token);
                    } else {
                        $this->errors = Tools::displayError('Address not found. Please try again.');
                    }
                } else {
                    $this->errors = Tools::displayError('Order not found. Please try again.');
                }
            } else {
                $this->errors = Tools::displayError('Order not found. Please try again.');
            }
        // Set customer current active address as current order address
        } elseif (Tools::getValue('action') && Tools::getValue('action') == 'set_address_current_address') {
            if ($idOrder = Tools::getValue('id_order')) {
                if (Validate::isLoadedObject($objOrder = new Order($idOrder))) {
                    $idAddressInvoice = $objOrder->id_address_invoice;
                    if ($idAddressInvoice && Validate::isLoadedObject($objOrderAddress = new Address($idAddressInvoice))) {
                        $updateAddress = 1;
                        if ($idCustomerAddress = Customer::getCustomerIdAddress($objOrder->id_customer)) {
                            // only proceed if current active address and current order address are different
                            if ($idCustomerAddress != $idAddressInvoice) {
                                if (Validate::isLoadedObject($objActiveAddress = new Address($idCustomerAddress))) {
                                    // set current active address information from order address
                                    $objOrderAddress->id = $objOrderAddress->id_address = $objActiveAddress->id;
                                    $objOrderAddress->deleted = 0;
                                    $objOrderAddress->update();
                                } else {
                                    $this->errors = Tools::displayError('Customer address not found. Please try again.');
                                }
                            }
                        } else {
                            // If customer has no active address, create new customer address with the order address
                            $objOrderAddress->id = $objOrderAddress->id_address = null;
                            $objOrderAddress->deleted = 0;
                            $objOrderAddress->add();
                        }

                        if (!count($this->errors)) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$idOrder.'&vieworder&conf=6&token='.$this->token);
                        }
                    } else {
                        $this->errors = Tools::displayError('Address not found. Please try again.');
                    }
                } else {
                    $this->errors = Tools::displayError('Order not found. Please try again.');
                }
            } else {
                $this->errors = Tools::displayError('Order not found. Please try again.');
            }
        } elseif (Tools::getValue('action') && Tools::getValue('action') == 'set_order_active_address') {
            if ($idOrder = Tools::getValue('id_order')) {
                if (Validate::isLoadedObject($objOrder = new Order($idOrder))) {
                    // get currenct active address of the customer
                    if ($idCustomerAddress = Customer::getCustomerIdAddress($objOrder->id_customer)) {
                        $objOrder->id_address_invoice = $idCustomerAddress;
                        if ($objOrder->update()) {
                            Tools::redirectAdmin(self::$currentIndex.'&id_order='.$idOrder.'&vieworder&conf=6&token='.$this->token);
                        } else {
                            $this->errors[] = Tools::displayError('Some error occurred while updating address detail. Please try again.');
                        }
                    } else {
                        $this->errors = Tools::displayError('Customer address not found. Please try again.');
                    }
                } else {
                    $this->errors = Tools::displayError('Order not found. Please try again.');
                }
            } else {
                $this->errors = Tools::displayError('Order not found. Please try again.');
            }
        }

        // Sending loader image for the modals to be used for all the modals ajax processes
        $this->context->smarty->assign('loaderImg', $this->context->link->getMediaLink(_PS_IMG_.'admin/ajax-loader.gif'));

        parent::postProcess();
    }

    // KPIs on view page
    public function renderKpis()
    {
        $kpis = array();
        $objOrder = new Order(Tools::getValue('id_order'));
        if (Validate::isLoadedObject($objOrder)) {
            $helper = new HelperKpi();
            $helper->id = 'box-order-date';
            $helper->icon = 'icon-calendar';
            $helper->color = 'color1';
            $helper->title = $this->l('Order Date');
            $helper->tooltip = $this->l('The date when the order was placed.');
            $helper->value = Tools::displayDate($objOrder->date_add);
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-order-total';
            $helper->icon = 'icon-money';
            $helper->color = 'color3';
            $helper->title = $this->l('Bookings Total');
            $helper->tooltip = $this->l('Total booking amount of this order.');
            $helper->value = Tools::displayPrice($objOrder->total_paid_tax_incl, new Currency($objOrder->id_currency));
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-total-paid';
            $helper->icon = 'icon-money';
            $helper->color = 'color4';
            $helper->title = $this->l('Total Paid');
            $helper->tooltip = $this->l('Total paid is the amount which customer has already paid for this order.');
            $helper->value = Tools::displayPrice($objOrder->total_paid_real, new Currency($objOrder->id_currency));
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-total-due';
            $helper->icon = 'icon-money';
            $helper->color = 'color2';
            $helper->title = $this->l('Total Due');
            $helper->tooltip = $this->l('Total due is the amount which customer has to pay for this order.');
            $helper->value = Tools::displayPrice(($objOrder->total_paid_tax_incl - $objOrder->total_paid_real), new Currency($objOrder->id_currency));
            $this->kpis[] = $helper;

            $orderHistory = array_reverse($objOrder->getHistory($this->context->language->id));
            $helper = new HelperKpi();
            $helper->id = 'box-order-source';
            $helper->icon = 'icon-globe';
            $helper->color = 'color1';
            $helper->title = $this->l('Order Source');
            $helper->tooltip = $this->l('Order source shows from which source the order was placed.');
            $helper->subtitle = $orderHistory[0]['id_employee'] ? $this->l('Back office') : $this->l('Front office');
            $helper->value = $objOrder->source;
            $this->kpis[] = $helper;

            $objCustomerThread = new CustomerThread();
            $idCustomerThread = $objCustomerThread->getIdCustomerThreadByIdOrder($objOrder->id);
            $helper = new HelperKpi();
            $helper->id = 'box-messages';
            $helper->icon = 'icon-comments';
            $helper->color = 'color2';
            $helper->title = $this->l('Messages');
            $helper->tooltip = $this->l('Messages is the number of customer messages for this order.');
            $helper->href = $this->context->link->getAdminLink('AdminCustomerThreads').((int) $idCustomerThread ? '&viewcustomer_thread&id_customer_thread='.(int) $idCustomerThread : '');
            $helper->value = count(CustomerThread::getCustomerMessages($objOrder->id_customer, null, $objOrder->id, CustomerMessage::QLO_CUSTOMER_MESSAGE_BY_CUSTOMER));
            $this->kpis[] = $helper;

            $objHotelBookingDetail = new HotelBookingDetail();
            $numRooms = count($objHotelBookingDetail->getBookingDataByOrderId($objOrder->id));
            $helper = new HelperKpi();
            $helper->id = 'box-total-rooms';
            $helper->icon = 'icon-home';
            $helper->color = 'color1';
            $helper->title = $this->l('Total Rooms');
            $helper->tooltip = $this->l('Total rooms is the number of rooms booked in this order.');
            $helper->href = '#start_products';
            $helper->value = $numRooms;
            $this->kpis[] = $helper;

            // if order has normal products the add kpi for total products
            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
            $hotelProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($objOrder->id, 0, 0, Product::SELLING_PREFERENCE_HOTEL_STANDALONE);
            $standaloneProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($objOrder->id, 0, 0, Product::SELLING_PREFERENCE_STANDALONE);

            if ($hotelProducts || $standaloneProducts) {
                $helper = new HelperKpi();
                $helper->id = 'box-total-products';
                $helper->icon = 'icon-home';
                $helper->color = 'color1';
                $helper->title = $this->l('Total Products');
                $helper->tooltip = $this->l('Total products is the number of product quantities booked in this order.');
                $helper->href = '#start_products';

                $totalProductQuantity = 0;
                if ($hotelProducts) {
                    $totalProductQuantity += array_sum(array_column($hotelProducts, 'quantity'));
                }
                if ($standaloneProducts) {
                    $totalProductQuantity += array_sum(array_column($standaloneProducts, 'quantity'));
                }

                $helper->value = $totalProductQuantity;
                $this->kpis[] = $helper;
            }

            $helper = new HelperKpi();
            if (isset($orderHistory[0]['id_order_state']) && ($orderHistory[0]['id_order_state'] == Configuration::get('PS_OS_PAYMENT_ACCEPTED'))) {
                $initialPaymentStatus = $this->l('Full Payment');
            } elseif (isset($orderHistory[0]['id_order_state']) && ($orderHistory[0]['id_order_state'] == Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED'))) {
                $initialPaymentStatus = $this->l('Partial Payment');
            } else {
                $initialPaymentStatus = $this->l('No Payment Received');
            }
            $helper->id = 'box-payment-status';
            $helper->icon = 'icon-home';
            $helper->color = 'color3';
            $helper->title = $this->l('Initial Payment Status');
            $helper->tooltip = $this->l('Initial Payment Status defines whether the order was fully paid, partially paid, or had no payment received at the time the order was placed.');
            $helper->value = $initialPaymentStatus;
            $this->kpis[] = $helper;

            if ($objOrder->is_advance_payment) {
                $helper = new HelperKpi();
                $helper->id = 'box-partial-payment-amount';
                $helper->icon = 'icon-money';
                $helper->color = 'color4';
                $helper->title = $this->l('Partial Payment Amount');
                $helper->tooltip = $this->l('Partial payment amount refers to the amount that customer has to pay as advance payment, if payment type selected as partial payment while placing this order.');
                $helper->value = Tools::displayPrice($objOrder->advance_paid_amount, new Currency($objOrder->id_currency));
                $this->kpis[] = $helper;
            }

            $objOrderReturn = new OrderReturn();
            $refundReqProducts = $objOrderReturn->getOrderRefundRequestedProducts($objOrder->id, 0, 1);
            $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($objOrder->id, 0, 1);
            if ($refundReqProducts || $refundReqBookings) {
                $refundedAmount = $objOrderReturn->getRefundedAmount($objOrder->id);
                $helper = new HelperKpi();
                $helper->id = 'box-payment-type';
                $helper->icon = 'icon-money';
                $helper->color = 'color3';
                $helper->title = $this->l('Refunded Amount');
                $helper->tooltip = $this->l('Refunded amount refers to the amount that has been refunded for this order.');
                $helper->value = Tools::displayPrice($refundedAmount, new Currency($objOrder->id_currency));
                $this->kpis[] = $helper;
            }
        } else {
            $time = time();
            $kpis = array();

            $helper = new HelperKpi();
            $helper->id = 'box-total-sales';
            $helper->icon = 'icon-money';
            $helper->color = 'color4';
            $helper->title = $this->l('Total Sales', null, null, false);
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=total_sales';
            $helper->tooltip = $this->l('Total sales/revenue of all the orders created.', null, null, false);
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-total-due-amount';
            $helper->icon = 'icon-money';
            $helper->color = 'color2';
            $helper->title = $this->l('Total Due Amount', null, null, false);
            $minValue = ('0.' . str_repeat('0', (Configuration::get('PS_PRICE_DISPLAY_PRECISION') - 1)) . '1');

            // get all valid order states for due amount calculations
            $validOrderStatesFilter = '';
            if ($allOrderStates = OrderState::getOrderStates($this->context->language->id)) {
                $allOrderStates = array_column($allOrderStates, 'id_order_state');
                $objHotelBooking = new HotelBookingDetail();
                $invalidOrderStates = $objHotelBooking->getOrderStatusToFreeBookedRoom();

                if ($validOrderStates = array_diff($allOrderStates, $invalidOrderStates)) {
                    foreach ($validOrderStates as $idOrderState) {
                        $validOrderStatesFilter .= '&orderFilter_os!id_order_state[]='.$idOrderState;
                    }
                }
            }
            $helper->href = $this->context->link->getAdminLink('AdminOrders').'&submitResetorder&submitFilterorder=1&orderFilter_amount_due%5B0%5D='.$minValue.$validOrderStatesFilter;
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=total_due_amount';
            $helper->tooltip = $this->l('Total due amount of all the orders created.', null, null, false);
            $this->kpis[] = $helper;

            $arivalDate = date('Y-m-d');
            $helper = new HelperKpi();
            $helper->id = 'box-today-arrivals';
            $helper->icon = 'icon-user';
            $helper->color = 'color1';
            $helper->title = $this->l('Arrivals', null, null, false);
            $helper->subtitle = $this->l('Today', null, null, false);
            $helper->href = $this->context->link->getAdminLink('AdminOrders').'&submitResetorder&submitFilterorder=1&orderFilter_hbd!is_refunded=0&orderFilter_hbd!id_status='.HotelBookingDetail::STATUS_ALLOTED.'&orderFilter_hbd!date_from[]='.pSQL(date('Y-m-d')).'&orderFilter_hbd!date_from[]='.pSQL(date('Y-m-d'));
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=today_arrivals';
            $helper->tooltip = $this->l('Total number of arrivals for today.', null, null, false);
            $this->kpis[] = $helper;

            $departureDate = date('Y-m-d');
            $helper = new HelperKpi();
            $helper->id = 'box-today-departures';
            $helper->icon = 'icon-user';
            $helper->color = 'color2';
            $helper->title = $this->l('Departures', null, null, false);
            $helper->subtitle = $this->l('Today', null, null, false);
            $helper->href = $this->context->link->getAdminLink('AdminOrders').'&submitResetorder&submitFilterorder=1&orderFilter_hbd!is_refunded=0&orderFilter_hbd!id_status='.HotelBookingDetail::STATUS_CHECKED_IN.'&orderFilter_hbd!date_to[]='.pSQL(date('Y-m-d')).'&orderFilter_hbd!date_to[]='.pSQL(date('Y-m-d'));
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=today_departures';
            $helper->tooltip = $this->l('Total number of departures for today.', null, null, false);
            $this->kpis[] = $helper;

            $dateFrom = date('Y-m-d');
            $dateTo = date('Y-m-d', strtotime('+1 day'));
            $helper = new HelperKpi();
            $helper->id = 'box-today-stay-over';
            $helper->icon = 'icon-user';
            $helper->color = 'color4';
            $helper->title = $this->l('Occupied Rooms', null, null, false);
            $helper->subtitle = $this->l('Today', null, null, false);
            $helper->href = $this->context->link->getAdminLink('AdminOrders').'&submitResetorder&submitFilterorder=1&orderFilter_hbd!is_refunded=0&orderFilter_hbd!id_status='.HotelBookingDetail::STATUS_CHECKED_IN.'&orderFilter_hbd!date_to[]='.pSQL(date('Y-m-d', strtotime('+ 1 days'))).'&orderFilter_hbd!date_to[]=';
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=occupied_rooms';
            $helper->tooltip = $this->l('The count of rooms that are currently occupied by guests.', null, null, false);
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-carts';
            $helper->icon = 'icon-shopping-cart';
            $helper->color = 'color2';
            $helper->title = $this->l('Abandoned Carts', null, null, false);
            $dateFrom = date(Context::getContext()->language->date_format_lite, strtotime('-2 day'));
            $dateTo = date(Context::getContext()->language->date_format_lite, strtotime('-1 day'));
            $helper->subtitle = sprintf($this->l('From %s to %s', null, null, false), $dateFrom, $dateTo);
            $helper->href = $this->context->link->getAdminLink('AdminCarts').'&action=filterOnlyAbandonedCarts&date_from='.date('Y-m-d', strtotime('-2 day')).'&date_to='.date('Y-m-d', strtotime('-1 day'));
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=abandoned_cart';
            $helper->tooltip = $this->l('Total number of abandoned carts  in given period of time.', null, null, false);
            $this->kpis[] = $helper;

            $daysForConversionRate = Configuration::get('PS_KPI_CONVERSION_RATE_NB_DAYS');
            $helper = new HelperKpi();
            $helper->id = 'box-conversion-rate';
            $helper->icon = 'icon-sort-by-attributes-alt';
            $helper->color = 'color1';
            $helper->title = $this->l('Conversion Rate', null, null, false);
            if ($daysForConversionRate == 1) {
                $helper->subtitle = $daysForConversionRate.' '.$this->l('day', null, null, false);
            } else {
                $helper->subtitle = $daysForConversionRate.' '.$this->l('days', null, null, false);
            }
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=conversion_rate';
            $helper->tooltip = sprintf($this->l('Percentage of visits that resulted in an order/booking in last %s day(s).', null, null, false), $daysForConversionRate);
            $this->kpis[] = $helper;

            $daysForAvgOrderVal = Configuration::get('PS_ORDER_KPI_AVG_ORDER_VALUE_NB_DAYS');
            $helper = new HelperKpi();
            $helper->id = 'box-average-order';
            $helper->icon = 'icon-money';
            $helper->color = 'color3';
            $helper->title = $this->l('Average Order Value', null, null, false);
            if ($daysForAvgOrderVal == 1) {
                $helper->subtitle = $daysForAvgOrderVal.' '.$this->l('day', null, null, false);
            } else {
                $helper->subtitle = $daysForAvgOrderVal.' '.$this->l('days', null, null, false);
            }
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=average_order_value';
            $helper->tooltip = sprintf($this->l('Total average order value without tax in the last %s day(s).', null, null, false), $daysForAvgOrderVal);
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-average-lead-time';
            $helper->icon = 'icon-time';
            $helper->color = 'color4';
            $helper->title = $this->l('Average Lead Time', null, null, false);
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=average_lead_time';
            $helper->tooltip = $this->l('Average number of days between the time guests book their rooms and the time guest schedule to arrive at the hotel.', null, null, false);
            $this->kpis[] = $helper;

            $helper = new HelperKpi();
            $helper->id = 'box-average-guest-in-booking';
            $helper->icon = 'icon-users';
            $helper->color = 'color3';
            $helper->title = $this->l('Average Guest Per Booking', null, null, false);
            $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=average_guest_in_booking';
            $helper->tooltip = $this->l('Average number of guests per booking.', null, null, false);
            $this->kpis[] = $helper;
        }

        Hook::exec('action'.$this->controller_name.'KPIListingModifier', array(
            'kpis' => &$kpis,
        ));

        return parent::renderKpis();
    }

    public function renderView()
    {
        if (!Validate::isLoadedObject($order = new Order(Tools::getValue('id_order')))) {
            return;
        }

        $this->content .= $this->renderKpis();

        $customer = new Customer($order->id_customer);
        $carrier = new Carrier($order->id_carrier);
        $products = $this->getProducts($order);
        $currency = new Currency((int)$order->id_currency);
        // Carrier module call
        $carrier_module_call = null;
        if ($carrier->is_module) {
            $module = Module::getInstanceByName($carrier->external_module_name);
            if (method_exists($module, 'displayInfoByCart')) {
                $carrier_module_call = call_user_func(array($module, 'displayInfoByCart'), $order->id_cart);
            }
        }

        // Retrieve addresses information
        $addressInvoice = new Address($order->id_address_invoice, $this->context->language->id);
        if (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) {
            $invoiceState = new State((int)$addressInvoice->id_state);
        }

        if ($order->id_address_invoice == $order->id_address_delivery) {
            $addressDelivery = $addressInvoice;
            if (isset($invoiceState)) {
                $deliveryState = $invoiceState;
            }
        } else {
            $addressDelivery = new Address($order->id_address_delivery, $this->context->language->id);
            if (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) {
                $deliveryState = new State((int)($addressDelivery->id_state));
            }
        }

        $addressTax = new Address($order->id_address_tax, $this->context->language->id);
        $hotelBooking = $addressTax->id_hotel;

        if ($addressTax->id_hotel) {
            $objHotelBranchInformation = new HotelBranchInformation($addressTax->id_hotel, $this->context->language->id);
            $this->toolbar_title = sprintf($this->l('Order %1$s - %2$s'), $order->reference, $objHotelBranchInformation->hotel_name);
        } else {
            $this->toolbar_title = sprintf($this->l('Order %1$s'), $order->reference);
        }
        if (Shop::isFeatureActive()) {
            $shop = new Shop((int)$order->id_shop);
            $this->toolbar_title .= ' - '.sprintf($this->l('Shop: %s'), $shop->name);
        }

        // get details if booking is done for some other guest
        $customerGuestDetail = false;
        if ($id_customer_guest_detail = OrderCustomerGuestDetail::isCustomerGuestBooking($order->id)) {
            $customerGuestDetail = new OrderCustomerGuestDetail($id_customer_guest_detail);
            $customerGuestDetail->gender = new Gender($customerGuestDetail->id_gender, $this->context->language->id);
        }

        // gets warehouses to ship products, if and only if advanced stock management is activated
        $warehouse_list = null;

        $order_details = $order->getOrderDetailList();
        foreach ($order_details as $order_detail) {
            $product = new Product($order_detail['product_id']);

            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
                && $product->advanced_stock_management
            ) {
                $warehouses = Warehouse::getWarehousesByProductId($order_detail['product_id'], $order_detail['product_attribute_id']);
                foreach ($warehouses as $warehouse) {
                    if (!isset($warehouse_list[$warehouse['id_warehouse']])) {
                        $warehouse_list[$warehouse['id_warehouse']] = $warehouse;
                    }
                }
            }
        }

        $payment_methods = array();
        foreach (PaymentModule::getInstalledPaymentModules() as $payment) {
            $module = Module::getInstanceByName($payment['name']);
            if (Validate::isLoadedObject($module) && $module->active) {
                $payment_methods[] = $module->displayName;
            }
        }
        // display warning if there are products out of stock
        $display_out_of_stock_warning = false;
        $current_order_state = $order->getCurrentOrderState();
        if (Configuration::get('PS_STOCK_MANAGEMENT') && (!Validate::isLoadedObject($current_order_state) || ($current_order_state->delivery != 1 && $current_order_state->shipped != 1))) {
            $display_out_of_stock_warning = true;
        }

        $objServiceProductOrderDetail = new ServiceProductOrderDetail();
        $orderHotelServiceProducts = array();
        $orderStandaloneServiceProducts = array();
        // products current stock (from stock_available)
        foreach ($products as &$product) {
            // Get total customized quantity for current product
            $customized_product_quantity = 0;

            if (is_array($product['customizedDatas'])) {
                foreach ($product['customizedDatas'] as $customizationPerAddress) {
                    foreach ($customizationPerAddress as $customizationId => $customization) {
                        $customized_product_quantity += (int)$customization['quantity'];
                    }
                }
            }

            $product['customized_product_quantity'] = $customized_product_quantity;
            $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
            $resume = OrderSlip::getProductSlipResume($product['id_order_detail']);
            $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
            $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
            $product['amount_refundable_tax_incl'] = $product['total_price_tax_incl'] - $resume['amount_tax_incl'];
            $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl'], $currency);

            // if the current stock requires a warning
            // if ($product['current_stock'] <= 0 && $display_out_of_stock_warning) {
            //     $this->displayWarning($this->l('This booked room type is not found: ').' '.$product['product_name']);
            // }
            if ($product['id_warehouse'] != 0) {
                $warehouse = new Warehouse((int)$product['id_warehouse']);
                $product['warehouse_name'] = $warehouse->name;
                $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
                if (!empty($warehouse_location)) {
                    $product['warehouse_location'] = $warehouse_location;
                } else {
                    $product['warehouse_location'] = false;
                }
            } else {
                $product['warehouse_name'] = '--';
                $product['warehouse_location'] = false;
            }

            $objProduct = new Product($product['product_id'], false, $this->context->language->id);
            if ($coverImageInfo = Product::getCover($product['product_id'])) {
                $product['image_link'] = $this->context->link->getImageLink($objProduct->link_rewrite, $objProduct->id.'-'.$coverImageInfo['id_image'], 'small_default');
            } else {
                $product['image_link'] = $this->context->link->getImageLink($objProduct->link_rewrite, $this->context->language->iso_code.'-default', 'small_default');
            }
            if ($product['selling_preference_type'] == Product::SELLING_PREFERENCE_HOTEL_STANDALONE) {
                $hotelProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($order->id, $product['id_order_detail'], $product['product_id'], Product::SELLING_PREFERENCE_HOTEL_STANDALONE);
                foreach ($hotelProducts as $hotelProduct) {
                    $orderHotelServiceProducts[] = array_merge($product, $hotelProduct);
                }
            }
            if ($product['selling_preference_type'] == Product::SELLING_PREFERENCE_STANDALONE) {
                $standaloneProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($order->id, $product['id_order_detail'], $product['product_id'], Product::SELLING_PREFERENCE_STANDALONE);
                foreach ($standaloneProducts as $standaloneProduct) {
                    $orderStandaloneServiceProducts[] = array_merge($product, $standaloneProduct);
                }
            }
        }

        $gender = new Gender((int)$customer->id_gender, $this->context->language->id);

        $history = $order->getHistory($this->context->language->id);

        foreach ($history as &$order_state) {
            $order_state['text-color'] = Tools::getBrightness($order_state['color']) < 128 ? 'white' : 'black';
        }

        $order_payment_detail = $order->getOrderPaymentDetail();
        foreach ($order_payment_detail as &$payment_detail) {
            $payment = new OrderPayment($payment_detail['id_order_payment']);
            if ($invoice = $payment->getOrderInvoice($order->id)) {
                $payment_detail['invoice_number'] = $invoice->getInvoiceNumberFormatted($this->context->language->id, $order->id_shop);
            }
        }


        //To get data to show hotel rooms order data on order detail page
        $cart_id = Cart::getCartIdByOrderId(Tools::getValue('id_order'));
        $cart_detail_data_obj = new HotelCartBookingData();
        $objBookingDetail = new HotelBookingDetail();

        $total_room_tax = 0;
        $totalRoomsCostTE = 0;
        $totalDemandsPriceTE = 0;
        $totalDemandsPriceTI = 0;
        $totalRefundedRooms = 0;
        $orderConvenienceFeeServices = array();
        if ($order_detail_data = $objBookingDetail->getOrderFormatedBookinInfoByIdOrder($order->id)) {
            $objBookingDemand = new HotelBookingDemands();
            $objHotelRoomType = new HotelRoomType();
            foreach ($order_detail_data as $key => $value) {
                $order_detail_data[$key]['total_room_price_te'] = $value['total_price_tax_excl'];
                $order_detail_data[$key]['total_room_price_ti'] = $value['total_price_tax_incl'];

                $order_detail_data[$key]['extra_demands'] = $objBookingDemand->getRoomTypeBookingExtraDemands(
                    $order->id,
                    $value['id_product'],
                    $value['id_room'],
                    $value['date_from'],
                    $value['date_to'],
                    1,
                    0,
                    1,
                    $value['id']
                );
                $order_detail_data[$key]['total_room_price_ti'] += $order_detail_data[$key]['extra_demands_price_ti'] = $objBookingDemand->getRoomTypeBookingExtraDemands(
                    $order->id,
                    $value['id_product'],
                    $value['id_room'],
                    $value['date_from'],
                    $value['date_to'],
                    0,
                    1,
                    1,
                    $value['id']
                );
                $totalDemandsPriceTI += $order_detail_data[$key]['extra_demands_price_ti'];
                $order_detail_data[$key]['total_room_price_te'] += $order_detail_data[$key]['extra_demands_price_te'] = $objBookingDemand->getRoomTypeBookingExtraDemands(
                    $order->id,
                    $value['id_product'],
                    $value['id_room'],
                    $value['date_from'],
                    $value['date_to'],
                    0,
                    1,
                    0,
                    $value['id']
                );
                $totalDemandsPriceTE += $order_detail_data[$key]['extra_demands_price_te'];

                $order_detail_data[$key]['additional_services'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    null,
                    null,
                    null,
                    0,
                    $value['id']
                );
                $order_detail_data[$key]['total_room_price_ti'] += $order_detail_data[$key]['additional_services_price_ti'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    1,
                    0,
                    null,
                    0,
                    $value['id']
                );
                $order_detail_data[$key]['total_room_price_te'] += $order_detail_data[$key]['additional_services_price_te'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    0,
                    0,
                    null,
                    0,
                    $value['id']
                );

                $order_detail_data[$key]['convenience_fee_ti'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    1,
                    1,
                    Product::PRICE_ADDITION_TYPE_INDEPENDENT,
                    0,
                    $value['id']
                );
                $order_detail_data[$key]['total_room_price_ti'] += $order_detail_data[$key]['convenience_fee_ti'];

                $order_detail_data[$key]['convenience_fee_te'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    0,
                    1,
                    Product::PRICE_ADDITION_TYPE_INDEPENDENT,
                    0,
                    $value['id']
                );

                $order_detail_data[$key]['total_room_price_te'] += $order_detail_data[$key]['convenience_fee_te'];

                $order_detail_data[$key]['additional_services_price_auto_add_ti'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    1,
                    1,
                    Product::PRICE_ADDITION_TYPE_WITH_ROOM,
                    0,
                    $value['id']
                );
                $order_detail_data[$key]['total_room_price_ti'] += $order_detail_data[$key]['additional_services_price_auto_add_ti'];

                $order_detail_data[$key]['additional_services_price_auto_add_te'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1,
                    0,
                    1,
                    Product::PRICE_ADDITION_TYPE_WITH_ROOM,
                    0,
                    $value['id']
                );
                $order_detail_data[$key]['total_room_price_te'] += $order_detail_data[$key]['additional_services_price_auto_add_te'];

                $cust_obj = new Customer($value['id_customer']);
                if ($cust_obj->firstname) {
                    $order_detail_data[$key]['alloted_cust_name'] = $cust_obj->firstname.' '.$cust_obj->lastname;
                } else {
                    $order_detail_data[$key]['alloted_cust_name'] = $this->l('No customer name found');
                }
                if ($cust_obj->email) {
                    $order_detail_data[$key]['alloted_cust_email'] = $cust_obj->email;
                } else {
                    $order_detail_data[$key]['alloted_cust_email'] = $this->l('No customer name found');
                }

                $order_detail_data[$key]['avail_room_types_to_realloc'] = $objBookingDetail->getAvailableRoomsForReallocation($value['date_from'], $value['date_to'], 0, $value['id_hotel'], 1);
                $order_detail_data[$key]['avail_rooms_to_swap'] = $objBookingDetail->getAvailableRoomsForSwapping($value['date_from'], $value['date_to'], $value['id_product'], $value['id_hotel'], $value['id_room']);

                /*Product price when order was created*/
                $totalRoomsCostTE += $value['total_price_tax_excl'];
                $total_room_tax += $value['total_price_tax_incl']-$value['total_price_tax_excl'];
                $num_days = HotelHelper::getNumberOfDays($value['date_from'], $value['date_to']);
                $order_detail_data[$key]['unit_amt_tax_excl'] = $value['total_price_tax_excl']/$num_days;
                $order_detail_data[$key]['unit_amt_tax_incl'] = $value['total_price_tax_incl']/$num_days;
                $order_detail_data[$key]['amt_with_qty_tax_excl'] = $value['total_price_tax_excl'];
                $order_detail_data[$key]['amt_with_qty_tax_incl'] = $value['total_price_tax_incl'];
                $order_detail_data[$key]['room_type_info'] = $objHotelRoomType->getRoomTypeInfoByIdProduct($value['id_product']);
                $order_detail_data[$key]['total_room_tax'] = $order_detail_data[$key]['total_room_price_ti'] - $order_detail_data[$key]['total_room_price_te'];

                if (isset($value['refund_info'])
                    && $value['refund_info']['refunded']
                    && $value['refund_info']['id_customization']
                    && $value['is_refunded']
                    && !$value['is_cancelled']
                ) {
                    $totalRefundedRooms += 1;
                }
            }
        } else {
            $order_detail_data = array();
        }

        if ($orderedRooms = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
            $order->id,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            null,
            1,
            Product::PRICE_ADDITION_TYPE_INDEPENDENT
        )) {
            foreach($orderedRooms as $orderedRoom) {
                if (isset($orderedRoom['additional_services']) && $orderedRoom['additional_services']) {
                    foreach ($orderedRoom['additional_services'] as $service) {
                        if (isset($orderConvenienceFeeServices[$service['id_product']])) {
                            $orderConvenienceFeeServices[$service['id_product']]['total_price_tax_excl'] += $service['total_price_tax_excl'];
                        } else {
                            $orderConvenienceFeeServices[$service['id_product']]['name'] = $service['name'];
                            $orderConvenienceFeeServices[$service['id_product']]['total_price_tax_excl'] = $service['total_price_tax_excl'];
                        }
                    }
                }
            }
        }


        $objOrderReturn = new OrderReturn();
        $refundedAmount = $objOrderReturn->getRefundedAmount($order->id);
        $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($order->id, 0, 1, 0, 1);
        $refundReqProducts = $objOrderReturn->getOrderRefundRequestedProducts($order->id, 0, 1, 1);

        // get booking information by order
        $bookingOrderInfo = $objBookingDetail->getBookingDataByOrderId($order->id);
        foreach ($bookingOrderInfo as &$bookingOrderRoomInfo) {
            // Get last refund request for booking
            if ($bookingRefundDetail = OrderReturn::getOrdersReturnDetail($bookingOrderRoomInfo['id_order'], 0, $bookingOrderRoomInfo['id'])) {
                $bookingRefundDetail = reset($bookingRefundDetail);
                if (!$bookingRefundDetail['id_customization']) {
                    $bookingOrderRoomInfo['is_refunded'] = 0;
                }
            }
            $bookingOrderRoomInfo['num_checkin_documents'] = HotelBookingDocument::getCountByIdHtlBooking($bookingOrderRoomInfo['id']);
        }

        $objHotelBookingDetail = new HotelBookingDetail();
        $htlBookingDetail = $objHotelBookingDetail->getOrderCurrentDataByOrderId($order->id);

        // hotel booking statuses
        $htlOrderStatus = HotelBookingDetail::getAllHotelOrderStatus();

        if ($this->context->employee->isSuperAdmin()) {
            $backOrderConfigKey = 'PS_BACKDATE_ORDER_SUPERADMIN';
        } else {
            $backOrderConfigKey = 'PS_BACKDATE_ORDER_EMPLOYEES';
        }
        $allowBackdateOrder = Configuration::get($backOrderConfigKey);

        // applicable refund policies
        $applicableRefundPolicies = HotelOrderRefundRules::getApplicableRefundRules($order->id);

        // Overbookings information of the order
        $orderOverBookings = $objHotelBookingDetail->getOverbookedRooms($order->id, 0, '', '', 0, 0, 1);

        // Guest address info
        $guestFormattedAddress = '';
        $idOrderAddressInvoice = 0;
        $ordersWithDiffInvAddr = 0;
        $idCurrentAddress = Customer::getCustomerIdAddress($order->id_customer);
        if ($order->id_address_invoice && Validate::isLoadedObject($objGuestAddress = new Address($order->id_address_invoice))) {
            $idOrderAddressInvoice = $order->id_address_invoice;
            $guestFormattedAddress = AddressFormat::generateAddress($objGuestAddress, array(), "<br />");

            // get customer orders without same id_address_invoice
            if ($customerOrders = Order::getCustomerOrders($order->id_customer, false, null, $idOrderAddressInvoice, 1)) {
                if ($customerOrders > 1) {
                    $ordersWithDiffInvAddr = 1;
                }
            }
        }

        $objCustomerThread = new CustomerThread();
        $messages = Message::getMessagesByOrderId($order->id, true);
        if ($customerMessages = CustomerMessage::getMessagesByOrderId($order->id, true)) {
            foreach ($messages as $messageKey => $message) {
                foreach ($customerMessages as $customerMessageKey => $customerMessage) {
                    if (strcmp($message['message'], $customerMessage['message']) == 0) {
                        unset($messages[$messageKey]);
                    }
                }
            }

            $messages = array_merge($customerMessages, $messages);
            usort($messages, function ($a, $b) {
                return strtotime($a['date_add']) < strtotime($b['date_add']);
            });
        }

        // send hotel standalone and standalone products
        $objProduct = new Product();
        $hotelStandaloneProducts = $objProduct->getServiceProducts(null, Product::SELLING_PREFERENCE_HOTEL_STANDALONE);
        $standaloneProducts = $objProduct->getServiceProducts(null, Product::SELLING_PREFERENCE_STANDALONE);

        $this->tpl_view_vars = array(
            'hotelStandaloneProducts' => $hotelStandaloneProducts,
            'standaloneProducts' => $standaloneProducts,
            'guestFormattedAddress' => $guestFormattedAddress,
            'idOrderAddressInvoice' => $idOrderAddressInvoice,
            'ordersWithDiffInvAddr' => $ordersWithDiffInvAddr,
            'idCurrentAddress' => $idCurrentAddress,
            'totalRefundedRooms' => $totalRefundedRooms,
            'orderOverBookings' => $orderOverBookings,
            // refund info
            'refund_allowed' => (int) $order->isReturnable(),
            'applicable_refund_policies' => $applicableRefundPolicies,
            'returns' => OrderReturn::getOrdersReturn($order->id_customer, $order->id),
            'refundReqBookings' => $refundReqBookings,
            'refundReqProducts' => $refundReqProducts,
            'refundedAmount' => $refundedAmount,
            'totalDemandsPriceTI' => $totalDemandsPriceTI,
            'totalDemandsPriceTE' => $totalDemandsPriceTE,
            'totalRoomsCostTE' => $totalRoomsCostTE,
            'total_room_tax' => $total_room_tax,
            'htl_booking_order_data' => $bookingOrderInfo,
            'hotel_order_status' => $htlOrderStatus,
            'order_detail_data' => $order_detail_data,
            'max_child_in_room' => Configuration::get('WK_GLOBAL_MAX_CHILD_IN_ROOM'),
            'max_child_age' => Configuration::get('WK_GLOBAL_CHILD_MAX_AGE'),
            'hotel_service_products' => $orderHotelServiceProducts,
            'standalone_service_products' => $orderStandaloneServiceProducts,
            'hotel_booking' => $hotelBooking,
            /*END*/
            'order' => $order,
            'cart' => new Cart($order->id_cart),
            'customer' => $customer,
            'allowBackdateOrder' => $allowBackdateOrder,
            'gender' => $gender,
            'customerGuestDetail' => $customerGuestDetail,
            'genders' => Gender::getGenders(),
            'customer_addresses' => $customer->getAddresses($this->context->language->id),
            'addresses' => array(
                'delivery' => $addressDelivery,
                'deliveryState' => isset($deliveryState) ? $deliveryState : null,
                'invoice' => $addressInvoice,
                'invoiceState' => isset($invoiceState) ? $invoiceState : null
            ),
            'customerStats' => $customer->getStats(),
            'products' => $products,
            'discounts' => $order->getCartRules(),
            'total_paid' => $order->getTotalPaid(),
            'customer_thread_message' => CustomerThread::getCustomerMessages($order->id_customer, null, $order->id),
            'id_customer_thread' => $objCustomerThread->getIdCustomerThreadByIdOrder($order->id),
            'orderMessages' => OrderMessage::getOrderMessages($order->id_lang),
            'messages' => $messages,
            'carrier' => new Carrier($order->id_carrier),
            'history' => $history,
            'order_payment_detail' => $order_payment_detail,
            'states' => OrderState::getOrderStates($this->context->language->id),
            'warehouse_list' => $warehouse_list,
            'sources' => ConnectionsSource::getOrderSources($order->id),
            'currentState' => $order->getCurrentOrderState(),
            'currency' => new Currency($order->id_currency),
            'currencies' => Currency::getCurrenciesByIdShop($order->id_shop),
            'previousOrder' => $order->getPreviousOrderId(),
            'nextOrder' => $order->getNextOrderId(),
            'current_index' => self::$currentIndex,
            'carrierModuleCall' => $carrier_module_call,
            'iso_code_lang' => $this->context->language->iso_code,
            'id_lang' => $this->context->language->id,
            'can_edit' => ($this->tabAccess['edit'] === 1),
            'current_id_lang' => $this->context->language->id,
            'invoices_collection' => $order->getInvoicesCollection(),
            'not_paid_invoices_collection' => $order->getNotPaidInvoicesCollection(),
            'payment_methods' => $payment_methods,
            'payment_types' => $this->getPaymentsTypes(),
            'invoice_management_active' => Configuration::get('PS_INVOICE', null, null, $order->id_shop),
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
            'HOOK_CONTENT_ORDER' => Hook::exec(
                'displayAdminOrderContentOrder',
                array(
                'order' => $order,
                'products' => $products,
                'customer' => $customer)
            ),
            'HOOK_CONTENT_SHIP' => Hook::exec(
                'displayAdminOrderContentShip',
                array(
                'order' => $order,
                'products' => $products,
                'customer' => $customer)
            ),
            'HOOK_TAB_ORDER' => Hook::exec(
                'displayAdminOrderTabOrder',
                array(
                'order' => $order,
                'products' => $products,
                'customer' => $customer)
            ),
            'HOOK_TAB_SHIP' => Hook::exec(
                'displayAdminOrderTabShip',
                array(
                'order' => $order,
                'products' => $products,
                'customer' => $customer)
            ),
            'orderDocuments' => $order->getDocuments(),
            'ROOM_STATUS_CHECKED_IN' => HotelBookingDetail::STATUS_CHECKED_IN,
            'ROOM_STATUS_CHECKED_OUT' => HotelBookingDetail::STATUS_CHECKED_OUT,
            'ALLOTMENT_MANUAL' => HotelBookingDetail::ALLOTMENT_MANUAL,
            'order_convenience_fee_services' => $orderConvenienceFeeServices,
        );

        return parent::renderView();
    }


    public function ajaxProcessGetHotelRoomTypes()
    {
        $response = array('status' => false);

        $idHotel = (int) Tools::getValue('id_hotel');
        $objHotelRoomType = new HotelRoomType();
        $hotelRoomTypes = array();

        if ($idHotel > 0) {
            $hotelRoomTypes = $objHotelRoomType->getRoomTypeByHotelId($idHotel, $this->context->language->id);

            $objHotelBranchInformation = new HotelBranchInformation($idHotel, $this->context->language->id);
            foreach ($hotelRoomTypes as &$hotelRoomType) {
                $hotelRoomType['hotel_name'] = $objHotelBranchInformation->hotel_name;
            }
        } else {
            $allHotelRoomTypes = $objHotelRoomType->getAllRoomTypes();
            if (is_array($allHotelRoomTypes) && count($allHotelRoomTypes)) {
                $hotels = array();
                foreach ($allHotelRoomTypes as $hotelRoomType) {
                    if (!array_key_exists($hotelRoomType['id_hotel'], $hotels)) {
                        $hotels[$hotelRoomType['id_hotel']] = new HotelBranchInformation(
                            $hotelRoomType['id_hotel'],
                            $this->context->language->id
                        );
                    }

                    $objProduct = new Product($hotelRoomType['id_product'], false, $this->context->language->id);
                    $hotelRoomTypes[] = array(
                        'id_product' => $hotelRoomType['id_product'],
                        'room_type' => $objProduct->name,
                        'hotel_name' => $hotels[$hotelRoomType['id_hotel']]->hotel_name,
                    );
                }
            }
        }

        if (is_array($hotelRoomTypes) && count($hotelRoomTypes)) {
            $response['has_room_types'] = true;

            $this->context->smarty->assign(array(
                'room_types_info' => $hotelRoomTypes,
            ));

            $response['status'] = true;
            $response['html_room_types'] = $this->context->smarty->fetch('controllers/orders/_filter_room_types.tpl');
        } else {
            $response['has_room_types'] = false;
            $response['status'] = true;
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessGetHotelRooms()
    {
        $response = array('status' => false);

        $idHotel = (int) Tools::getValue('id_hotel');
        $idProduct = (int) Tools::getValue('id_product');

        $hotelRoomsInfo = HotelRoomInformation::getHotelRoomsInfo($idHotel, $idProduct);

        if (is_array($hotelRoomsInfo) && count($hotelRoomsInfo)) {
            $response['has_hotel_rooms'] = true;

            $this->context->smarty->assign(array(
                'hotel_rooms_info' => $hotelRoomsInfo,
            ));

            $response['status'] = true;
            $response['html_hotel_rooms'] = $this->context->smarty->fetch('controllers/orders/_filter_hotel_rooms.tpl');
        } else {
            $response['has_hotel_rooms'] = false;
            $response['status'] = true;
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessGetBookingDocuments()
    {
        $response = array('status' => false);

        $idHtlBooking = (int) Tools::getValue('id_htl_booking');
        $objHotelBookingDetail = new HotelBookingDetail($idHtlBooking);
        if (Validate::isLoadedObject($objHotelBookingDetail)) {
            $response['html'] = $this->getRenderedBookingDocuments($idHtlBooking);
            $response['status'] = true;
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessUploadBookingDocument()
    {
        $response = array('status' => false);

        if ($this->tabAccess['edit'] === 1) {
            $idHtlBooking = (int) Tools::getValue('id_htl_booking');
            $title = Tools::getValue('title');

            // validations
            $objHotelBookingDetail = new HotelBookingDetail($idHtlBooking);
            if (!Validate::isLoadedObject($objHotelBookingDetail)) {
                $this->errors[] = $this->l('Booking detail not found.');
            }

            if (!$title) {
                $title = '--';
            } else {
                if (!Validate::isCatalogName($title)) {
                    $this->errors[] = $this->l('Please enter a valid Title.');
                }
            }

            $objHotelBookingDocument = new HotelBookingDocument();
            $objHotelBookingDocument->setFileInfoForUploadedDocument('booking_document');
            if (!$objHotelBookingDocument->fileInfo || !count($objHotelBookingDocument->fileInfo)) {
                $this->errors[] = $this->l('Please select a file to upload.');
            } elseif ($objHotelBookingDocument->fileInfo['size'] > Tools::getMaxUploadSize()) {
                $this->errors[] = $this->l('Uploaded file size is too large.');
            } elseif(!(ImageManager::isRealImage($objHotelBookingDocument->fileInfo['tmp_name'])
                || $objHotelBookingDocument->fileInfo['mime'] == 'application/pdf')
            ) {
                $this->errors[] = $this->l('Please upload an image or a PDF file only. Allowed image formats: .gif, .jpg, .jpeg and .png');
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
        }

        if (!count($this->errors)) {
            $objHotelBookingDocument = new HotelBookingDocument();
            $objHotelBookingDocument->setFileInfoForUploadedDocument('booking_document');
            $objHotelBookingDocument->id_htl_booking = $idHtlBooking;
            $objHotelBookingDocument->title = $title;
            $objHotelBookingDocument->setFileType();
            if ($objHotelBookingDocument->save()) {
                $objHotelBookingDocument->saveDocumentFile();

                $response['html'] = $this->getRenderedBookingDocuments($idHtlBooking);
                $response['num_checkin_documents'] = HotelBookingDocument::getCountByIdHtlBooking($idHtlBooking);
                $response['status'] = true;
            } else {
                $this->errors[] = $this->l('Document upload failed.');
            }
        } else {
            $this->context->smarty->assign(array(
                'errors' => $this->errors,
            ));

            $response['errors'] = $this->context->smarty->fetch('alerts.tpl');
            $response['status'] = false;
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDeleteBookingDocument()
    {
        $response = array('status' => false);

        if ($this->tabAccess['edit'] === 1) {
            $idHtlBookingDocument = (int) Tools::getValue('id_htl_booking_document');

            $objHotelBookingDocument = new HotelBookingDocument($idHtlBookingDocument);
            if (Validate::isLoadedObject($objHotelBookingDocument)) {
                $idHtlBooking = $objHotelBookingDocument->id_htl_booking;
                if ($objHotelBookingDocument->delete()) {
                    $response['html'] = $this->getRenderedBookingDocuments($idHtlBooking);
                    $response['num_checkin_documents'] = HotelBookingDocument::getCountByIdHtlBooking($idHtlBooking);
                    $response['status'] = true;
                }
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to edit this order.');
            $this->context->smarty->assign(array('errors' => $this->errors));
            $response['status'] = false;
            $response['errors'] = $this->context->smarty->fetch('alerts.tpl');
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessUpdateGuestDetails()
    {
        $response = array(
            'hasError' => false,
            'errors' => array(),
        );

        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            if (Validate::isLoadedObject($order = new Order(Tools::getValue('id_order')))) {
                $objCustomerGuestDetail = new OrderCustomerGuestDetail();
                $response['errors'] = $objCustomerGuestDetail->validateController();
                if (!$response['errors']) {
                    if ($id_customer_guest_detail = OrderCustomerGuestDetail::isCustomerGuestBooking($order->id)) {
                        if (Validate::isLoadedObject($objCustomerGuestDetail = new OrderCustomerGuestDetail($id_customer_guest_detail))) {
                            $id_gender = Tools::getValue('id_gender');
                            $firstname = Tools::getValue('firstname');
                            $lastname = Tools::getValue('lastname');
                            $email = Tools::getValue('email');
                            $phone = Tools::getValue('phone');
                            $objCustomerGuestDetail->id_gender = $id_gender;
                            $objCustomerGuestDetail->firstname = $firstname;
                            $objCustomerGuestDetail->lastname = $lastname;
                            $objCustomerGuestDetail->email = $email;
                            $objCustomerGuestDetail->phone = $phone;
                            if ($objCustomerGuestDetail->save()) {
                                $gender = new Gender($objCustomerGuestDetail->id_gender, $this->context->language->id);
                                $response['data']['gender_name'] = $gender->name;
                                $response['data']['guest_name'] = $objCustomerGuestDetail->firstname.' '.$objCustomerGuestDetail->lastname ;
                                $response['data']['guest_email'] = $objCustomerGuestDetail->email;
                                $response['data']['guest_phone'] = $objCustomerGuestDetail->phone;

                                $response['msg'] = $this->l('Guest details are updated.');
                            } else {
                                $response['errors'][] = Tools::displayError('Unable to save guest details.');
                            }
                        } else {
                            $response['errors'][] = Tools::displayError('Guest details not found.');
                        }
                    } else {
                        $response['errors'][] = Tools::displayError('Guest details not found.');
                    }
                }
            }
        } else {
            $response['errors'][] = Tools::displayError('You do not have permission to edit this order.');
        }

        if ($response['errors']) {
            $response['hasError'] = true;
            $this->context->smarty->assign('errors', $response['errors']);

            $response['errorsHtml'] = $this->context->smarty->fetch('alerts.tpl');
        }

        $this->ajaxDie(json_encode($response));
    }

    private function getRenderedBookingDocuments($idHtlBooking)
    {
        $bookingDocuments = HotelBookingDocument::getDocumentsByIdHtlBooking($idHtlBooking);

        $this->context->smarty->assign(array(
            'can_edit' => $this->tabAccess['edit'],
            'link' => $this->context->link,
            'booking_documents' => $bookingDocuments,
            'pdf_icon_link' => $this->context->link->getBaseLink().'modules/hotelreservationsystem/views/img/pdf-icon.jpg',
        ));

        return $this->context->smarty->fetch('controllers/orders/_booking_document_line.tpl');
    }

    public function ajaxProcessSearchProducts()
    {
        Context::getContext()->customer = new Customer((int)Tools::getValue('id_customer'));
        $currency = new Currency((int)Tools::getValue('id_currency'));
        // $bookingProduct = (bool)Tools::getValue('booking_product', true);
        $to_return = array('found' => false);
        if (Validate::isLoadedObject($order = new Order(Tools::getValue('id_order')))) {
            $objBookingDetail = new HotelBookingDetail();
            $hotelBookingDetail = $objBookingDetail->getOrderCurrentDataByOrderId($order->id);
            if (count($hotelBookingDetail)) {
                $idHotel = reset($hotelBookingDetail)['id_hotel'];
            } else {
                $idHotel = false;
            }

            if ($products = Product::searchByName((int)$this->context->language->id, pSQL(Tools::getValue('product_search')), 1, null, $idHotel)) {
                $objRoomType = new HotelRoomType();
                foreach ($products as $key => &$product) {

                    // if (((bool)$product['booking_product']) != $bookingProduct) {
                    //     unset($products[$key]);
                    //     continue;
                    // }

                    // get product room type informatin
                    if ($roomTypeDetail = $objRoomType->getRoomTypeInfoByIdProduct($product['id_product'])) {
                        $product['room_type_info'] = $roomTypeDetail;
                    }

                    $idHotelAddress = $order->id_address_tax;
                    // Concret price
                    $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), _PS_PRICE_COMPUTE_PRECISION_);
                    $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice(Product::getPriceStatic(
                        $product['id_product'],
                        true,
                        null,
                        6,
                        null,
                        false,
                        true,
                        1,
                        false,
                        null,
                        null,
                        $idHotelAddress
                    ), $currency), _PS_PRICE_COMPUTE_PRECISION_);

                    // Formatted price
                    $product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $currency), $currency);
                    $productObj = new Product((int)$product['id_product'], false, (int)$this->context->language->id);
                    $combinations = array();
                    $attributes = $productObj->getAttributesGroups((int)$this->context->language->id);

                    // Tax rate for this customer
                    if (Tools::isSubmit('id_address')) {
                        $product['tax_rate'] = $productObj->getTaxesRate(new Address($idHotelAddress));
                    }

                    $product['warehouse_list'] = array();

                    foreach ($attributes as $attribute) {
                        if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                            $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                        }
                        $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'].' - ';
                        $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                        $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                        if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                            $price_tax_incl = Product::getPriceStatic((int)$product['id_product'], true, $attribute['id_product_attribute']);
                            $price_tax_excl = Product::getPriceStatic((int)$product['id_product'], false, $attribute['id_product_attribute']);
                            $combinations[$attribute['id_product_attribute']]['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($price_tax_incl, $currency), 2);
                            $combinations[$attribute['id_product_attribute']]['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($price_tax_excl, $currency), 2);
                            $combinations[$attribute['id_product_attribute']]['formatted_price'] = Tools::displayPrice(Tools::convertPrice($price_tax_excl, $currency), $currency);
                        }
                        if (!isset($combinations[$attribute['id_product_attribute']]['qty_in_stock'])) {
                            $combinations[$attribute['id_product_attribute']]['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct((int)$product['id_product'], $attribute['id_product_attribute'], (int)$this->context->shop->id);
                        }

                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)$product['advanced_stock_management'] == 1) {
                            $product['warehouse_list'][$attribute['id_product_attribute']] = Warehouse::getProductWarehouseList($product['id_product'], $attribute['id_product_attribute']);
                        } else {
                            $product['warehouse_list'][$attribute['id_product_attribute']] = array();
                        }

                        $product['stock'][$attribute['id_product_attribute']] = Product::getRealQuantity($product['id_product'], $attribute['id_product_attribute']);
                    }

                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)$product['advanced_stock_management'] == 1) {
                        $product['warehouse_list'][0] = Warehouse::getProductWarehouseList($product['id_product']);
                    } else {
                        $product['warehouse_list'][0] = array();
                    }

                    $product['stock'][0] = StockAvailable::getQuantityAvailableByProduct((int)$product['id_product'], 0, (int)$this->context->shop->id);

                    foreach ($combinations as &$combination) {
                        $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                    }
                    $product['combinations'] = $combinations;

                    if ($product['customizable']) {
                        $product_instance = new Product((int)$product['id_product']);
                        $product['customization_fields'] = $product_instance->getCustomizationFields($this->context->language->id);
                    }
                }

                if (!empty($products)) {
                    $to_return = array(
                        'products' => $products,
                        'found' => true
                    );
                }
            }
        }
        $this->ajaxDie(json_encode($to_return));
    }

    // To search standalone products while adding to the order
    public function ajaxProcessSearchStandaloneProducts()
    {
        Context::getContext()->customer = new Customer((int)Tools::getValue('id_customer'));
        $currency = new Currency((int)Tools::getValue('id_currency'));
        $to_return = array('found' => false);
        if (Validate::isLoadedObject($order = new Order(Tools::getValue('id_order')))) {
            $addressTax = new Address((int)$order->id_address_tax);
            if ($idHotel = $addressTax->id_hotel) {
                if ($products = Product::searchByName(
                    (int)$this->context->language->id,
                    pSQL(Tools::getValue('product_search')),
                    0,
                    null,
                    $idHotel
                )) {
                    $objServiceProductOption = new ServiceProductOption();
                    foreach ($products as $key => &$product) {
                        $idHotelAddress = Cart::getIdAddressForTaxCalculation($product['id_product'], $idHotel);
                        $product['price_tax_incl'] = RoomTypeServiceProductPrice::getPrice($product['id_product'], $idHotel, null, true);
                        $product['price_tax_excl'] = RoomTypeServiceProductPrice::getPrice($product['id_product'], $idHotel, null, false);

                        // if product has options then set the price of the first selected option of the product
                        if ($product['options'] = $objServiceProductOption->getProductOptions($product['id_product'])) {
                            $product['price_tax_incl'] = RoomTypeServiceProductPrice::getPrice(
                                $product['id_product'],
                                $idHotel,
                                $product['options'][0]['id_product_option'],
                                true
                            );
                            $product['price_tax_excl'] = RoomTypeServiceProductPrice::getPrice(
                                $product['id_product'],
                                $idHotel,
                                $product['options'][0]['id_product_option'],
                                false
                            );
                        }

                        // convert price to order currency
                        $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_incl'], $currency), _PS_PRICE_COMPUTE_PRECISION_);
                        $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), _PS_PRICE_COMPUTE_PRECISION_);

                        // Tax rate for this customer
                        if (Tools::isSubmit('id_address')) {
                            $productObj = new Product((int)$product['id_product']);
                            $product['tax_rate'] = $productObj->getTaxesRate(new Address($idHotelAddress));
                        }
                    }
                    if (!empty($products)) {
                        $to_return = array(
                            'products' => $products,
                            'found' => true
                        );
                    }
                }
            } else {
                if ($products = Product::searchByName(
                    (int)$this->context->language->id,
                    pSQL(Tools::getValue('product_search')),
                    0,
                    Product::SELLING_PREFERENCE_STANDALONE
                )) {
                    $objServiceProductOption = new ServiceProductOption();
                    $idAddress = 0;
                    if (Tools::isSubmit('id_address')) {
                        $idAddress = Tools::getValue('id_address');
                    }

                    $addressType = Configuration::get('PS_STANDARD_PRODUCT_ORDER_ADDRESS_PREFRENCE');
                    if (Product::STANDARD_PRODUCT_ADDRESS_PREFERENCE_HOTEL == $addressType) {
                        $idAddress = ConfigurationCore::get('WK_PRIMARY_HOTEL');
                    } else if (Product::STANDARD_PRODUCT_ADDRESS_PREFERENCE_CUSTOM == $addressType) {
                        $idAddress = Configuration::get('PS_STANDARD_PRODUCT_ORDER_ADDRESS_ID');
                    }

                    foreach ($products as $key => &$product) {
                        $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), _PS_PRICE_COMPUTE_PRECISION_);
                        $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice(Product::getPriceStatic(
                            $product['id_product'],
                            true,
                            null,
                            6,
                            null,
                            false,
                            true,
                            1,
                            false,
                            null,
                            null,
                            $idAddress
                        ), $currency), _PS_PRICE_COMPUTE_PRECISION_);
                        // if product has options then set the price of the first selected option of the product
                        if ($product['options'] = $objServiceProductOption->getProductOptions($product['id_product'])) {
                            $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice(Product::getPriceStatic(
                                $product['id_product'],
                                true,
                                $product['options'][0]['id_product_option'],
                                6,
                                null,
                                false,
                                true,
                                1,
                                false,
                                null,
                                null,
                                $idAddress
                            ), $currency), _PS_PRICE_COMPUTE_PRECISION_);
                            $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice(Product::getPriceStatic(
                                $product['id_product'],
                                false,
                                $product['options'][0]['id_product_option'],
                                6,
                                null,
                                false,
                                true,
                                1,
                                false,
                                null,
                                null,
                                $idAddress
                            ), $currency), _PS_PRICE_COMPUTE_PRECISION_);
                        }

                        // convert price to order currency
                        $product['price_tax_incl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_incl'], $currency), _PS_PRICE_COMPUTE_PRECISION_);
                        $product['price_tax_excl'] = Tools::ps_round(Tools::convertPrice($product['price_tax_excl'], $currency), _PS_PRICE_COMPUTE_PRECISION_);

                        $productObj = new Product((int)$product['id_product'], false, (int)$this->context->language->id);
                        // Tax rate for this customer
                        if (Tools::isSubmit('id_address')) {
                            $product['tax_rate'] = $productObj->getTaxesRate(new Address($idAddress));
                        }
                    }
                    if (!empty($products)) {
                        $to_return = array(
                            'products' => $products,
                            'found' => true
                        );
                    }
                }
            }
        }

        $this->content = json_encode($to_return);
    }

    public function ajaxProcessGetProductOptionPrice()
    {
        $addProduct = Tools::getValue('add_product');
        $to_return = array('found' => false);
        if (Validate::isLoadedObject($order = new Order(Tools::getValue('id_order')))) {
            $addressTax = new Address((int)$order->id_address_tax);
            $currency = new Currency((int)$order->id_currency);
            if ($idHotel = $addressTax->id_hotel) {
                if ($id_product_option = $addProduct['product_option']) {
                    $price_tax_incl = RoomTypeServiceProductPrice::getPrice($addProduct['product_id'], $idHotel, $id_product_option, true);
                    $price_tax_excl = RoomTypeServiceProductPrice::getPrice($addProduct['product_id'], $idHotel, $id_product_option, false);

                    // convert price to order currency
                    $price_tax_excl = Tools::ps_round(Tools::convertPrice($price_tax_excl, $currency), _PS_PRICE_COMPUTE_PRECISION_);
                    $price_tax_incl = Tools::ps_round(Tools::convertPrice($price_tax_incl, $currency), _PS_PRICE_COMPUTE_PRECISION_);

                    $to_return = array(
                        'price_tax_incl' => $price_tax_incl,
                        'price_tax_excl' => $price_tax_excl,
                        'found' => true
                    );
                }
            } else {
                if ($id_product_option = $addProduct['product_option']) {
                    $price_tax_incl = RoomTypeServiceProductPrice::getPrice($addProduct['product_id'], false, $id_product_option, true);
                    $price_tax_excl = RoomTypeServiceProductPrice::getPrice($addProduct['product_id'], false, $id_product_option, false);

                    // convert price to order currency
                    $price_tax_excl = Tools::ps_round(Tools::convertPrice($price_tax_excl, $currency), _PS_PRICE_COMPUTE_PRECISION_);
                    $price_tax_incl = Tools::ps_round(Tools::convertPrice($price_tax_incl, $currency), _PS_PRICE_COMPUTE_PRECISION_);
                    $to_return = array(
                        'price_tax_incl' => $price_tax_incl,
                        'price_tax_excl' => $price_tax_excl,
                        'found' => true
                    );
                }
            }
        }
        $this->content = json_encode($to_return);
    }

    public function ajaxProcessSendMailValidateOrder()
    {
        if ($this->tabAccess['edit'] === 1) {
            $cart = new Cart((int)Tools::getValue('id_cart'));
            if (Validate::isLoadedObject($cart)) {
                $customer = new Customer((int)$cart->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    $mailVars = array(
                        '{order_link}' => Context::getContext()->link->getPageLink('order', false, (int)$cart->id_lang, 'step=3&recover_cart='.(int)$cart->id.'&token_cart='.md5(_COOKIE_KEY_.'recover_cart_'.(int)$cart->id)),
                        '{firstname}' => $customer->firstname,
                        '{lastname}' => $customer->lastname
                    );
                    if (Mail::Send(
                        (int)$cart->id_lang,
                        'backoffice_order',
                        Mail::l('Process the payment of your order', (int)$cart->id_lang),
                        $mailVars,
                        $customer->email,
                        $customer->firstname.' '.$customer->lastname,
                        null,
                        null,
                        null,
                        null,
                        _PS_MAIL_DIR_,
                        true,
                        $cart->id_shop
                    )) {
                        die(json_encode(array('errors' => false, 'result' => $this->l('The email was sent to your customer.'))));
                    }
                }
            }
            $this->content = json_encode(array('errors' => true, 'result' => $this->l('Error in sending the email to your customer.')));
        }
    }

    public function ajaxProcessAddServiceProductOnOrder()
    {
        // Check tab access is allowed to edit
        if (!$this->tabAccess['edit'] === 1) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You do not have permission to edit this order.')
            )));
        }

        // Load object
        $order = new Order((int)Tools::getValue('id_order'));
        if (!Validate::isLoadedObject($order)) {
            die(Tools::jsonEncode(array(
                'result' => false,
                'error' => Tools::displayError('The order object cannot be loaded.')
            )));
        }

        $old_cart_rules = Context::getContext()->cart->getCartRules();

        if ($order->hasBeenShipped()) {
            die(Tools::jsonEncode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot add products to delivered orders. ')
            )));
        }

        $product_informations = $_POST['add_product'];
        if (isset($_POST['add_invoice'])) {
            $invoice_informations = $_POST['add_invoice'];
        } else {
            $invoice_informations = array();
        }
        $product = new Product($product_informations['product_id'], false, $order->id_lang);
        if (!Validate::isLoadedObject($product)) {
            die(Tools::jsonEncode(array(
                'result' => false,
                'error' => Tools::displayError('The product object cannot be loaded.')
            )));
        }

        if (isset($product_informations['product_attribute_id']) && $product_informations['product_attribute_id']) {
            $combination = new Combination($product_informations['product_attribute_id']);
            if (!Validate::isLoadedObject($combination)) {
                die(Tools::jsonEncode(array(
                'result' => false,
                'error' => Tools::displayError('The combination object cannot be loaded.')
            )));
            }
        }

        if ($product->booking_product || Product::SELLING_PREFERENCE_WITH_ROOM_TYPE == $product->selling_preference_type) {
            die(Tools::jsonEncode(array(
                'result' => false,
                'error' => Tools::displayError('The product cannot be added through this method.')
            )));
        }

        // Total method
        $total_method = Cart::BOTH_WITHOUT_SHIPPING;

        // Create new cart
        $cart = new Cart();
        $cart->id_shop_group = $order->id_shop_group;
        $cart->id_shop = $order->id_shop;
        $cart->id_customer = $order->id_customer;
        $cart->id_carrier = $order->id_carrier;
        $cart->id_address_delivery = $order->id_address_delivery;
        $cart->id_address_invoice = $order->id_address_invoice;
        $cart->id_currency = $order->id_currency;
        $cart->id_lang = $order->id_lang;
        $cart->secure_key = $order->secure_key;

        // Save new cart
        $cart->add();

        // Save context (in order to apply cart rule)
        $this->context->cart = $cart;
        $this->context->customer = new Customer($order->id_customer);

        // always add taxes even if there are not displayed to the customer
        $use_taxes = true;

        $initial_product_price_tax_incl = Product::getPriceStatic(
            $product->id,
            $use_taxes,
            isset($combination) ? $combination->id : null,
            2,
            null,
            false,
            true,
            1,
            false,
            $order->id_customer,
            $cart->id,
            $order->id_address_tax
        );

        // Creating specific price if needed
        if ($product_informations['product_price_tax_incl'] != $initial_product_price_tax_incl) {
            $specific_price = new SpecificPrice();
            $specific_price->id_shop = 0;
            $specific_price->id_shop_group = 0;
            $specific_price->id_currency = 0;
            $specific_price->id_country = 0;
            $specific_price->id_group = 0;
            $specific_price->id_customer = $order->id_customer;
            $specific_price->id_product = $product->id;
            if (isset($combination)) {
                $specific_price->id_product_attribute = $combination->id;
            } else {
                $specific_price->id_product_attribute = 0;
            }
            $specific_price->price = $product_informations['product_price_tax_excl'];
            $specific_price->from_quantity = 1;
            $specific_price->reduction = 0;
            $specific_price->reduction_type = 'amount';
            $specific_price->reduction_tax = 0;
            $specific_price->from = '0000-00-00 00:00:00';
            $specific_price->to = '0000-00-00 00:00:00';
            $specific_price->add();
        }

        // Add product to cart
        $update_quantity = $cart->updateQty($product_informations['product_quantity'], $product->id, isset($product_informations['product_attribute_id']) ? $product_informations['product_attribute_id'] : null,
            isset($combination) ? $combination->id : null, 'up', 0, new Shop($cart->id_shop));

        if ($update_quantity < 0) {
            // If product has attribute, minimal quantity is set with minimal quantity of attribute
            $minimal_quantity = ($product_informations['product_attribute_id']) ? ProductAttribute::getAttributeMinimalQty($product_informations['product_attribute_id']) : $product->minimal_quantity;
            die(Tools::jsonEncode(array('error' => sprintf(Tools::displayError('You must add %d minimum quantity', false), $minimal_quantity))));
        } elseif (!$update_quantity) {
            die(Tools::jsonEncode(array('error' => Tools::displayError('You already have the maximum quantity available for this product.', false))));
        }

        // If order is valid, we can create a new invoice or edit an existing invoice
        if ($order->hasInvoice()) {
            $order_invoice = new OrderInvoice($product_informations['invoice']);
            // Create new invoice
            if ($order_invoice->id == 0) {
                // If we create a new invoice, we calculate shipping cost
                $total_method = Cart::BOTH;
                // Create Cart rule in order to make free shipping
                if (isset($invoice_informations['free_shipping']) && $invoice_informations['free_shipping']) {
                    $cart_rule = new CartRule();
                    $cart_rule->id_customer = $order->id_customer;
                    $cart_rule->name = array(
                        Configuration::get('PS_LANG_DEFAULT') => $this->l('[Generated] CartRule for Free Shipping')
                    );
                    $cart_rule->date_from = date('Y-m-d H:i:s', time());
                    $cart_rule->date_to = date('Y-m-d H:i:s', time() + 24 * 3600);
                    $cart_rule->quantity = 1;
                    $cart_rule->quantity_per_user = 1;
                    $cart_rule->minimum_amount_currency = $order->id_currency;
                    $cart_rule->reduction_currency = $order->id_currency;
                    $cart_rule->free_shipping = true;
                    $cart_rule->active = 1;
                    $cart_rule->add();

                    // Add cart rule to cart and in order
                    $cart->addCartRule($cart_rule->id);
                    $values = array(
                        'tax_incl' => $cart_rule->getContextualValue(true),
                        'tax_excl' => $cart_rule->getContextualValue(false)
                    );
                    $order->addCartRule($cart_rule->id, $cart_rule->name[Configuration::get('PS_LANG_DEFAULT')], $values);
                }

                $order_invoice->id_order = $order->id;
                if ($order_invoice->number) {
                    Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $order->id_shop);
                } else {
                    $order_invoice->number = Order::getLastInvoiceNumber() + 1;
                }

                $invoice_address = new Address((int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});
                $carrier = new Carrier((int)$order->id_carrier);
                $tax_calculator = $carrier->getTaxCalculator($invoice_address);

                $order_invoice->total_paid_tax_excl = (float)$cart->getOrderTotal(false, $total_method);
                $order_invoice->total_paid_tax_incl = (float)$cart->getOrderTotal($use_taxes, $total_method);
                $order_invoice->total_products = (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order_invoice->total_products_wt = (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);
                $order_invoice->total_shipping_tax_excl = (float)$cart->getTotalShippingCost(null, false);
                $order_invoice->total_shipping_tax_incl = (float)$cart->getTotalShippingCost();

                $order_invoice->total_wrapping_tax_excl = abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                $order_invoice->total_wrapping_tax_incl = abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order_invoice->shipping_tax_computation_method = (int)$tax_calculator->computation_method;

                // Update current order field, only shipping because other field is updated later
                $order->total_shipping += $order_invoice->total_shipping_tax_incl;
                $order->total_shipping_tax_excl += $order_invoice->total_shipping_tax_excl;
                $order->total_shipping_tax_incl += ($use_taxes) ? $order_invoice->total_shipping_tax_incl : $order_invoice->total_shipping_tax_excl;

                $order->total_wrapping += abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order->total_wrapping_tax_excl += abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                $order->total_wrapping_tax_incl += abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order_invoice->add();

                $order_invoice->saveCarrierTaxCalculator($tax_calculator->getTaxesAmount($order_invoice->total_shipping_tax_excl));

                $order_carrier = new OrderCarrier();
                $order_carrier->id_order = (int)$order->id;
                $order_carrier->id_carrier = (int)$order->id_carrier;
                $order_carrier->id_order_invoice = (int)$order_invoice->id;
                $order_carrier->weight = (float)$cart->getTotalWeight();
                $order_carrier->shipping_cost_tax_excl = (float)$order_invoice->total_shipping_tax_excl;
                $order_carrier->shipping_cost_tax_incl = ($use_taxes) ? (float)$order_invoice->total_shipping_tax_incl : (float)$order_invoice->total_shipping_tax_excl;
                $order_carrier->add();
            }
            // Update current invoice
            else {
                $order_invoice->total_paid_tax_excl += (float)$cart->getOrderTotal(false, $total_method);
                $order_invoice->total_paid_tax_incl += (float)$cart->getOrderTotal($use_taxes, $total_method);
                $order_invoice->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order_invoice->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);
                $order_invoice->update();
            }
        }

        // Create Order detail information
        $order_detail = new OrderDetail();
        $order_detail->createList($order, $cart, $order->getCurrentOrderState(), $cart->getProducts(), (isset($order_invoice) ? $order_invoice->id : 0), $use_taxes, (int)Tools::getValue('add_product_warehouse'));

        // update totals amount of order
        $order->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $order->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);

        $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

        if (isset($order_invoice) && Validate::isLoadedObject($order_invoice)) {
            $order->total_shipping = $order_invoice->total_shipping_tax_incl;
            $order->total_shipping_tax_incl = $order_invoice->total_shipping_tax_incl;
            $order->total_shipping_tax_excl = $order_invoice->total_shipping_tax_excl;
        }

        // discount
        $order->total_discounts += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
        $order->total_discounts_tax_excl += (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
        $order->total_discounts_tax_incl += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));

        // Save changes of order
        $order->update();

        // Update weight SUM
        $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
        if (Validate::isLoadedObject($order_carrier)) {
            $order_carrier->weight = (float)$order->getTotalWeight();
            if ($order_carrier->update()) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
            }
        }

        // Update Tax lines
        $order_detail->updateTaxAmount($order);

        // Delete specific price if exists
        if (isset($specific_price)) {
            $specific_price->delete();
        }

        $products = $this->getProducts($order);

        // Get the last product
        $product = end($products);
        $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
        $resume = OrderSlip::getProductSlipResume((int)$product['id_order_detail']);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
        $product['return_history'] = OrderReturn::getProductReturnDetail((int)$product['id_order_detail']);
        $product['refund_history'] = OrderSlip::getProductSlipDetail((int)$product['id_order_detail']);
        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int)$product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouse_location)) {
                $product['warehouse_location'] = $warehouse_location;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'product' => $product,
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'can_edit' => $this->tabAccess['edit'],
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex,
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
        ));

        $this->sendChangedNotification($order);
        $new_cart_rules = Context::getContext()->cart->getCartRules();
        sort($old_cart_rules);
        sort($new_cart_rules);
        $result = array_diff($new_cart_rules, $old_cart_rules);
        $refresh = false;

        $res = true;
        foreach ($result as $cart_rule) {
            $refresh = true;
            // Create OrderCartRule
            $rule = new CartRule($cart_rule['id_cart_rule']);
            $values = array(
                    'tax_incl' => $rule->getContextualValue(true),
                    'tax_excl' => $rule->getContextualValue(false)
                    );
            $order_cart_rule = new OrderCartRule();
            $order_cart_rule->id_order = $order->id;
            $order_cart_rule->id_cart_rule = $cart_rule['id_cart_rule'];
            $order_cart_rule->id_order_invoice = $order_invoice->id;
            $order_cart_rule->name = $cart_rule['name'];
            $order_cart_rule->value = $values['tax_incl'];
            $order_cart_rule->value_tax_excl = $values['tax_excl'];
            $res &= $order_cart_rule->add();

            $order->total_discounts += $order_cart_rule->value;
            $order->total_discounts_tax_incl += $order_cart_rule->value;
            $order->total_discounts_tax_excl += $order_cart_rule->value_tax_excl;

            $order->total_paid -= $order_cart_rule->value;
            $order->total_paid = $order->total_paid > 0 ? $order->total_paid : 0;

            $order->total_paid_tax_incl -= $order_cart_rule->value;
            $order->total_paid_tax_incl = $order->total_paid_tax_incl > 0 ? $order->total_paid_tax_incl : 0;

            $order->total_paid_tax_excl -= $order_cart_rule->value_tax_excl;
            $order->total_paid_tax_excl = $order->total_paid_tax_excl > 0 ? $order->total_paid_tax_excl : 0;
        }

        // Update Order
        $res &= $order->update();

        die(Tools::jsonEncode(array(
            'result' => true,
            'view' => $this->createTemplate('_product_line.tpl')->fetch(),
            'can_edit' => $this->tabAccess['edit'],
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch(),
            'refresh' => $refresh
        )));
    }

    public function ajaxProcessAddRoomOnOrder()
    {
        // Check tab access is allowed to edit
        if (!$this->tabAccess['edit'] === 1) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You do not have permission to edit this order.')
            )));
        }

        // Load object
        $id_order = (int) Tools::getValue('id_order');
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The order object cannot be loaded.')
            )));
        }

        $old_cart_rules = Context::getContext()->cart->getCartRules();

        if ($order->hasBeenShipped()) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot add products to delivered orders. ')
            )));
        }

        $product_informations = $_POST['add_product'];

        /*By Webkul Code is added to add order information In our table while adding product in the process order edit from order detail page.*/
        $date_from = date('Y-m-d H:i:s', strtotime($product_informations['date_from']));
        $date_to = date('Y-m-d H:i:s', strtotime($product_informations['date_to']));
        $curr_date = date('Y-m-d');
        $occupancy = Tools::getValue('occupancy');
        /*Validations*/
        if ($date_from == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please Enter Check In Date.'),
            )));
        } elseif (!Validate::isDateFormat($date_from)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please Enter a Valid Check In Date.'),
            )));
        } elseif ($date_to == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please Enter Check Out Date.'),
            )));
        } elseif (!Validate::isDateFormat($date_to)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please Enter a valid Check out Date.'),
            )));
        }
        if ($this->context->employee->isSuperAdmin()) {
            $backOrderConfigKey = 'PS_BACKDATE_ORDER_SUPERADMIN';
        } else {
            $backOrderConfigKey = 'PS_BACKDATE_ORDER_EMPLOYEES';
        }
        if (!Configuration::get($backOrderConfigKey)) {
            if ($date_from < $curr_date) {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('Check In date should not be date before current date.'),
                )));
            }
        }
        if ($date_to <= $date_from) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Check out Date Should be after Check In date.'),
            )));
        }
        if ($order->with_occupancy) {
            if ($occupancy) {
                foreach($occupancy as $key =>$roomOccupancy) {
                    if (!$roomOccupancy['adults'] || !Validate::isUnsignedInt($roomOccupancy['adults'])) {
                        die(json_encode(array(
                            'result' => false,
                            'error' => sprintf(Tools::displayError('Invalid number of adults for Room %s.'), ($key + 1)),
                        )));
                    } elseif (!Validate::isUnsignedInt($roomOccupancy['children'])) {
                        die(json_encode(array(
                            'result' => false,
                            'error' => sprintf(Tools::displayError('Invalid number of children for Room %s.'), ($key + 1)),
                        )));
                    }
                    if ($roomOccupancy['children'] > 0) {
                        if (!isset($roomOccupancy['child_ages']) || ($roomOccupancy['children'] != count($roomOccupancy['child_ages']))) {
                            die(json_encode(array(
                                'result' => false,
                            'error' => sprintf(Tools::displayError('Please provide all children age for Room %s.'), ($key + 1)),
                            )));
                        } else {
                            foreach($roomOccupancy['child_ages'] as $childAge) {
                                if (!Validate::isUnsignedInt($childAge)) {
                                    die(json_encode(array(
                                        'result' => false,
                                        'error' => sprintf(Tools::displayError('Invalid children age for Room %s.'), ($key + 1)),
                                    )));
                                }
                            }
                        }
                    }
                }
            } else {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('Invalid occupancy.'),
                )));
            }
        } elseif (!Validate::isUnsignedInt($product_informations['product_quantity'])) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please Enter a valid Quantity.'),
            )));
        }

        if (!Validate::isPrice($product_informations['product_price_tax_incl'])) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter a valid tax included price.'),
            )));
        } elseif (!Validate::isPrice($product_informations['product_price_tax_excl'])) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter a valid tax excluded price.'),
            )));
        }

        if ($order->with_occupancy) {
            $req_rm = count($occupancy);
        } else {
            $req_rm = $product_informations['product_quantity'];
        }
        $obj_booking_detail = new HotelBookingDetail();
        $num_days = HotelHelper::getNumberOfDays($date_from, $date_to);
        $product_informations['product_quantity'] = $req_rm * (int) $num_days;

        $obj_room_type = new HotelRoomType();
        $room_info_by_id_product = $obj_room_type->getRoomTypeInfoByIdProduct($product_informations['product_id']);

        if ($room_info_by_id_product) {
            $id_hotel = $room_info_by_id_product['id_hotel'];

            if ($id_hotel) {
                $objBookingDetail = new HotelBookingDetail();
                $bookingParams = array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'hotel_id' => $id_hotel,
                    'id_room_type' => $product_informations['product_id'],
                    'only_search_data' => 1
                );
                $hotel_room_data = $objBookingDetail->dataForFrontSearch($bookingParams);
                $total_available_rooms = $hotel_room_data['stats']['num_avail'];

                if ($total_available_rooms < $req_rm) {
                    die(json_encode(array(
                        'result' => false,
                        'error' => Tools::displayError('Required number of rooms are not available.'),
                        )));
                }
            } else {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('Some error occured Please try again.'),
                    )));
            }
        } else {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Some error occured Please try again.'),
            )));
        }

        /*END*/

        if (isset($_POST['add_invoice'])) {
            $invoice_informations = $_POST['add_invoice'];
        } else {
            $invoice_informations = array();
        }

        $idProduct = $product_informations['product_id'];
        $product = new Product($idProduct, false, $order->id_lang);
        if (!Validate::isLoadedObject($product)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The product object cannot be loaded.')
            )));
        }

        if (isset($product_informations['product_attribute_id']) && $product_informations['product_attribute_id']) {
            $combination = new Combination($product_informations['product_attribute_id']);
            if (!Validate::isLoadedObject($combination)) {
                die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The combination object cannot be loaded.')
            )));
            }
        }

        // Total method
        $total_method = Cart::BOTH_WITHOUT_SHIPPING;

        // Create new cart
        $cart = new Cart();
        $cart->id_shop_group = $order->id_shop_group;
        $cart->id_shop = $order->id_shop;
        $cart->id_customer = $order->id_customer;
        $cart->id_carrier = $order->id_carrier;
        $cart->id_address_delivery = $order->id_address_delivery;
        $cart->id_address_invoice = $order->id_address_invoice;
        $cart->id_currency = $order->id_currency;
        $cart->id_lang = $order->id_lang;
        $cart->secure_key = $order->secure_key;

        // Save new cart
        $cart->add();

        // Save context (in order to apply cart rule)
        $this->context->cart = $cart;
        $this->context->customer = new Customer($order->id_customer);

        // always add taxes even if not displayed to the customer
        $use_taxes = true;
        $this->context->currency = new Currency($order->id_currency);

        $initial_product_price_tax_incl = Product::getPriceStatic(
            $product->id,
            $use_taxes,
            isset($combination) ? $combination->id : null,
            2,
            null,
            false,
            true,
            1,
            false,
            $order->id_customer,
            $cart->id,
            $order->id_address_tax
        );

        // create feature price if needed
        $createFeaturePrice = $product_informations['product_price_tax_incl'] != $initial_product_price_tax_incl;
        $featurePriceParams = array();
        if ($createFeaturePrice) {
            if ($order->id_currency != (int)Configuration::get('PS_CURRENCY_DEFAULT')) {
                $product_informations['product_price_tax_excl'] = Tools::convertPrice($product_informations['product_price_tax_excl'], $order->id_currency, false);
                $product_informations['product_price_tax_excl'] = Tools::ps_round($product_informations['product_price_tax_excl'], _PS_PRICE_COMPUTE_PRECISION_);
            }

            $featurePriceParams = array(
                'id_cart' => $this->context->cart->id,
                'id_guest' => $this->context->cookie->id_guest,
                'impact_value' => $product_informations['product_price_tax_excl'],
                'id_product' => $product->id,
            );
        }

        /*By Webkul to make entries in HotelCartBookingData */
        $hotel_room_info_arr = $hotel_room_data['rm_data'][$idProduct]['data']['available'];
        $chkQty = 0;
        $objServiceProductCartDetail = new ServiceProductCartDetail();
        if ($hotel_room_info_arr) {
            foreach ($hotel_room_info_arr as $key => $room_info) {
                if ($chkQty < $req_rm) {
                    $objCartBookingData = new HotelCartBookingData();
                    $objCartBookingData->id_cart = $this->context->cart->id;
                    $objCartBookingData->id_guest = $this->context->cookie->id_guest;
                    $objCartBookingData->id_customer = $order->id_customer;
                    $objCartBookingData->id_currency = $order->id_currency;
                    $objCartBookingData->id_product = $room_info['id_product'];
                    $objCartBookingData->id_room = $room_info['id_room'];
                    $objCartBookingData->id_hotel = $room_info['id_hotel'];
                    $objCartBookingData->booking_type = 1;
                    $objCartBookingData->quantity = $num_days;
                    $objCartBookingData->date_from = $date_from;
                    $objCartBookingData->date_to = $date_to;

                    if ($order->with_occupancy) {
                        $room_occupancy = array_shift($occupancy);
                        $objCartBookingData->adults = $room_occupancy['adults'];
                        $objCartBookingData->children = $room_occupancy['children'];
                        $objCartBookingData->child_ages = $room_occupancy['children'] ? json_encode($room_occupancy['child_ages']) : json_encode(array());
                    } else {
                        // if room is being booked without occupancy selection the for adults set base occupancy and children will be 0
                        $objCartBookingData->adults = $room_info_by_id_product['adults'];
                        $objCartBookingData->children = 0;
                        $objCartBookingData->child_ages = json_encode(array());
                    }
                    if ($objCartBookingData->save()
                        && $services = RoomTypeServiceProduct::getAutoAddServices($room_info['id_product'])
                    ) {
                        foreach($services as $service) {
                            $objServiceProductCartDetail->addServiceProductInCart(
                                $this->context->cart->id,
                                $service['id_product'],
                                1,
                                false,
                                $objCartBookingData->id
                            );
                        }
                    }
                    ++$chkQty;

                    // create feature price if needed
                    if ($createFeaturePrice) {
                        $featurePriceParams['id_room'] = $room_info['id_room'];
                        $featurePriceParams['restrictions'] = array(
                            array(
                                'date_from' => $date_from,
                                'date_to' => $date_to
                            )
                        );
                        HotelRoomTypeFeaturePricing::createRoomTypeFeaturePrice($featurePriceParams);
                    }
                } else {
                    break;
                }
            }
        }
        /*END*/

        // Add product to cart
        $update_quantity = $cart->updateQty(
            ($req_rm * $num_days),
            $product->id,
            isset($product_informations['product_attribute_id']) ? $product_informations['product_attribute_id'] : null,
            isset($combination) ? $combination->id : null,
            'up',
            0,
            new Shop($cart->id_shop)
        );

        if ($update_quantity < 0) {
            // If product has attribute, minimal quantity is set with minimal quantity of attribute
            $minimal_quantity = ($product_informations['product_attribute_id']) ? ProductAttribute::getAttributeMinimalQty($product_informations['product_attribute_id']) : $product->minimal_quantity;
            die(json_encode(array('error' => sprintf(Tools::displayError('You must add %d minimum quantity', false), $minimal_quantity))));
        } elseif (!$update_quantity) {
            die(json_encode(array('error' => Tools::displayError('You already have the maximum quantity available for this product.', false))));
        }

        // If order is valid, we can create a new invoice or edit an existing invoice
        if ($order->hasInvoice()) {
            $order_invoice = new OrderInvoice($product_informations['invoice']);
            // Create new invoice
            if ($order_invoice->id == 0) {
                // If we create a new invoice, we calculate shipping cost
                $total_method = Cart::BOTH;
                // Create Cart rule in order to make free shipping
                if (isset($invoice_informations['free_shipping']) && $invoice_informations['free_shipping']) {
                    $cart_rule = new CartRule();
                    $cart_rule->id_customer = $order->id_customer;
                    $cart_rule->name = array(
                        Configuration::get('PS_LANG_DEFAULT') => $this->l('[Generated] CartRule for Free Shipping')
                    );
                    $cart_rule->date_from = date('Y-m-d H:i:s', time());
                    $cart_rule->date_to = date('Y-m-d H:i:s', time() + 24 * 3600);
                    $cart_rule->quantity = 1;
                    $cart_rule->quantity_per_user = 1;
                    $cart_rule->minimum_amount_currency = $order->id_currency;
                    $cart_rule->reduction_currency = $order->id_currency;
                    $cart_rule->free_shipping = true;
                    $cart_rule->active = 1;
                    $cart_rule->add();

                    // Add cart rule to cart and in order
                    $cart->addCartRule($cart_rule->id);
                    $values = array(
                        'tax_incl' => $cart_rule->getContextualValue(true),
                        'tax_excl' => $cart_rule->getContextualValue(false)
                    );
                    $order->addCartRule($cart_rule->id, $cart_rule->name[Configuration::get('PS_LANG_DEFAULT')], $values);
                }

                $order_invoice->id_order = $order->id;
                if ($order_invoice->number) {
                    Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $order->id_shop);
                } else {
                    $order_invoice->number = Order::getLastInvoiceNumber() + 1;
                }

                $invoice_address = new Address((int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $order->id_shop)});
                $carrier = new Carrier((int)$order->id_carrier);
                $tax_calculator = $carrier->getTaxCalculator($invoice_address);

                $order_invoice->total_paid_tax_excl = Tools::ps_round((float)$cart->getOrderTotal(false, $total_method), 2);
                $order_invoice->total_paid_tax_incl = Tools::ps_round((float)$cart->getOrderTotal($use_taxes, $total_method), 2);
                $order_invoice->total_products = (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order_invoice->total_products_wt = (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);
                $order_invoice->total_shipping_tax_excl = (float)$cart->getTotalShippingCost(null, false);
                $order_invoice->total_shipping_tax_incl = (float)$cart->getTotalShippingCost();

                $order_invoice->total_wrapping_tax_excl = abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                $order_invoice->total_wrapping_tax_incl = abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order_invoice->shipping_tax_computation_method = (int)$tax_calculator->computation_method;

                // Update current order field, only shipping because other field is updated later
                $order->total_shipping += $order_invoice->total_shipping_tax_incl;
                $order->total_shipping_tax_excl += $order_invoice->total_shipping_tax_excl;
                $order->total_shipping_tax_incl += ($use_taxes) ? $order_invoice->total_shipping_tax_incl : $order_invoice->total_shipping_tax_excl;

                $order->total_wrapping += abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order->total_wrapping_tax_excl += abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                $order->total_wrapping_tax_incl += abs($cart->getOrderTotal($use_taxes, Cart::ONLY_WRAPPING));
                $order_invoice->add();

                $order_invoice->saveCarrierTaxCalculator($tax_calculator->getTaxesAmount($order_invoice->total_shipping_tax_excl));

                $order_carrier = new OrderCarrier();
                $order_carrier->id_order = (int)$order->id;
                $order_carrier->id_carrier = (int)$order->id_carrier;
                $order_carrier->id_order_invoice = (int)$order_invoice->id;
                $order_carrier->weight = (float)$cart->getTotalWeight();
                $order_carrier->shipping_cost_tax_excl = (float)$order_invoice->total_shipping_tax_excl;
                $order_carrier->shipping_cost_tax_incl = ($use_taxes) ? (float)$order_invoice->total_shipping_tax_incl : (float)$order_invoice->total_shipping_tax_excl;
                $order_carrier->add();
            }
            // Update current invoice
            else {
                $order_invoice->total_paid_tax_excl += (float)$cart->getOrderTotal(false, $total_method);
                $order_invoice->total_paid_tax_incl += (float)$cart->getOrderTotal($use_taxes, $total_method);
                $order_invoice->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                $order_invoice->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);
                $order_invoice->update();
            }
        }

        // update totals amount of order
        $order->total_products += (float)$cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $order->total_products_wt += (float)$cart->getOrderTotal($use_taxes, Cart::ONLY_PRODUCTS);

        // Create Order detail information
        $order_detail = new OrderDetail();
        // In case there is no room in the order the the create list will not add taxes as total_products is zero.
        $order_detail->createList($order, $cart, $order->getCurrentOrderState(), $cart->getProducts(), (isset($order_invoice) ? $order_invoice->id : 0), $use_taxes, (int)Tools::getValue('add_product_warehouse'));

        $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

        if (isset($order_invoice) && Validate::isLoadedObject($order_invoice)) {
            $order->total_shipping = $order_invoice->total_shipping_tax_incl;
            $order->total_shipping_tax_incl = $order_invoice->total_shipping_tax_incl;
            $order->total_shipping_tax_excl = $order_invoice->total_shipping_tax_excl;
        }

        // discount
        $order->total_discounts += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
        $order->total_discounts_tax_excl += (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
        $order->total_discounts_tax_incl += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));

        // Save changes of order
        $order->update();

        // Update weight SUM
        $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
        if (Validate::isLoadedObject($order_carrier)) {
            $order_carrier->weight = (float)$order->getTotalWeight();
            if ($order_carrier->update()) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
            }
        }

        // Update Tax lines
        $order_detail->updateTaxAmount($order);

        $products = $this->getProducts($order);

        // Get the last product
        $product = end($products);
        $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
        $resume = OrderSlip::getProductSlipResume((int)$product['id_order_detail']);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);
        $product['return_history'] = OrderReturn::getProductReturnDetail((int)$product['id_order_detail']);

        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int)$product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouse_location)) {
                $product['warehouse_location'] = $warehouse_location;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'product' => $product,
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'can_edit' => $this->tabAccess['edit'],
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex,
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
        ));

        $this->sendChangedNotification($order);
        $new_cart_rules = Context::getContext()->cart->getCartRules();
        sort($old_cart_rules);
        sort($new_cart_rules);
        $result = array_diff($new_cart_rules, $old_cart_rules);
        $refresh = false;

        $res = true;
        foreach ($result as $cart_rule) {
            $refresh = true;
            // Create OrderCartRule
            $rule = new CartRule($cart_rule['id_cart_rule']);
            $values = array(
                'tax_incl' => $rule->getContextualValue(true),
                'tax_excl' => $rule->getContextualValue(false)
            );
            $order_cart_rule = new OrderCartRule();
            $order_cart_rule->id_order = $order->id;
            $order_cart_rule->id_cart_rule = $cart_rule['id_cart_rule'];
            $order_cart_rule->id_order_invoice = $order_invoice->id;
            $order_cart_rule->name = $cart_rule['name'];
            $order_cart_rule->value = $values['tax_incl'];
            $order_cart_rule->value_tax_excl = $values['tax_excl'];
            $res &= $order_cart_rule->add();

            $order->total_discounts += $order_cart_rule->value;
            $order->total_discounts_tax_incl += $order_cart_rule->value;
            $order->total_discounts_tax_excl += $order_cart_rule->value_tax_excl;

            $order->total_paid -= $order_cart_rule->value;
            $order->total_paid = $order->total_paid > 0 ? $order->total_paid : 0;

            $order->total_paid_tax_incl -= $order_cart_rule->value;
            $order->total_paid_tax_incl = $order->total_paid_tax_incl > 0 ? $order->total_paid_tax_incl : 0;

            $order->total_paid_tax_excl -= $order_cart_rule->value_tax_excl;
            $order->total_paid_tax_excl = $order->total_paid_tax_excl > 0 ? $order->total_paid_tax_excl : 0;
        }

        // Update Order
        $res &= $order->update();

        /*By Webkul Entry into table HotelbookingDetail*/
        $objRoomType = new HotelRoomType();
        $objBookingDetail = new HotelBookingDetail();
        $inserted_id_order_detail = $objBookingDetail->getLastInsertedRoomIdOrderDetail($order->id);
        $idLang = (int)$this->context->cart->id_lang;
        $objCartBookingData = new HotelCartBookingData();
        if ($cartBookingData = $objCartBookingData->getOnlyCartBookingData(
            $this->context->cart->id,
            $this->context->cart->id_guest,
            $idProduct
        )) {
            foreach ($cartBookingData as $cb_k => $cb_v) {
                $objCartBookingData = new HotelCartBookingData($cb_v['id']);
                $objCartBookingData->id_order = $order->id;
                $objCartBookingData->save();

                $objBookingDetail = new HotelBookingDetail();
                $objBookingDetail->id_product = $idProduct;
                $objBookingDetail->id_order = $order->id;
                $objBookingDetail->id_order_detail = $inserted_id_order_detail;
                $objBookingDetail->id_cart = $this->context->cart->id;
                $objBookingDetail->id_room = $objCartBookingData->id_room;
                $objBookingDetail->id_hotel = $objCartBookingData->id_hotel;
                $objBookingDetail->id_customer = $order->id_customer;
                $objBookingDetail->booking_type = $objCartBookingData->booking_type;
                $objBookingDetail->id_status = 1;
                $objBookingDetail->comment = $objCartBookingData->comment;
                $objBookingDetail->room_type_name = Product::getProductName($idProduct, null, $order->id_lang);

                $objBookingDetail->date_from = $objCartBookingData->date_from;
                $objBookingDetail->date_to = $objCartBookingData->date_to;
                $objBookingDetail->adults = $objCartBookingData->adults;
                $objBookingDetail->children = $objCartBookingData->children;
                $objBookingDetail->child_ages = $objCartBookingData->child_ages;

                $occupancy = array(
                    array(
                        'adults' => $objCartBookingData->adults,
                        'children' => $objCartBookingData->children,
                        'child_ages' => json_decode($objCartBookingData->child_ages)
                    )
                );

                $total_price = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice(
                    $idProduct,
                    $objCartBookingData->date_from,
                    $objCartBookingData->date_to,
                    $occupancy,
                    Group::getCurrent()->id,
                    $this->context->cart->id,
                    $this->context->cookie->id_guest,
                    $objCartBookingData->id_room,
                    0
                );
                $objBookingDetail->total_price_tax_excl = $total_price['total_price_tax_excl'];
                $objBookingDetail->total_price_tax_incl = $total_price['total_price_tax_incl'];
                $objBookingDetail->total_paid_amount = $total_price['total_price_tax_incl'];

                // Save hotel information/location/contact
                if (Validate::isLoadedObject($objRoom = new HotelRoomInformation($objCartBookingData->id_room))) {
                    $objBookingDetail->room_num = $objRoom->room_num;
                }
                if (Validate::isLoadedObject($objHotelBranch = new HotelBranchInformation(
                    $objCartBookingData->id_hotel,
                    $idLang
                ))) {
                    $addressInfo = $objHotelBranch->getAddress($objCartBookingData->id_hotel);
                    $objBookingDetail->hotel_name = $objHotelBranch->hotel_name;
                    $objBookingDetail->city = $addressInfo['city'];
                    $objBookingDetail->state = State::getNameById($addressInfo['id_state']);
                    $objBookingDetail->country = Country::getNameById($idLang, $addressInfo['id_country']);
                    $objBookingDetail->zipcode = $addressInfo['postcode'];;
                    $objBookingDetail->phone = $addressInfo['phone'];
                    $objBookingDetail->email = $objHotelBranch->email;
                    $objBookingDetail->check_in_time = $objHotelBranch->check_in;
                    $objBookingDetail->check_out_time = $objHotelBranch->check_out;
                }
                if ($roomTypeInfo = $objRoomType->getRoomTypeInfoByIdProduct($idProduct)) {
                    $objBookingDetail->adults = $objCartBookingData->adults;
                    $objBookingDetail->children = $objCartBookingData->children;
                    $objBookingDetail->child_ages = $objCartBookingData->child_ages;
                }

                if ($objBookingDetail->save()) {
                    $objRoomTypeServiceProduct = new RoomTypeServiceProduct();
                    $objRoomTypeServiceProductPrice = new RoomTypeServiceProductPrice();
                    $objServiceProductCartDetail = new ServiceProductCartDetail();
                    if ($services = $objServiceProductCartDetail->getServiceProductsInCart(
                        $objCartBookingData->id_cart,
                        [],
                        null,
                        $objCartBookingData->id,
                        null,
                        null,
                        null,
                        null,
                        0,
                        1
                    )) {
                        foreach ($services as $service) {
                            $insertedServiceProductIdOrderDetail = $objBookingDetail->getLastInsertedServiceIdOrderDetail($order->id, $service['id_product']);
                            $numDays = 1;
                            if (Product::getProductPriceCalculation($service['id_product']) == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                                $numDays = HotelHelper::getNumberOfDays($objBookingDetail->date_from, $objBookingDetail->date_to);
                            }

                            $totalPriceTaxExcl = Product::getServiceProductPrice(
                                (int) $service['id_product'],
                                0,
                                false,
                                $roomTypeInfo['id'],
                                false,
                                1,
                                $objBookingDetail->date_from,
                                $objBookingDetail->date_to
                            );
                            $totalPriceTaxIncl = Product::getServiceProductPrice(
                                (int)$service['id_product'],
                                0,
                                false,
                                $roomTypeInfo['id'],
                                true,
                                1,
                                $objBookingDetail->date_from,
                                $objBookingDetail->date_to
                            );
                            $unitPriceTaxIncl = Product::getServiceProductPrice(
                                (int)$service['id_product'],
                                0,
                                false,
                                $roomTypeInfo['id'],
                                true,
                                1,
                                $objBookingDetail->date_from,
                                $objBookingDetail->date_to
                            ) / $numDays;
                            $unitPriceTaxExcl = Product::getServiceProductPrice(
                                (int) $service['id_product'],
                                0,
                                false,
                                $roomTypeInfo['id'],
                                false,
                                1,
                                $objBookingDetail->date_from,
                                $objBookingDetail->date_to
                            ) / $numDays;

                            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
                            $objServiceProductOrderDetail->id_product = $service['id_product'];
                            $objServiceProductOrderDetail->id_order = $objBookingDetail->id_order;
                            $objServiceProductOrderDetail->id_order_detail = $insertedServiceProductIdOrderDetail;
                            $objServiceProductOrderDetail->id_cart = $cart->id;
                            $objServiceProductOrderDetail->id_htl_booking_detail = $objBookingDetail->id;
                            $objServiceProductOrderDetail->unit_price_tax_excl = $unitPriceTaxExcl;
                            $objServiceProductOrderDetail->unit_price_tax_incl = $unitPriceTaxIncl;
                            $objServiceProductOrderDetail->total_price_tax_excl = $totalPriceTaxExcl;
                            $objServiceProductOrderDetail->total_price_tax_incl = $totalPriceTaxIncl;
                            $objServiceProductOrderDetail->name = $service['name'];
                            $objServiceProductOrderDetail->quantity = 1;
                            $objServiceProductOrderDetail->save();
                        }
                    }
                }
            }
        }

        // delete cart feature prices after room addition success
        HotelRoomTypeFeaturePricing::deleteFeaturePrices($this->context->cart->id);

        die(json_encode(array(
            'result' => true,
            //'view' => $this->createTemplate('_product_line.tpl')->fetch(),
            'can_edit' => $this->tabAccess['edit'],
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch(),
            'refresh' => $refresh
        )));
    }

    // Add a stanalone or hotel standalone product to an order
    public function ajaxProcessAddProductOnOrder()
    {
        // Validate the request
        $response = array(
            'status' => true
        );

        // Check tab access is allowed to edit
        $productInformations = $_POST['add_product'];
        $idOrder = (int) Tools::getValue('id_order');
        $idProduct = $productInformations['product_id'];
        $objOrder = new Order($idOrder);
        $idProductOption = null;
        $addressTax = new Address($objOrder->id_address_tax, $this->context->language->id);
        if (!$this->tabAccess['edit'] === 1) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('You do not have permission to edit this order.');
        } elseif (!Validate::isLoadedObject($objOrder)) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('The order object cannot be loaded.');
        } elseif (!Validate::isUnsignedInt($productInformations['product_quantity'])) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Please enter a valid quantity.');
        } elseif (!Validate::isPrice($productInformations['product_price_tax_incl'])) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Please enter a valid tax included price.');
        } elseif (!Validate::isPrice($productInformations['product_price_tax_excl'])) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Please enter a valid tax excluded price.');
        } elseif (!Validate::isPrice(($productInformations['product_quantity'] * $productInformations['product_price_tax_incl']))) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('The product can not be added with entered price and quantities.');
        } elseif (!Validate::isLoadedObject($objProduct = new Product($idProduct, false, $objOrder->id_lang))) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('The product object cannot be loaded.');
        }  elseif ($objProduct->booking_product
            || $objProduct->selling_preference_type == Product::SELLING_PREFERENCE_WITH_ROOM_TYPE
        ) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Invalid product. Please try adding any other product.');
        } elseif ($objProduct->selling_preference_type != Product::SELLING_PREFERENCE_STANDALONE
            && (!$addressTax->id_hotel
            || !Validate::isLoadedObject($objHotel = new HotelBranchInformation($addressTax->id_hotel)))
        ) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('The hotel object cannot be loaded.');
        } elseif (ServiceProductOption::productHasOptions($idProduct)) {
            if (!$productInformations['product_option']) {
                $this->errors[] = Tools::displayError('Cannot add service without a option.');
            } else {
                $idProductOption = $productInformations['product_option'];
            }
        }

        if ($response['status']) {
            $totalMethod = Cart::BOTH_WITHOUT_SHIPPING;
            // Create new cart
            $objCart = new Cart();
            $objCart->id_shop_group = $objOrder->id_shop_group;
            $objCart->id_shop = $objOrder->id_shop;
            $objCart->id_customer = $objOrder->id_customer;
            $objCart->id_carrier = $objOrder->id_carrier;
            $objCart->id_address_delivery = $objOrder->id_address_delivery;
            $objCart->id_address_invoice = $objOrder->id_address_invoice;
            $objCart->id_currency = $objOrder->id_currency;
            $objCart->id_lang = $objOrder->id_lang;
            $objCart->secure_key = $objOrder->secure_key;
            // Save new cart
            if ($objCart->add()) {
                $this->context->cart = $objCart;
                $this->context->customer = new Customer($objOrder->id_customer);

                // always add taxes even if not displayed to the customer
                $useTaxes = true;
                $this->context->currency = new Currency($objOrder->id_currency);

                $objServiceProductCartDetail = new ServiceProductCartDetail();

                $initialProductPriceTI = Product::getPriceStatic(
                    $idProduct,
                    $useTaxes,
                    $idProductOption,
                    2,
                    null,
                    false,
                    true,
                    1,
                    false,
                    $objOrder->id_customer,
                    $objCart->id,
                    $objOrder->id_address_tax
                );

                // Creating specific price if needed
                if ($productInformations['product_price_tax_incl'] != $initialProductPriceTI) {
                    if ($this->context->cart->id_currency != (int)Configuration::get('PS_CURRENCY_DEFAULT')) {
                        $productInformations['product_price_tax_excl'] = Tools::convertPrice($productInformations['product_price_tax_excl'], $this->context->cart->id_currency, false);
                        $productInformations['product_price_tax_excl'] = Tools::ps_round($productInformations['product_price_tax_excl'], _PS_PRICE_COMPUTE_PRECISION_);
                    }

                    $objSpecificPrice = new SpecificPrice();
                    $objSpecificPrice->id_shop = 0;
                    $objSpecificPrice->id_shop_group = 0;
                    $objSpecificPrice->id_currency = 0;
                    $objSpecificPrice->id_country = 0;
                    $objSpecificPrice->id_group = 0;
                    $objSpecificPrice->id_cart = $objCart->id;
                    $objSpecificPrice->id_customer = $objOrder->id_customer;
                    $objSpecificPrice->id_product = $idProduct;
                    $objSpecificPrice->id_product_attribute = $idProductOption;
                    $objSpecificPrice->price = $productInformations['product_price_tax_excl'];
                    $objSpecificPrice->from_quantity = 1;
                    $objSpecificPrice->reduction = 0;
                    $objSpecificPrice->reduction_type = 'amount';
                    $objSpecificPrice->reduction_tax = 0;
                    $objSpecificPrice->from = '0000-00-00 00:00:00';
                    $objSpecificPrice->to = '0000-00-00 00:00:00';
                    $objSpecificPrice->add();
                }

                if (!$updateQuantity = $objServiceProductCartDetail->updateCartServiceProduct(
                    $objCart->id,
                    $idProduct,
                    'up',
                    $productInformations['product_quantity'],
                    isset($addressTax->id_hotel) ? $addressTax->id_hotel : 0,
                    false,
                    isset($productInformations['product_option']) ? $productInformations['product_option'] : null
                )) {
                    $response['status'] = false;
                    $response['error'] = Tools::displayError('Unable to add quantity of this product.');
                }
            } else {
                $response['status'] = false;
                $response['error'] = Tools::displayError('Some error occurred while processing the cart.');
            }

            if ($response['status']) {
                // If product is standalone and current order is for a hotel then we have to create a new order for this product
                $response['new_id_order'] = 0;
                if ($objProduct->selling_preference_type == Product::SELLING_PREFERENCE_STANDALONE && $addressTax->id_hotel) {
                    $objPaymentModule = new BoOrder();
                    $objEmployee = new Employee($this->context->cookie->id_employee);

                    $objPaymentModule->validateOrder(
                        $objCart->id,
                        Configuration::get('PS_OS_AWAITING_PAYMENT'),
                        0,
                        $objPaymentModule->displayName,
                        $this->l('Manual order -- Employee:').' '.substr($objEmployee->firstname, 0, 1).'. '.$objEmployee->lastname,
                        null,
                        null,
                        false,
                        $objCart->secure_key
                    );

                    if (isset($this->context->cookie->id_cart)) {
                        unset($this->context->cookie->id_cart);
                    }
                    if (isset($this->context->cookie->id_guest)) {
                        unset($this->context->cookie->id_guest);
                    }

                    if ($objPaymentModule->currentOrder) {
                        $response['status'] = true;
                        $response['new_id_order'] = $objPaymentModule->currentOrder;
                    } else {
                        $response['status'] = false;
                        $response['error'] = Tools::displayError('Some error occurred while processing the order.');
                    }
                } else {
                    // If product is Hotel standalone then we will add in the same orders

                    // If order is valid, we can create a new invoice or edit an existing invoice
                    if ($objOrder->hasInvoice()) {
                        $objOrderInvoice = new OrderInvoice($productInformations['invoice']);
                        // Create new invoice
                        if ($objOrderInvoice->id == 0) {
                            // If we create a new invoice, we calculate shipping cost
                            $totalMethod = Cart::BOTH;
                            $objOrderInvoice->id_order = $objOrder->id;
                            if ($objOrderInvoice->number) {
                                Configuration::updateValue('PS_INVOICE_START_NUMBER', false, false, null, $objOrder->id_shop);
                            } else {
                                $objOrderInvoice->number = Order::getLastInvoiceNumber() + 1;
                            }

                            $invoice_address = new Address((int) $objOrder->{Configuration::get('PS_TAX_ADDRESS_TYPE', null, null, $objOrder->id_shop)});
                            $carrier = new Carrier((int)$objOrder->id_carrier);
                            $tax_calculator = $carrier->getTaxCalculator($invoice_address);
                            $objOrderInvoice->total_paid_tax_excl = Tools::ps_round((float)$objCart->getOrderTotal(false, $totalMethod), 2);
                            $objOrderInvoice->total_paid_tax_incl = Tools::ps_round((float)$objCart->getOrderTotal($useTaxes, $totalMethod), 2);
                            $objOrderInvoice->total_products = (float)$objCart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                            $objOrderInvoice->total_products_wt = (float)$objCart->getOrderTotal($useTaxes, Cart::ONLY_PRODUCTS);
                            $objOrderInvoice->total_shipping_tax_excl = (float)$objCart->getTotalShippingCost(null, false);
                            $objOrderInvoice->total_shipping_tax_incl = (float)$objCart->getTotalShippingCost();

                            $objOrderInvoice->total_wrapping_tax_excl = abs($objCart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                            $objOrderInvoice->total_wrapping_tax_incl = abs($objCart->getOrderTotal($useTaxes, Cart::ONLY_WRAPPING));
                            $objOrderInvoice->shipping_tax_computation_method = (int)$tax_calculator->computation_method;

                            // Update current order field, only shipping because other field is updated later
                            $objOrder->total_shipping += $objOrderInvoice->total_shipping_tax_incl;
                            $objOrder->total_shipping_tax_excl += $objOrderInvoice->total_shipping_tax_excl;
                            $objOrder->total_shipping_tax_incl += ($useTaxes) ? $objOrderInvoice->total_shipping_tax_incl : $objOrderInvoice->total_shipping_tax_excl;

                            $objOrder->total_wrapping += abs($objCart->getOrderTotal($useTaxes, Cart::ONLY_WRAPPING));
                            $objOrder->total_wrapping_tax_excl += abs($objCart->getOrderTotal(false, Cart::ONLY_WRAPPING));
                            $objOrder->total_wrapping_tax_incl += abs($objCart->getOrderTotal($useTaxes, Cart::ONLY_WRAPPING));
                            $objOrderInvoice->add();
                        } else {
                            // Update current invoice
                            $objOrderInvoice->total_paid_tax_excl += (float)$objCart->getOrderTotal(false, $totalMethod);
                            $objOrderInvoice->total_paid_tax_incl += (float)$objCart->getOrderTotal($useTaxes, $totalMethod);
                            $objOrderInvoice->total_products += (float)$objCart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                            $objOrderInvoice->total_products_wt += (float)$objCart->getOrderTotal($useTaxes, Cart::ONLY_PRODUCTS);
                            $objOrderInvoice->update();
                        }
                    }

                    $objOrderDetail = new OrderDetail();
                    $cartProducts = $objCart->getProducts();
                    if ($cartProducts[0]['selling_preference_type'] == Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE) {
                        $cartProducts[0]['selling_preference_type'] = Product::SELLING_PREFERENCE_HOTEL_STANDALONE;
                    }
                    $objOrderDetail->createList($objOrder, $objCart, $objOrder->getCurrentOrderState(), $cartProducts, (isset($objOrderInvoice) ? $objOrderInvoice->id : 0), $useTaxes, (int)Tools::getValue('add_product_warehouse'));

                    // update totals amount of order
                    $objOrder->total_products += (float)$objCart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
                    $objOrder->total_products_wt += (float)$objCart->getOrderTotal($useTaxes, Cart::ONLY_PRODUCTS);

                    $objOrder->total_paid = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                    $objOrder->total_paid_tax_incl = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                    $objOrder->total_paid_tax_excl = Tools::ps_round($objOrder->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                    if (isset($objOrderInvoice) && Validate::isLoadedObject($objOrderInvoice)) {
                        $objOrder->total_shipping = $objOrderInvoice->total_shipping_tax_incl;
                        $objOrder->total_shipping_tax_incl = $objOrderInvoice->total_shipping_tax_incl;
                        $objOrder->total_shipping_tax_excl = $objOrderInvoice->total_shipping_tax_excl;
                    }

                    // discount
                    $objOrder->total_discounts += (float)abs($objCart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
                    $objOrder->total_discounts_tax_excl += (float)abs($objCart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
                    $objOrder->total_discounts_tax_incl += (float)abs($objCart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));

                    // Save changes of order
                    $objOrder->update();

                    // Update Tax lines
                    $objOrderDetail->updateTaxAmount($objOrder);

                    $this->sendChangedNotification($objOrder);

                    if ($serviceProducts = $objServiceProductCartDetail->getServiceProductsInCart(
                        $this->context->cart->id,
                        [],
                        null,
                        null,
                        null,
                        $idProduct
                    )) {
                        foreach ($serviceProducts as $serviceProduct) {
                            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
                            $objServiceProductOrderDetail->id_product = $idProduct;
                            $objServiceProductOrderDetail->id_product_option = $serviceProduct['id_product_option'];
                            $objServiceProductOrderDetail->id_order = $objOrder->id;
                            $objServiceProductOrderDetail->id_order_detail = $objOrderDetail->id;
                            $objServiceProductOrderDetail->id_cart = $this->context->cart->id;
                            $objServiceProductOrderDetail->id_hotel = isset($addressTax->id_hotel) ? $addressTax->id_hotel : 0;
                            $objServiceProductOrderDetail->unit_price_tax_excl = $serviceProduct['unit_price_tax_excl'];
                            $objServiceProductOrderDetail->unit_price_tax_incl = $serviceProduct['unit_price_tax_incl'];
                            $objServiceProductOrderDetail->total_price_tax_excl = $serviceProduct['total_price_tax_excl'];
                            $objServiceProductOrderDetail->total_price_tax_incl = $serviceProduct['total_price_tax_incl'];
                            $objServiceProductOrderDetail->name = $serviceProduct['name'];
                            $objServiceProductOrderDetail->option_name = $serviceProduct['option_name'];
                            $objServiceProductOrderDetail->quantity = $serviceProduct['quantity'];
                            if ($objServiceProductOrderDetail->id_hotel) {
                                if (Validate::isLoadedObject(
                                    $objHotelBranch = new HotelBranchInformation($objServiceProductOrderDetail->id_hotel, $this->context->cart->id_lang)
                                )) {
                                    $objServiceProductOrderDetail->hotel_name = $objHotelBranch->hotel_name;
                                }
                            }
                            $objServiceProductOrderDetail->save();
                        }

                        $response['status'] = true;
                    } else {
                        $response['status'] = false;
                        $response['error'] = Tools::displayError('Some error occurred while processing the order.');
                    }
                }
            }
        }

        $this->ajaxDie(json_encode($response));
    }

    /**
     * This function is called when order is changed (Add/Edit/Delete room on order)
     */
    public function sendChangedNotification(?Order $order = null)
    {
        if (is_null($order)) {
            $order = new Order(Tools::getValue('id_order'));
        }

        $this->updateOrderStatusOnOrderChange($order);

        // load updated object
        $order = new Order(Tools::getValue('id_order'));

        Hook::exec('actionOrderEdited', array('order' => $order));
    }

    /**
     * This function is called to manage order status when order is changed
     */
    public function updateOrderStatusOnOrderChange($objOrder)
    {
        // If order is completely refunded or cancelled then change the order state
        if ($idOrderState = $objOrder->getOrderCompleteRefundStatus()) {
            $objOrderHistory = new OrderHistory();
            $objOrderHistory->id_order = (int)$objOrder->id;
            $useExistingPayment = false;
            if (!$objOrder->hasInvoice()) {
                $useExistingPayment = true;
            }
            $objOrderHistory->changeIdOrderState($idOrderState, $objOrder, $useExistingPayment);
            $objOrderHistory->add();
        } else {
            // check if new order amount is greater that old order amount and order payment is accepted
            // then update order status to partial payment accepted
            $currentOrderState = $objOrder->getCurrentOrderState();
            $psOsPartialPaymentAccepted = Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED');
            if ($currentOrderState->paid == 1 && $currentOrderState->id != $psOsPartialPaymentAccepted) {
                // calculate due amount
                $dueAmount = $objOrder->total_paid_tax_incl - $objOrder->total_paid_real;
                if ($dueAmount > 0) {
                    // now change order status to partial payment
                    $objOrderHistory = new OrderHistory();
                    $objOrderHistory->id_order = $objOrder->id;
                    $objOrderHistory->id_employee = (int) $this->context->employee->id;

                    $useExistingPayment = false;
                    if (!$objOrder->hasInvoice()) {
                        $useExistingPayment = true;
                    }

                    $objOrderHistory->changeIdOrderState($psOsPartialPaymentAccepted, $objOrder, $useExistingPayment);
                    $objOrderHistory->add();
                }
            }
        }
    }

    public function ajaxProcessLoadProductInformation()
    {
        $order_detail = new OrderDetail(Tools::getValue('id_order_detail'));
        if (!Validate::isLoadedObject($order_detail))
        	die(json_encode(array(
        		'result' => false,
        		'error' => Tools::displayError('The OrderDetail object cannot be loaded.')
        	)));

        die(json_encode(array(
            'result' => true,
            'reduction_percent' => $order_detail->reduction_percent
        )));
    }

    public function ajaxProcessEditRoomOnOrder()
    {
        // Check tab access is allowed to edit
        if (!$this->tabAccess['edit'] === 1) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You do not have permission to edit this order.')
            )));
        }

        // Return value
        $res = true;
        $id_order = (int) Tools::getValue('id_order');
        $order = new Order($id_order);
        //$order_detail = new OrderDetail((int)Tools::getValue('product_id_order_detail'));
        $order_detail = new OrderDetail((int) Tools::getValue('id_order_detail'));//by webkul id_order_detail from our table
        $this->doEditRoomValidation($order_detail, $order, isset($order_invoice) ? $order_invoice : null);

        if (Tools::isSubmit('product_invoice')) {
            $order_invoice = new OrderInvoice((int) Tools::getValue('product_invoice'));
        }

        /*By webkul To edit Order and cart entries when edit rooms from the orderLine when editing the order*/
        $obj_booking_detail = new HotelBookingDetail((int) Tools::getValue('id_booking_detail'));
        $cart = new Cart($obj_booking_detail->id_cart);

        $product_informations = $_POST['edit_product'];
        $new_date_from = trim(date('Y-m-d H:i:s', strtotime($product_informations['date_from'])));
        $new_date_to = trim(date('Y-m-d H:i:s', strtotime($product_informations['date_to'])));
        $old_date_from = trim(Tools::getValue('date_from'));
        $old_date_to = trim(Tools::getValue('date_to'));
        $id_hotel = trim(Tools::getValue('id_hotel'));
        $id_room = trim(Tools::getValue('id_room'));
        $id_product = trim(Tools::getValue('id_product'));
        $room_unit_price = trim(Tools::getValue('room_unit_price'));
        $product_quantity = (int) HotelHelper::getNumberOfDays($new_date_from, $new_date_to);
        $old_product_quantity =  (int) HotelHelper::getNumberOfDays($old_date_from, $old_date_to);
        $qty_diff = $product_quantity - $old_product_quantity;

        $occupancy = Tools::getValue('occupancy');
        $occupancy = array_shift($occupancy);
        $adults = $occupancy['adults'];
        $children = $occupancy['children'];
        $child_ages = isset($occupancy['child_ages']) ? $occupancy['child_ages'] : false;

        $totalRoomPriceBeforeTE = $obj_booking_detail->total_price_tax_excl;
        $totalRoomPriceBeforeTI = $obj_booking_detail->total_price_tax_incl;
        $totalRoomPriceAfterTE = 0;
        $totalRoomPriceAfterTI = 0;

        if (Validate::isLoadedObject($objProduct = new Product($id_product))) {
            // If room type is NOT DELETED then use current room type price calculation
            $params = array(
                'id_cart' => $cart->id,
                'id_guest' => $cart->id_guest,
                'id_product' => $id_product,
                'id_room' => $id_room,
                'impact_value' => $room_unit_price,
                'restrictions' => array(
                    array(
                        'date_from' => $new_date_from,
                        'date_to' => $new_date_to
                    )
                )
            );
            HotelRoomTypeFeaturePricing::createRoomTypeFeaturePrice($params);

            $roomTotalPrice = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice(
                $id_product,
                $new_date_from,
                $new_date_to,
                0,
                Group::getCurrent()->id,
                $cart->id,
                $cart->id_guest,
                $id_room,
                0
            );

            $totalRoomPriceAfterTE = (float) $roomTotalPrice['total_price_tax_excl'];
            $totalRoomPriceAfterTI = (float) $roomTotalPrice['total_price_tax_incl'];
        } else {
            // If room type is DELETED use order details information for price calculation
            $taxCalculator = $order_detail->getTaxCalculator();
            // room price with tax
            $roomPriceTI = $taxCalculator->addTaxes($room_unit_price);

            $totalRoomPriceAfterTE = Tools::processPriceRounding($room_unit_price, $product_quantity, $order->round_type, $order->round_mode);
            $totalRoomPriceAfterTI = Tools::processPriceRounding($roomPriceTI, $product_quantity, $order->round_type, $order->round_mode);
        }

        // delete cart feature prices after booking update success
        HotelRoomTypeFeaturePricing::deleteFeaturePrices($cart->id);

        // Calculate differences of price (Before / After)
        $diff_price_tax_incl = $totalRoomPriceAfterTI - $totalRoomPriceBeforeTI;
        $diff_price_tax_excl = $totalRoomPriceAfterTE - $totalRoomPriceBeforeTE;

        // Apply change on OrderInvoice
        if (isset($order_invoice)) {
            // If OrderInvoice to use is different, we update the old invoice and new invoice
            if ($order_detail->id_order_invoice != $order_invoice->id) {
                $old_order_invoice = new OrderInvoice($order_detail->id_order_invoice);
                // We remove cost of products
                $old_order_invoice->total_products -= $order_detail->total_price_tax_excl;
                $old_order_invoice->total_products_wt -= $order_detail->total_price_tax_incl;
                $old_order_invoice->total_paid_tax_excl -= $order_detail->total_price_tax_excl;
                $old_order_invoice->total_paid_tax_incl -= $order_detail->total_price_tax_incl;

                $res &= $old_order_invoice->update();

                $order_invoice->total_products += $order_detail->total_price_tax_excl;
                $order_invoice->total_products_wt += $order_detail->total_price_tax_incl;
                $order_invoice->total_paid_tax_excl += $order_detail->total_price_tax_excl;
                $order_invoice->total_paid_tax_incl += $order_detail->total_price_tax_incl;

                $order_detail->id_order_invoice = $order_invoice->id;
            }
        }

        if ($diff_price_tax_incl != 0 && $diff_price_tax_excl != 0) {
            $order_detail->total_price_tax_incl += (float)$diff_price_tax_incl;
            $order_detail->total_price_tax_excl += (float)$diff_price_tax_excl;

            $order_detail->unit_price_tax_incl = $order_detail->total_price_tax_incl / ($order_detail->product_quantity + $qty_diff);
            $order_detail->unit_price_tax_excl = $order_detail->total_price_tax_excl / ($order_detail->product_quantity + $qty_diff);


            if (isset($order_invoice)) {
                // Apply changes on OrderInvoice
                $order_invoice->total_paid_tax_excl += (float)$diff_price_tax_excl;
                $order_invoice->total_paid_tax_incl += (float)$diff_price_tax_incl;
            }

            // Apply changes on Order
            $order = new Order($order_detail->id_order);
            $order->total_products += (float)$diff_price_tax_excl;
            $order->total_products = $order->total_products > 0 ? $order->total_products : 0;
            $order->total_products_wt += (float)$diff_price_tax_incl;
            $order->total_products_wt = $order->total_products_wt > 0 ? $order->total_products_wt : 0;

            $res &= $order->update();
        }

        $old_quantity = $order_detail->product_quantity;

        $order_detail->product_quantity = $old_quantity + $qty_diff;
        $order_detail->reduction_percent = 0;

        // update taxes
        $res &= $order_detail->updateTaxAmount($order);

        // Save order detail
        $res &= $order_detail->update();

        // Update weight SUM
        $order_carrier = new OrderCarrier((int)$order->getIdOrderCarrier());
        if (Validate::isLoadedObject($order_carrier)) {
            $order_carrier->weight = (float)$order->getTotalWeight();
            $res &= $order_carrier->update();
            if ($res) {
                $order->weight = sprintf("%.3f ".Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
            }
        }

        if ($qty_diff != 0) {
            $cartQty = $qty_diff;
            $op = 'up';
            if ($qty_diff < 0) {
                $op = 'down';
                $cartQty = -$cartQty;
            }

            $cart->updateQty($cartQty, $obj_booking_detail->id_product, null, false, $op, 0, null, true);
        }

        // Update product available quantity
        StockAvailable::updateQuantity($order_detail->product_id, $order_detail->product_attribute_id, ($old_quantity - $order_detail->product_quantity), $order->id_shop);

        $products = $this->getProducts($order);
        // Get the last product
        $product = $products[$order_detail->id];
        $product['current_stock'] = StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], $product['id_shop']);
        $resume = OrderSlip::getProductSlipResume($order_detail->id);
        $product['quantity_refundable'] = $product['product_quantity'] - $resume['product_quantity'];
        $product['amount_refundable'] = $product['total_price_tax_excl'] - $resume['amount_tax_excl'];
        $product['amount_refund'] = Tools::displayPrice($resume['amount_tax_incl']);

        if ($product['id_warehouse'] != 0) {
            $warehouse = new Warehouse((int)$product['id_warehouse']);
            $product['warehouse_name'] = $warehouse->name;
            $warehouse_location = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse']);
            if (!empty($warehouse_location)) {
                $product['warehouse_location'] = $warehouse_location;
            } else {
                $product['warehouse_location'] = false;
            }
        } else {
            $product['warehouse_name'] = '--';
            $product['warehouse_location'] = false;
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'product' => $product,
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'can_edit' => $this->tabAccess['edit'],
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex,
            'display_warehouse' => (int)Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')
        ));

        if (!$res) {
            die(json_encode(array(
                'result' => $res,
                'error' => Tools::displayError('An error occurred while editing the product line.')
            )));
        }

        // get extra demands of the room before changing in the booking table
        $objBookingDemand = new HotelBookingDemands();
        $objServiceProductOrderDetail = new ServiceProductOrderDetail();
        $orderServiceProducts = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
            $id_order,
            0,
            0,
            0,
            $old_date_from,
            $old_date_to,
            $id_room,
            0,
            null,
            null,
            null,
            0,
            $obj_booking_detail->id
        );

        $extraDemands = $objBookingDemand->getRoomTypeBookingExtraDemands(
            $id_order,
            0,
            $id_room,
            $old_date_from,
            $old_date_to,
            1,
            0,
            1,
            $obj_booking_detail->id
        );

        // set occupancy details
        $occupancy = array(
            'adults' => $adults,
            'children' => $children,
            'child_ages' => $child_ages
        );

        /*By webkul to edit the Hotel Cart and Hotel Order tables when editing the room for the order detail page*/
        $new_total_price = array(
            'tax_excl' => $totalRoomPriceAfterTE,
            'tax_incl' => $totalRoomPriceAfterTI,
        );

        if ($obj_booking_detail->updateHotelCartHotelOrderOnOrderEdit(
            $id_order,
            $id_room,
            $old_date_from,
            $old_date_to,
            $new_date_from,
            $new_date_to,
            $occupancy,
            $new_total_price,
            $obj_booking_detail->id
        )) {
            // update extra demands total prices if dates are changes (price calc method for each day)
            if ($extraDemands) {
                foreach ($extraDemands as $demand) {
                    if (isset($demand['extra_demands']) && $demand['extra_demands']) {
                        foreach ($demand['extra_demands'] as $rDemand) {
                            if ($rDemand['price_calc_method'] == HotelRoomTypeGlobalDemand::WK_PRICE_CALC_METHOD_EACH_DAY) {
                                $objBookingDemand = new HotelBookingDemands($rDemand['id_booking_demand']);

                                $numDays = HotelHelper::getNumberOfDays($new_date_from, $new_date_to);
                                $demandPriceTE = Tools::processPriceRounding(
                                    ($objBookingDemand->unit_price_tax_excl * $numDays),
                                    1,
                                    $order->round_type,
                                    $order->round_mode
                                );
                                $demandPriceTI = Tools::processPriceRounding(
                                    ($objBookingDemand->unit_price_tax_incl * $numDays),
                                    1,
                                    $order->round_type,
                                    $order->round_mode
                                );

                                $objBookingDemand->total_price_tax_excl = $demandPriceTE;
                                $objBookingDemand->total_price_tax_incl = $demandPriceTI;

                                $objBookingDemand->save();

                                if (isset($order_invoice)) {
                                    $order_invoice->total_paid_tax_excl -= $rDemand['total_price_tax_excl'];
                                    $order_invoice->total_paid_tax_incl -= $rDemand['total_price_tax_incl'];

                                    $order_invoice->total_paid_tax_excl += (float)$objBookingDemand->total_price_tax_excl;
                                    $order_invoice->total_paid_tax_incl += (float)$objBookingDemand->total_price_tax_incl;
                                }
                            }
                        }
                    }
                }

            }

            if (isset($orderServiceProducts)
                && is_array($orderServiceProducts)
                && count($orderServiceProducts)
            ) {
                foreach ($orderServiceProducts as $orderServiceProduct) {
                    if (isset($orderServiceProduct['additional_services'])
                        && is_array($orderServiceProduct['additional_services'])
                        && count($orderServiceProduct['additional_services'])
                    ) {
                        foreach ($orderServiceProduct['additional_services'] as $serviceProduct) {
                            if ($serviceProduct['price_calculation_method'] == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                                $newNumDays = HotelHelper::getNumberOfDays($new_date_from, $new_date_to);
                                $objServiceProductOrderDetail = new ServiceProductOrderDetail((int) $serviceProduct['id_service_product_order_detail']);
                                $unitPriceTaxExcl = $objServiceProductOrderDetail->unit_price_tax_excl;
                                $unitPriceTaxIncl = $objServiceProductOrderDetail->unit_price_tax_incl;

                                $newTotalPriceTaxExcl = Tools::processPriceRounding(
                                    ($newNumDays * $unitPriceTaxExcl),
                                    $objServiceProductOrderDetail->quantity,
                                    $order->round_type,
                                    $order->round_mode
                                );
                                $newTotalPriceTaxIncl = Tools::processPriceRounding(
                                    ($newNumDays * $unitPriceTaxIncl),
                                    $objServiceProductOrderDetail->quantity,
                                    $order->round_type,
                                    $order->round_mode
                                );

                                $order->total_products -= $objServiceProductOrderDetail->total_price_tax_excl;
                                $order->total_products_wt -= $objServiceProductOrderDetail->total_price_tax_incl;

                                // change order total
                                $order->total_products += $newTotalPriceTaxExcl;
                                $order->total_products_wt += $newTotalPriceTaxIncl;

                                $objOrderDetail = new OrderDetail((int) $serviceProduct['id_order_detail']);
                                $objOrderDetail->total_price_tax_excl -= $objServiceProductOrderDetail->total_price_tax_excl;
                                $objOrderDetail->total_price_tax_incl -= $objServiceProductOrderDetail->total_price_tax_incl;

                                $objOrderDetail->total_price_tax_excl += $newTotalPriceTaxExcl;
                                $objOrderDetail->total_price_tax_incl += $newTotalPriceTaxIncl;

                                $oldNumDays = HotelHelper::getNumberOfDays($old_date_from, $old_date_to);
                                $oldProductQuantity = $objServiceProductOrderDetail->quantity * $oldNumDays;
                                $newProductQuantity = $objServiceProductOrderDetail->quantity * $newNumDays;
                                $objOrderDetail->product_quantity += ($newProductQuantity - $oldProductQuantity);

                                $objOrderDetail->save();
                                $this->context->cart = new Cart($objServiceProductOrderDetail->id_cart);
                                $objOrderDetail->updateTaxAmount($order);

                                if (isset($order_invoice)) {
                                    $order_invoice->total_paid_tax_excl -= $objServiceProductOrderDetail->total_price_tax_excl;
                                    $order_invoice->total_paid_tax_incl -= $objServiceProductOrderDetail->total_price_tax_incl;

                                    $order_invoice->total_paid_tax_excl += (float)$newTotalPriceTaxExcl;
                                    $order_invoice->total_paid_tax_incl += (float)$newTotalPriceTaxIncl;
                                }
                                $objServiceProductOrderDetail->unit_price_tax_excl = $unitPriceTaxExcl;
                                $objServiceProductOrderDetail->unit_price_tax_incl = $unitPriceTaxIncl;
                                $objServiceProductOrderDetail->total_price_tax_excl = $newTotalPriceTaxExcl;
                                $objServiceProductOrderDetail->total_price_tax_incl = $newTotalPriceTaxIncl;
                                $objServiceProductOrderDetail->save();
                            }
                        }
                    }
                }
            }
        }

        $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);
        $order->save();

        // Save order invoice
        if (isset($order_invoice) && $order_invoice instanceof OrderInvoice) {
            $order_invoice->total_products = $order_invoice->total_paid_tax_excl;
            $order_invoice->total_products_wt = $order_invoice->total_paid_tax_incl;
            $res &= $order_invoice->update();
        }

        if (is_array(Tools::getValue('product_quantity'))) {
            $view = $this->createTemplate('_customized_data.tpl')->fetch();
        } else {
            $view = $this->createTemplate('_product_line.tpl')->fetch();
        }

        $this->sendChangedNotification($order);
        die(json_encode(array(
            'result' => $res,
            'view' => $view,
            'can_edit' => $this->tabAccess['edit'],
            'invoices_collection' => $invoice_collection,
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch(),
            'customized_product' => is_array(Tools::getValue('product_quantity'))
        )));
    }

    // ajax to EDIT the standalone and hotel standalone products
    public function ajaxProcessEditProductOnOrder()
    {
        // Validate the request
        $response = array(
            'status' => true
        );

        $idOrder = (int) Tools::getValue('id_order');
        $objOrder = new Order($idOrder);
        $editProductInfo = Tools::getValue('edit_product');
        if (!$this->tabAccess['edit'] === 1) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('You do not have permission to edit this order.');
        } elseif (!Validate::isLoadedObject($objOrder)) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Order information not found.');
        } elseif (!ValidateCore::isLoadedObject(
            $objServiceProductOrderDetail = new ServiceProductOrderDetail(
                $editProductInfo['id_service_product_order_detail']
            )
        )) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Product information not found.');
        } elseif (!Validate::isUnsignedInt($editProductInfo['product_quantity'])) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Please enter a valid quantity.');
        } elseif (!Validate::isPrice($editProductInfo['product_price_tax_excl'])) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Please enter a valid tax excluded price.');
        } elseif (!Validate::isPrice(($editProductInfo['product_quantity'] * $editProductInfo['product_price_tax_excl']))) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('The product can not be added with entered price and quantities.');
        }

        if ($response['status']) {
            $result = true;
            if (Tools::isSubmit('product_invoice')) {
                $objOrderInvoice = new OrderInvoice((int)Tools::getValue('product_invoice'));
            }

            $objOrderDetail = new OrderDetail((int)$objServiceProductOrderDetail->id_order_detail);
            // If multiple product_quantity, the order details concern a product customized

            $product_quantity = $editProductInfo['product_quantity'];
            if ($updateQty = (int)($product_quantity - $objServiceProductOrderDetail->quantity)) {
                $this->checkStockAvailable($objOrderDetail, $updateQty);

            }
            $taxRate =  $objServiceProductOrderDetail->unit_price_tax_incl / $objServiceProductOrderDetail->unit_price_tax_excl;

            $unitPriceTaxExcl = $editProductInfo['product_price_tax_excl'];
            $unitPriceTaxIncl = $unitPriceTaxExcl * $taxRate;

            $totalProductsTaxExcl = Tools::processPriceRounding($unitPriceTaxExcl, $product_quantity);
            $totalProductsTaxIncl = Tools::processPriceRounding($unitPriceTaxIncl, $product_quantity);

            // Calculate differences of price (Before / After)
            $diffPriceTaxExcl = $totalProductsTaxExcl - $objServiceProductOrderDetail->total_price_tax_excl;
            $diffPriceTaxIncl = $totalProductsTaxIncl - $objServiceProductOrderDetail->total_price_tax_incl;

            if ($diffPriceTaxIncl != 0 && $diffPriceTaxExcl != 0) {
                // Apply change on OrderInvoice
                if ($objOrderDetail->id_order_invoice) {
                    $objOldOrderInvoice = new OrderInvoice($objOrderDetail->id_order_invoice);
                    $objOldOrderInvoice->total_products += $diffPriceTaxExcl;
                    $objOldOrderInvoice->total_products_wt += $diffPriceTaxIncl;
                    $objOldOrderInvoice->total_paid_tax_excl += $diffPriceTaxExcl;
                    $objOldOrderInvoice->total_paid_tax_incl += $diffPriceTaxIncl;

                    $result &= $objOldOrderInvoice->update();
                }
            }
            $objOrderDetail->total_price_tax_incl += $diffPriceTaxIncl;
            $objOrderDetail->total_price_tax_excl += $diffPriceTaxExcl;
            $objOrderDetail->product_quantity += $updateQty;
            $objOrderDetail->reduction_percent = 0;

            // update taxes
            $result &= $objOrderDetail->updateTaxAmount($objOrder);

            // Save order detail
            $result &= $objOrderDetail->update();

            $objServiceProductOrderDetail->unit_price_tax_excl = $unitPriceTaxExcl;
            $objServiceProductOrderDetail->unit_price_tax_incl = $unitPriceTaxIncl;
            $objServiceProductOrderDetail->total_price_tax_excl = $totalProductsTaxExcl;
            $objServiceProductOrderDetail->total_price_tax_incl = $totalProductsTaxIncl;
            $objServiceProductOrderDetail->quantity += $updateQty;
            $result &= $objServiceProductOrderDetail->update();

            // Apply changes on Order
            $objOrder = new Order($objOrderDetail->id_order);
            $objOrder->total_products += $diffPriceTaxExcl;
            $objOrder->total_products = $objOrder->total_products > 0 ? $objOrder->total_products : 0;
            $objOrder->total_products_wt += $diffPriceTaxIncl;
            $objOrder->total_products_wt = $objOrder->total_products_wt > 0 ? $objOrder->total_products_wt : 0;

            $objOrder->total_paid = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
            $objOrder->total_paid_tax_incl = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
            $objOrder->total_paid_tax_excl = Tools::ps_round($objOrder->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);
            $result &= $objOrder->update();


            // Save order invoice
            if (isset($objOrderInvoice)) {
                $objOrderInvoice->total_products_wt -= $diffPriceTaxIncl;
                $objOrderInvoice->total_paid_tax_excl += $diffPriceTaxExcl;
                $objOrderInvoice->total_paid_tax_incl += $diffPriceTaxIncl;
                $result &= $objOrderInvoice->update();
            }

            // Update product available quantity
            if ($updateQty) {
                $result &= StockAvailable::updateQuantity($objOrderDetail->product_id, $objOrderDetail->product_attribute_id, -(int)$updateQty, $objOrder->id_shop);
            }

            if (!$result) {
                $response['status'] = false;
                $response['error'] = Tools::displayError('Some error has been occurred while updating the product. Please try again.');
            } else {
                $this->sendChangedNotification($objOrder);
            }
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDeleteRoomLine()
    {
        // Check tab access is allowed to edit
        if (!$this->tabAccess['edit'] === 1) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You do not have permission to edit this order.')
            )));
        }

        $res = true;
        $order_detail = new OrderDetail((int) Tools::getValue('id_order_detail'));
        $id_order = (int) Tools::getValue('id_order');
        $order = new Order($id_order);
        /*By webkul To delete Order and cart entries when deleting rooms from the orderLine when editing the order*/
        /*By webkul to validate booking before deleting the cart and order data form the tables*/
        if (($idHotelBooking = Tools::getValue('id_htl_booking')) == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Booking id not found.'),
            )));
        } elseif (!Validate::isLoadedObject($objBookingDetail = new HotelBookingDetail($idHotelBooking))) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Booking Detail not found.'),
            )));
        }
        $product_quantity = (int) HotelHelper::getNumberOfDays($objBookingDetail->date_from, $objBookingDetail->date_to);
        if (!Validate::isUnsignedInt($product_quantity)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Invalid quantity.'),
            )));
        }

        /*END*/
        $this->doDeleteProductLineValidation($order_detail, $order);
        Hook::exec('actionOrderProductLineDeleteBefore', array('order' => $order));

        $bookingPriceTaxIncl = Tools::processPriceRounding($objBookingDetail->total_price_tax_incl, 1, $order->round_type, $order->round_mode);
        $bookingPriceTaxExcl = Tools::processPriceRounding($objBookingDetail->total_price_tax_excl, 1, $order->round_type, $order->round_mode);

        $objBookingDemand = new HotelBookingDemands();
        $roomExtraDemandTI = $objBookingDemand->getRoomTypeBookingExtraDemands(
            $id_order,
            $objBookingDetail->id_product,
            $objBookingDetail->id_room,
            $objBookingDetail->date_from,
            $objBookingDetail->date_to,
            0,
            1,
            1,
            $objBookingDetail->id
        );
        $roomExtraDemandTE = $objBookingDemand->getRoomTypeBookingExtraDemands(
            $id_order,
            $objBookingDetail->id_product,
            $objBookingDetail->id_room,
            $objBookingDetail->date_from,
            $objBookingDetail->date_to,
            0,
            1,
            0,
            $objBookingDetail->id
        );

        $objServiceProductOrderDetail = new ServiceProductOrderDetail();
        $additionlServicesTI = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            1,
            1,
            null, // to get the auto added services.
            null,
            0,
            $idHotelBooking
        );

        $additionlServicesTE = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            1,
            0,
            null, // to get the auto added services.
            null,
            0,
            $idHotelBooking
        );

        $selectedAdditonalServices = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            null,
            null, // to get the auto added services.
            null,
            0,
            $idHotelBooking
        );

        //This is added to remove the services from the cart along with room deletion process.
        $objHotelCartBookingData = new HotelCartBookingData();
        $roomHtlCartInfo = $objHotelCartBookingData->getRoomRowByIdProductIdRoomInDateRange(
            $objBookingDetail->id_cart,
            $objBookingDetail->id_product,
            $objBookingDetail->date_from,
            $objBookingDetail->date_to,
            $objBookingDetail->id_room
        );

        /*$totalProductPriceBeforeTE = $order_detail->total_price_tax_excl;
        $totalProductPriceBeforeTI = $order_detail->total_price_tax_incl;*/
        // by webkul to calculate rates of the product from hotelreservation syatem tables with feature prices....
        /* $hotelCartBookingData = new HotelCartBookingData();
         $roomTypesByIdProduct = $hotelCartBookingData->getCartInfoIdCartIdProduct($this->id, $product['id_product']);*/

        /*This code below to alter the values in the order detail table*/
        //$diff_products_tax_incl = $order_detail->unit_price_tax_incl * $product_quantity;
        //$diff_products_tax_excl = $order_detail->unit_price_tax_excl * $product_quantity;
        $diff_products_tax_incl = $bookingPriceTaxIncl;
        $diff_products_tax_excl = $bookingPriceTaxExcl;

        $delete = false;
        if ($product_quantity >= $order_detail->product_quantity) {
            $delete = true;
        } else {
            // Calculate differences of price (Before / After)

            $order_detail->total_price_tax_incl -= $diff_products_tax_incl;
            $order_detail->total_price_tax_excl -= $diff_products_tax_excl;

            $old_quantity = $order_detail->product_quantity;

            $order_detail->product_quantity = $old_quantity - $product_quantity;
            $order_detail->reduction_percent = 0;

            // update taxes
            $res &= $order_detail->updateTaxAmount($order);

            // Save order detail
            $res &= $order_detail->update();
        }
        // update order detail for selected aditional services
        $objServiceProductCartDetail = new ServiceProductCartDetail();
        if (isset($selectedAdditonalServices[$idHotelBooking])) {
            $objServiceProductCartDetail = new ServiceProductCartDetail();
            foreach ($selectedAdditonalServices[$idHotelBooking]['additional_services'] as $service) {
                $serviceOrderDetail = new OrderDetail($service['id_order_detail']);

                $cart_quantity = $service['quantity'];
                if ($service['price_calculation_method'] == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                    $cart_quantity = $cart_quantity * $product_quantity;
                }
                if ($cart_quantity >= $serviceOrderDetail->product_quantity) {
                    $serviceOrderDetail->delete();
                } else {
                    $serviceOrderDetail->total_price_tax_incl -= $service['total_price_tax_incl'];
                    $serviceOrderDetail->total_price_tax_excl -= $service['total_price_tax_excl'];
                    $serviceOrderDetail->product_quantity -= $cart_quantity;

                    // update taxes
                    $res &= $serviceOrderDetail->updateTaxAmount($order);

                    // Save order detail
                    $res &= $serviceOrderDetail->update();
                }

                if (isset($roomHtlCartInfo['id'])) {
                    if ($idServiceProductCartDetail = $objServiceProductCartDetail->alreadyExists(
                        $service['id_product'],
                        $selectedAdditonalServices[$idHotelBooking]['id_cart'],
                        $roomHtlCartInfo['id']
                    )) {
                        $objServiceProductCartDetail = new ServiceProductCartDetail((int) $idServiceProductCartDetail);
                        $serviceQuantity = $objServiceProductCartDetail->quantity;
                        if ($objServiceProductCartDetail->delete()
                            && Validate::isLoadedObject(new Product((int) $service['id_product']))
                        ) {
                            $objCart = new Cart($selectedAdditonalServices[$idHotelBooking]['id_cart']);
                            $objCart->updateQty((int)abs($serviceQuantity), $service['id_product'], null, false, 'down');
                        }
                    }
                }
            }
        }
        /*End*/

        // Update OrderInvoice of this OrderDetail
        if ($order_detail->id_order_invoice != 0) {
            // values changes as values are calculated accoding to the quantity of the product by webkul
            $order_invoice = new OrderInvoice($order_detail->id_order_invoice);
            $order_invoice->total_paid_tax_excl -= ($diff_products_tax_excl + $roomExtraDemandTE + $additionlServicesTE);
            $order_invoice->total_paid_tax_excl = $order_invoice->total_paid_tax_excl > 0 ? $order_invoice->total_paid_tax_excl : 0;

            $order_invoice->total_paid_tax_incl -= ($diff_products_tax_incl + $roomExtraDemandTI + $additionlServicesTI);
            $order_invoice->total_paid_tax_incl = $order_invoice->total_paid_tax_incl > 0 ? $order_invoice->total_paid_tax_incl : 0;

            $order_invoice->total_products -= $diff_products_tax_excl;
            $order_invoice->total_products = $order_invoice->total_products > 0 ? $order_invoice->total_products : 0;

            $order_invoice->total_products_wt -= $diff_products_tax_incl;
            $order_invoice->total_products_wt = $order_invoice->total_products_wt > 0 ? $order_invoice->total_products_wt : 0;

            $res &= $order_invoice->update();
        }

        // Reinject quantity in stock
        $order_detail->reinjectQuantity($order_detail, $order_detail->product_quantity, $delete);

        // Update Order
        // values changes as values are calculated accoding to the quantity of the product by webkul
        $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal() - $roomExtraDemandTI, _PS_PRICE_COMPUTE_PRECISION_);
        $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false) - $roomExtraDemandTE, _PS_PRICE_COMPUTE_PRECISION_);

        $order->total_products -= ($diff_products_tax_excl + $additionlServicesTE);
        $order->total_products = $order->total_products > 0 ? $order->total_products : 0;

        $order->total_products_wt -= ($diff_products_tax_incl + $additionlServicesTI);
        $order->total_products_wt = $order->total_products_wt > 0 ? $order->total_products_wt : 0;

        $res &= $order->update();

        // Update weight SUM
        $order_carrier = new OrderCarrier((int) $order->getIdOrderCarrier());
        if (Validate::isLoadedObject($order_carrier)) {
            $order_carrier->weight = (float) $order->getTotalWeight();
            $res &= $order_carrier->update();
            if ($res) {
                $order->weight = sprintf('%.3f '.Configuration::get('PS_WEIGHT_UNIT'), $order_carrier->weight);
            }
        }

        if (!$res) {
            die(json_encode(array(
                'result' => $res,
                'error' => Tools::displayError('An error occurred while attempting to delete the product line.')
            )));
        }

        // Get invoices collection
        $invoice_collection = $order->getInvoicesCollection();

        $invoice_array = array();
        foreach ($invoice_collection as $invoice) {
            /** @var OrderInvoice $invoice */
            $invoice->name = $invoice->getInvoiceNumberFormatted(Context::getContext()->language->id, (int)$order->id_shop);
            $invoice_array[] = $invoice;
        }

        // delete the demands od this booking
        $objBookingDemand->deleteBookingDemands($idHotelBooking);

        $objServiceProductOrderDetail->deleteSeviceProducts(0, $idHotelBooking);

        /*By webkul to delete cart and order entries from cart and order tables of hotelreservationsystem when delete booking form the order line in order detaoil page*/
        $obj_htl_cart_booking = new HotelCartBookingData();
        $delete_room_order = $obj_htl_cart_booking->deleteCartBookingData(
            $objBookingDetail->id_cart,
            $objBookingDetail->id_product,
            $objBookingDetail->id_room,
            $objBookingDetail->date_from,
            $objBookingDetail->date_to,
            1,
            $objBookingDetail->id_order
        );

        $objBookingDetail->delete();

        // delete refund request of the room if exists.
        OrderReturnDetail::deleteReturnDetailByIdBookingDetail($id_order, $idHotelBooking);

        // Reducing the quantity from the cart after removing the room from the order
        $objCart = new Cart($objBookingDetail->id_cart);
        $objCart->updateQty($product_quantity, $objBookingDetail->id_product, null, false, 'down', 0, null, true);

        // If no rooms left in the order, delete all cart rules of the order
        if (!$objBookingDetail->getBookingDataByOrderId($order->id)) {
            if ($orderDiscounts = $order->getCartRules()) {
                foreach ($orderDiscounts as $orderDiscount) {
                    $objOrderCartRule = new OrderCartRule($orderDiscount['id_order_cart_rule']);
                    $objOrderCartRule->delete();
                }

            }

            // set the discounts in the invoices as 0 after all the cart rules are deleted.
            if ($order_detail->id_order_invoice != 0) {
                $order_invoice->total_discount_tax_incl = 0;
                $order_invoice->total_discount_tax_excl = 0;
                $res &= $order_invoice->update();
            }

            // If no rooms left in the order, Update Order total and discounts to 0
            $order->total_discounts = 0;
            $order->total_discounts_tax_incl = 0;
            $order->total_discounts_tax_excl = 0;
            $order->total_paid = 0;
            $order->total_paid_tax_incl = 0;
            $order->total_paid_tax_excl = 0;
            $order->update();
        }

        // Assign to smarty informations in order to show the new product line
        $this->context->smarty->assign(array(
            'order' => $order,
            'currency' => new Currency($order->id_currency),
            'invoices_collection' => $invoice_collection,
            'current_id_lang' => Context::getContext()->language->id,
            'link' => Context::getContext()->link,
            'current_index' => self::$currentIndex,
            'can_edit' => ($this->tabAccess['edit'] === 1),
        ));

        $this->sendChangedNotification($order);
        die(json_encode(array(
            'result' => $res,
            'order' => $order,
            'invoices' => $invoice_array,
            'documents_html' => $this->createTemplate('_documents.tpl')->fetch(),
            'shipping_html' => $this->createTemplate('_shipping.tpl')->fetch()
        )));
    }

    // Ajax to delete the standalone|hotel standalone products
    public function ajaxProcessDeleteProductLine()
    {
        // Validate the request
        $response = array(
            'status' => true
        );

        $idOrder = Tools::getValue('id_order');
        $idOrderDetail = Tools::getValue('id_order_detail');
        $idServiceProdOrderDetail = Tools::getValue('id_service_product_order_detail');

        $objOrder = new Order($idOrder);
        $objOrderDetail = new OrderDetail($idOrderDetail);
        if (!$this->tabAccess['edit'] === 1) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('You do not have permission to edit this order.');
        } elseif (!Validate::isLoadedObject($objOrder)) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Order information not found.');
        } elseif (!Validate::isLoadedObject($objOrderDetail)) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Product information not found.');
        } elseif (!Validate::isLoadedObject(
            $objServiceProductOrderDetail = new ServiceProductOrderDetail($idServiceProdOrderDetail)
        )) {
            $response['status'] = false;
            $response['error'] = Tools::displayError('Product information not found.');
        } elseif ($idOrder !== $objOrderDetail->id_order) {
            $response['error'] = Tools::displayError('Product information not found. Please try after reloading the page');
        }

        if ($response['status']) {
            $result = true;
            $total_price_tax_excl = $objServiceProductOrderDetail->total_price_tax_excl;
            $total_price_tax_incl = $objServiceProductOrderDetail->total_price_tax_incl;
            $qty = $objServiceProductOrderDetail->quantity;

            // Update OrderInvoice of this OrderDetail
            if ($objOrderDetail->id_order_invoice != 0) {
                $objOrderInvoice = new OrderInvoice($objOrderDetail->id_order_invoice);
                $objOrderInvoice->total_paid_tax_excl -= $total_price_tax_excl;
                $objOrderInvoice->total_paid_tax_incl -= $total_price_tax_incl;
                $objOrderInvoice->total_products -= $total_price_tax_excl;
                $objOrderInvoice->total_products_wt -= $total_price_tax_incl;
                $result &= $objOrderInvoice->update();
            }
            // Reinject quantity in stock
            if ($qty >= $objOrderDetail->product_quantity) {
                $objOrderDetail->reinjectQuantity($objOrderDetail, $qty, true);
            } else {
                $objOrderDetail->reinjectQuantity($objOrderDetail, $qty);
                $objOrderDetail->total_price_tax_incl -= (float)$total_price_tax_incl;
                $objOrderDetail->total_price_tax_excl -= (float)$total_price_tax_excl;
                $objOrderDetail->product_quantity -= $qty;
                $objOrderDetail->update();
            }

            if ($result &= $objServiceProductOrderDetail->delete()) {
                // Update Order
                $objOrder->total_paid = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                $objOrder->total_paid_tax_incl = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                $objOrder->total_paid_tax_excl = Tools::ps_round($objOrder->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                $objOrder->total_products -= $total_price_tax_excl;
                $objOrder->total_products = $objOrder->total_products > 0 ? $objOrder->total_products : 0;

                $objOrder->total_products_wt -= $total_price_tax_incl;
                $objOrder->total_products_wt = $objOrder->total_products_wt > 0 ? $objOrder->total_products_wt : 0;

                $result &= $objOrder->update();
            }

            if (!$result) {
                $response['status'] = false;
                $response['error'] = Tools::displayError('Some error has been occurred while updating the product. Please try again.');
            } else {
                $this->sendChangedNotification($objOrder);
            }
        }

        $this->ajaxDie(json_encode($response));
    }

    protected function doEditProductValidation(OrderDetail $order_detail, Order $order, ?OrderInvoice $order_invoice = null)
    {
        $this->doEditValidation($order_detail, $order, $order_invoice);

        if (!is_array(Tools::getValue('product_quantity')) && !Validate::isUnsignedInt(Tools::getValue('product_quantity'))) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Invalid quantity')
            )));
        } elseif (is_array(Tools::getValue('product_quantity'))) {
            foreach (Tools::getValue('product_quantity') as $qty) {
                if (!Validate::isUnsignedInt($qty)) {
                    die(json_encode(array(
                        'result' => false,
                        'error' => Tools::displayError('Invalid quantity')
                    )));
                }
            }
        }
    }

    /**
     * Check if the room edit is valid. Check for the following things:
     *  - Check if the hotel booking is refunded or cancelled
     *  - Check if the room price, tax incl and excl is valid
     *  - Check if the room is already booked for the new date range
     *  - Check if the room is disabled for the new date range
     *  - Check if the hotel booking is with occupancy and children age is valid
     *  - Check if the new date is before current date
     * @param OrderDetail $order_detail
     * @param Order $order
     * @param OrderInvoice $order_invoice
     * @return bool
     */
    protected function doEditRoomValidation(OrderDetail $order_detail, Order $order, ?OrderInvoice $order_invoice = null)
    {
        $this->doEditValidation($order_detail, $order, $order_invoice);

        $id_hotel_booking = Tools::getValue('id_booking_detail');
        $product_price_tax_incl = str_replace(',', '.', Tools::getValue('product_price_tax_incl'));
        $product_price_tax_excl = str_replace(',', '.', Tools::getValue('product_price_tax_excl'));
        $product_informations = $_POST['edit_product'];
        $new_date_from = trim(date('Y-m-d H:i:s', strtotime($product_informations['date_from'])));
        $new_date_to = trim(date('Y-m-d H:i:s', strtotime($product_informations['date_to'])));
        $old_date_from = date('Y-m-d H:i:s', strtotime(trim(Tools::getValue('date_from'))));
        $old_date_to = date('Y-m-d H:i:s', strtotime(trim(Tools::getValue('date_to'))));
        $id_hotel = trim(Tools::getValue('id_hotel'));
        $id_room = trim(Tools::getValue('id_room'));
        $id_product = trim(Tools::getValue('id_product'));
        $room_unit_price = trim(Tools::getValue('room_unit_price'));
        $obj_booking_detail = new HotelBookingDetail();
        $product_quantity = (int) HotelHelper::getNumberOfDays($new_date_from, $new_date_to);
        $old_product_quantity =  (int) HotelHelper::getNumberOfDays($old_date_from, $old_date_to);
        $qty_diff = $product_quantity - $old_product_quantity;
        $occupancy = Tools::getValue('occupancy');
        $occupancy = array_shift($occupancy);
        $adults = $occupancy['adults'];
        $children = $occupancy['children'];
        $child_ages = isset($occupancy['child_ages']) ? $occupancy['child_ages'] : false;

        // If order is refunded, the validate changes which are not allowed
        $objOrderReturn = new OrderReturn();
        $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($order->id, 0, 1);
        $objBookingDetail = new HotelBookingDetail($id_hotel_booking);
        if ($refundReqBookings && (in_array($id_hotel_booking, $refundReqBookings))) {
            // If order is cancelled, we can't edit order
            if ($objBookingDetail->is_refunded && $objBookingDetail->is_cancelled) {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('Booking cannot be edited if booking is cancelled.'),
                )));
            // If order is refunded, we can't edit dates
            } elseif ($objBookingDetail->is_refunded
                && (strtotime($old_date_from) != strtotime($new_date_from) || strtotime($old_date_to) != strtotime($new_date_to))
            ) {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('Check-In/Check-Out dates cannot be changed if booking is refunded.'),
                )));
            }
        }

        if ($objBookingDetail->id_status != HotelBookingDetail::STATUS_ALLOTED
            && strtotime($old_date_from) != strtotime($new_date_from)
        ) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot update the start date for a booking that has already been checked in.'),
            )));
        } else if ($objBookingDetail->id_status == HotelBookingDetail::STATUS_CHECKED_OUT
            && strtotime($old_date_to) != strtotime($new_date_to)
        ) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot update the end date for a booking that has already been checked out.'),
            )));
        }

        if (!Validate::isPrice($product_price_tax_incl) || !Validate::isPrice($product_price_tax_excl)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Invalid price')
            )));
        }

        if ($id_hotel == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Hotel Id is mising.'),
            )));
        } elseif ($id_room == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Room Id is missing.'),
            )));
        } elseif ($new_date_from == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter Check-In date.'),
            )));
        } elseif (!Validate::isDateFormat($new_date_from)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter a valid Check-In date.'),
            )));
        } elseif ($new_date_to == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter Check-Out date.'),
            )));
        } elseif (!Validate::isDateFormat($new_date_to)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter a valid Check-out date.'),
            )));
        } elseif ($new_date_to <= $new_date_from) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Check-out date should be after Check-In date.'),
            )));
        } elseif ($room_unit_price == '') {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter unit price.'),
            )));
        } elseif (!Validate::isPrice($room_unit_price)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Please enter a valid unit price.'),
            )));
        } elseif (!Validate::isUnsignedInt($product_quantity)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Invalid quantity.'),
            )));
        }

        // validations if order is with occupancy
        if (!isset($adults) || !$adults || !Validate::isUnsignedInt($adults)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Invalid number of adults.'),
            )));
        } elseif (!Validate::isUnsignedInt($children)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('Invalid number of children.'),
            )));
        }

        if ($children > 0) {
            if (!isset($child_ages) || ($children != count($child_ages))) {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('Please provide all children age.'),
                )));
            } else {
                foreach($child_ages as $childAge) {
                    if (!Validate::isUnsignedInt($childAge)) {
                        die(json_encode(array(
                            'result' => false,
                            'error' => Tools::displayError('Invalid children age.'),
                        )));
                    }
                }
            }
        }

        if ($this->context->employee->isSuperAdmin()) {
            $backOrderConfigKey = 'PS_BACKDATE_ORDER_SUPERADMIN';
        } else {
            $backOrderConfigKey = 'PS_BACKDATE_ORDER_EMPLOYEES';
        }
        if (!Configuration::get($backOrderConfigKey)) {
            $compareDate = min(date('Y-m-d'), $old_date_from);
            if ($new_date_from < $compareDate) {
                die(json_encode(array(
                    'result' => false,
                    'error' => sprintf(Tools::displayError('Check-In date should not be date before %s.'),$compareDate)
                )));
            }
        }

        if ($rooms_booked = $obj_booking_detail->getRoomBookinInformationForDateRangeByOrder(
            $id_room,
            $old_date_from,
            $old_date_to,
            $new_date_from,
            $new_date_to
        )) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('This room is unavailable for selected duration.'),
            )));
        }

        $objHotelRoomDisableDates = new HotelRoomDisableDates();
        $params = array(
            'id_room' => $id_room,
            'date_from' => $new_date_from,
            'date_to' => $new_date_to
        );
        if ($objHotelRoomDisableDates->checkIfRoomAlreadyDisabled($params)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('This room is disabled in selected duration.'),
            )));
        }
    }

    protected function doEditValidation(OrderDetail $order_detail, Order $order, ?OrderInvoice $order_invoice = null)
    {
        if (!Validate::isLoadedObject($order_detail)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The Order Detail object could not be loaded.')
            )));
        }

        if (!empty($order_invoice) && !Validate::isLoadedObject($order_invoice)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The invoice object cannot be loaded.')
            )));
        }

        if (!Validate::isLoadedObject($order)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The order object cannot be loaded.')
            )));
        }

        if ($order_detail->id_order != $order->id) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot edit the order detail for this order.')
            )));
        }

        // We can't edit a delivered order
        if ($order->hasBeenDelivered()) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot edit a delivered order.')
            )));
        }

        if (!empty($order_invoice) && $order_invoice->id_order != Tools::getValue('id_order')) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot use this invoice for the order')
            )));
        }
    }

    protected function doDeleteProductLineValidation(OrderDetail $order_detail, Order $order)
    {
        if (!Validate::isLoadedObject($order_detail)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The Order Detail object could not be loaded.')
            )));
        }

        if (!Validate::isLoadedObject($order)) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('The order object cannot be loaded.')
            )));
        }

        if ($order_detail->id_order != $order->id) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot delete the order detail.')
            )));
        }

        // We can't edit a delivered order
        if ($order->hasBeenDelivered()) {
            die(json_encode(array(
                'result' => false,
                'error' => Tools::displayError('You cannot edit a delivered order.')
            )));
        }
    }

    /**
     * @param $order_detail
     * @param $add_quantity
     */
    protected function checkStockAvailable($order_detail, $add_quantity)
    {
        if ($add_quantity > 0) {
            $StockAvailable = StockAvailable::getQuantityAvailableByProduct($order_detail->product_id, $order_detail->product_attribute_id, $order_detail->id_shop);
            $product = new Product($order_detail->product_id, true, null, $order_detail->id_shop);
            if (!Validate::isLoadedObject($product)) {
                die(json_encode(array(
                    'result' => false,
                    'error' => Tools::displayError('The Product object could not be loaded.')
                )));
            } else {
                if (($StockAvailable < $add_quantity) && (!$product->isAvailableWhenOutOfStock((int)$product->out_of_stock))) {
                    die(json_encode(array(
                        'result' => false,
                        'error' => Tools::displayError('This product is no longer in stock with those attributes ')
                    )));
                }
            }
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getProducts($order)
    {
        $products = $order->getProducts();

        foreach ($products as &$product) {
            if ($product['image'] != null) {
                $name = 'product_mini_'.(int)$product['product_id'].(isset($product['product_attribute_id']) ? '_'.(int)$product['product_attribute_id'] : '').'.jpg';
                // generate image cache, only for back office
                $product['image_tag'] = ImageManager::thumbnail(_PS_IMG_DIR_.'p/'.$product['image']->getExistingImgPath().'.jpg', $name, 45, 'jpg');
                if (file_exists(_PS_TMP_IMG_DIR_.$name)) {
                    $product['image_size'] = getimagesize(_PS_TMP_IMG_DIR_.$name);
                } else {
                    $product['image_size'] = false;
                }
            }
        }

        ksort($products);

        return $products;
    }

    protected function getPaymentsTypes()
    {
        return array(
            OrderPayment::PAYMENT_TYPE_PAY_AT_HOTEL => array(
                'key' => 'PAYMENT_TYPE_PAY_AT_HOTEL',
                'value' => OrderPayment::PAYMENT_TYPE_PAY_AT_HOTEL,
                'name' => $this->l('Pay at hotel')
            ),
            OrderPayment::PAYMENT_TYPE_ONLINE => array(
                'key' => 'PAYMENT_TYPE_ONLINE',
                'value' => OrderPayment::PAYMENT_TYPE_ONLINE,
                'name' => $this->l('Online')
            ),
            OrderPayment::PAYMENT_TYPE_REMOTE_PAYMENT => array(
                'key' => 'PAYMENT_TYPE_REMOTE_PAYMENT',
                'value' => OrderPayment::PAYMENT_TYPE_REMOTE_PAYMENT,
                'name' => $this->l('Remote payment')
            ),
        );
    }

    /**
     * @param OrderInvoice $order_invoice
     * @param float $value_tax_incl
     * @param float $value_tax_excl
     */
    protected function applyDiscountOnInvoice($order_invoice, $value_tax_incl, $value_tax_excl)
    {
        // Update OrderInvoice
        $order_invoice->total_discount_tax_incl += $value_tax_incl;
        $order_invoice->total_discount_tax_excl += $value_tax_excl;
        $order_invoice->total_paid_tax_incl -= $value_tax_incl;
        $order_invoice->total_paid_tax_excl -= $value_tax_excl;
        $order_invoice->update();
    }

    public function ajaxProcessChangePaymentMethod()
    {
        $customer = new Customer(Tools::getValue('id_customer'));
        $modules = Module::getAuthorizedModules($customer->id_default_group);
        $authorized_modules = array();

        if (!Validate::isLoadedObject($customer) || !is_array($modules)) {
            die(json_encode(array('result' => false)));
        }

        foreach ($modules as $module) {
            $authorized_modules[] = (int)$module['id_module'];
        }

        $payment_modules = array();

        foreach (PaymentModule::getInstalledPaymentModules() as $p_module) {
            if (in_array((int)$p_module['id_module'], $authorized_modules)) {
                $payment_modules[] = Module::getInstanceById((int)$p_module['id_module']);
            }
        }

        $this->context->smarty->assign(array(
            'payment_modules' => $payment_modules,
        ));

        die(json_encode(array(
            'result' => true,
            'view' => $this->createTemplate('_select_payment.tpl')->fetch(),
        )));
    }

    /*By Webkul to delete the rooms added in the cart*/
    public function ajaxProcessDeleteRoomProcess()
    {
        $dt_frm = Tools::getValue('date_from');
        $dt_to = Tools::getValue('date_to');
        $cart_id = Tools::getValue('id_cart');
        $id_product = Tools::getValue('id_product');
        $del_id = Tools::getValue('del_id');
        $room_id = Tools::getValue('id_room');
        $obj_hotel_cart_detail = new HotelCartBookingData();
        if ($obj_hotel_cart_detail->deleteCartBookingData($cart_id, $id_product, $room_id, $dt_frm, $dt_to)) {
            $obj_product_process = new HotelCartBookingData();
            $num_cart_rooms = 0;
            $dt_to = date('Y-m-d H:i:s', strtotime($dt_to) - _TIME_1_DAY_);
            HotelRoomTypeFeaturePricing::deleteFeaturePrices($cart_id, $id_product, $room_id, $dt_frm, $dt_to);
            if ($rooms = $obj_hotel_cart_detail->getCartCurrentDataByCartId($cart_id)) {
                $num_cart_rooms = count($rooms);
            }

            $result['status'] = 'deleted';
            $result['cart_rooms'] = $num_cart_rooms;
        } else {
            $result['status'] = 'failed';
        }

        die(json_encode($result));
    }

    public function ajaxProcessDeleteProductProcess()
    {
        $errors = array();
        if ((!$id_product = (int)Tools::getValue('id_product')) || !Validate::isInt($id_product)) {
            $errors[] = Tools::displayError('Invalid product');
        }
        $cart_id = Tools::getValue('id_cart');
        if (!Validate::isLoadedObject($objCart = new Cart($cart_id))) {
            $errors[] = Tools::displayError('Cart not found');
        }
        if (count($errors)) {
            die(json_encode($errors));
        }
        if ($objCart->deleteProduct($id_product)) {
            die(json_encode(array('status' => 'deleted')));
        }
    }

    // To show rooms extra demands in the modal box in order details view page
    public function ajaxProcessGetRoomTypeBookingDemands()
    {
        $orderEdit = Tools::getValue('orderEdit');
        if (($idProduct = Tools::getValue('id_product'))
            && ($idOrder = Tools::getValue('id_order'))
            && ($idRoom = Tools::getValue('id_room'))
            && ($dateFrom = Tools::getValue('date_from'))
            && ($dateTo = Tools::getValue('date_to'))
            && ($idHtlBooking = Tools::getValue('id_htl_booking'))
        ) {
            $smartyVars = array();
            $objOrder = new Order($idOrder);
            $objCurrency = new Currency($objOrder->id_currency);
            $smartyVars['orderCurrency'] = $objOrder->id_currency;
            $smartyVars['currencySign'] = $objCurrency->sign;
            $smartyVars['link'] = $this->context->link;

            $smartyVars['id_booking_detail'] = $idHtlBooking;

            $objBookingDemand = new HotelBookingDemands();

            // set context currency So that we can get prices in the order currency
            $this->context->currency = $objCurrency;

            if ($extraDemands = $objBookingDemand->getRoomTypeBookingExtraDemands(
                $idOrder,
                $idProduct,
                $idRoom,
                $dateFrom,
                $dateTo,
                1,
                0,
                1,
                $idHtlBooking
            )) {
                $smartyVars['extraDemands'] = $extraDemands;
            }

            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
            if ($additionalServices = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                null,
                null,
                null,
                0,
                $idHtlBooking
            )) {
                $smartyVars['additionalServices'] = $additionalServices[$idHtlBooking];
            }

            $smartyVars['orderEdit'] = $orderEdit;

            $this->context->smarty->assign($smartyVars);

            $modal = array(
                'modal_id' => 'room-extra-demands',
                'modal_class' => 'modal-lg order_detail_modal',
                'modal_title' => '<i class="icon icon-file-text"></i> &nbsp'.$this->l('Extra Services'),
                'modal_content' => $this->context->smarty->fetch('controllers/orders/modals/_extra_services.tpl'),
            );

            $this->context->smarty->assign($modal);

            $response['modalHtml'] = $this->context->smarty->fetch('modal.tpl');
            $response['hasError'] = 0;
        } else {
            $response['hasError'] = 1;
        }

        $response = Tools::jsonEncode($response);
        die($response);
    }

    public function processRenderServicesPanel($idOrder, $idProduct, $dateFrom, $dateTo, $idRoom, $idHtlBookingDetail, $orderEdit)
    {
        $smartyVars = array();
        $objOrder = new Order($idOrder);
        $objCurrency = new Currency($objOrder->id_currency);
        $smartyVars['orderCurrency'] = $objOrder->id_currency;
        $smartyVars['currencySign'] = $objCurrency->sign;
        $smartyVars['link'] = $this->context->link;

        $smartyVars['id_booking_detail'] = $idHtlBookingDetail;
        $objServiceProductOrderDetail = new ServiceProductOrderDetail();
        if ($additionalServices = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            null,
            null,
            null,
            0,
            $idHtlBookingDetail
        )) {
            $smartyVars['additionalServices'] = $additionalServices[$idHtlBookingDetail];
        }

        if ($orderEdit) {
            // set context currency So that we can get prices in the order currency
            $this->context->currency = $objCurrency;

            $smartyVars['orderEdit'] = $orderEdit;
            if (Configuration::get('PS_ALLOW_ADD_ALL_SERVICES_IN_BOOKING')) {
                // get all services
                $objProduct = new Product();
                $hotelServiceProducts = $objProduct->getServiceProducts(true, Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE);
                $roomTypeServiceProducts = $objProduct->getServiceProducts(true, Product::SELLING_PREFERENCE_WITH_ROOM_TYPE);
                $serviceProducts = array_merge($roomTypeServiceProducts, $hotelServiceProducts);
            } else {
                $objRoomTypeServiceProduct = new RoomTypeServiceProduct();
                $serviceProducts = $objRoomTypeServiceProduct->getServiceProductsData($idProduct, 1, 0, false, 2, null);
            }

            if ($serviceProducts) {
                foreach ($serviceProducts as $key => $servProduct) {
                    $serviceProducts[$key]['price_tax_exc'] = $servProduct['price'];
                    if (isset($additionalServices[$idHtlBookingDetail]) && $additionalServices[$idHtlBookingDetail]) {
                        if (in_array($servProduct['id_product'], array_column($additionalServices[$idHtlBookingDetail]['additional_services'], 'id_product'))) {
                            unset($serviceProducts[$key]);
                        }
                    }
                }
            }

            $smartyVars['serviceProducts'] = $serviceProducts;
            $smartyVars['customServiceAllowed'] = Configuration::get('PS_ALLOW_CREATE_CUSTOM_SERVICES_IN_BOOKING');

            // check if booking room type tax rule is still exist
            $objHotelBookingDetail = new HotelBookingDetail($idHtlBookingDetail);
            $objOrderDetail = new OrderDetail($objHotelBookingDetail->id_order_detail);
            if (Validate::isLoadedObject($objTaxRuleGroup = new TaxRulesGroup($objOrderDetail->id_tax_rules_group))) {
                $smartyVars['roomTypeTaxRuleGroupExist'] = 1;
            } else {
                $smartyVars['roomTypeTaxRuleGroupExist'] = 0;
            }

            $taxRulesGroups = TaxRulesGroup::getTaxRulesGroups(true);
            $smartyVars['taxRulesGroups'] = $taxRulesGroups;
        }

        $smartyVars['show_active'] = true;
        $this->context->smarty->assign($smartyVars);

        $servicesTpl = $this->context->smarty->fetch(
            _PS_ADMIN_DIR_.'/themes/default/template/controllers/orders/modals/_extra_services_service_products_tab_content.tpl'
        );

        return $servicesTpl;
    }

    public function processRenderFacilitiesBlock($idOrder, $idProduct, $dateFrom, $dateTo, $idRoom, $idHtlBookingDetail, $orderEdit)
    {
        $smartyVars = array();
        $objOrder = new Order($idOrder);
        $objCurrency = new Currency($objOrder->id_currency);
        $smartyVars['orderCurrency'] = $objOrder->id_currency;
        $smartyVars['currencySign'] = $objCurrency->sign;
        $smartyVars['link'] = $this->context->link;
        $smartyVars['id_booking_detail'] = $idHtlBookingDetail;

        // set context currency So that we can get prices in the order currency
        $this->context->currency = new Currency($objOrder->id_currency);

        $objBookingDemand = new HotelBookingDemands();

        if ($extraDemands = $objBookingDemand->getRoomTypeBookingExtraDemands(
            $idOrder,
            $idProduct,
            $idRoom,
            $dateFrom,
            $dateTo,
            1,
            0,
            1,
            $idHtlBookingDetail
        )) {
            $smartyVars['extraDemands'] = $extraDemands;
        }

        // if admin is editing order
        if ($orderEdit) {
            $smartyVars['orderEdit'] = $orderEdit;

            // get room type additional demands
            $objRoomDemands = new HotelRoomTypeDemand();
            if ($roomTypeDemands = $objRoomDemands->getRoomTypeDemands($idProduct)) {
                foreach ($roomTypeDemands as &$demand) {
                    // if demand has advance options then set demand price as first advance option price.
                    if (isset($demand['adv_option']) && $demand['adv_option']) {
                        $demand['price'] = current($demand['adv_option'])['price'];
                    }
                }
                $smartyVars['roomTypeDemands'] = $roomTypeDemands;
            }
        }

        $smartyVars['show_active'] = true;
        $this->context->smarty->assign($smartyVars);

        $servicesTpl = $this->context->smarty->fetch(
            _PS_ADMIN_DIR_.'/themes/default/template/controllers/orders/modals/_extra_services_facilities_tab_content.tpl'
        );
        return $servicesTpl;
    }

    public function ajaxProcessUpdateSelectedRoomServices()
    {
        $response = array('hasError' => false);
        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            if ($selectedServicesOrderDetails = Tools::getValue('id_service_product_order_detail')) {
                $serviceQuantities = Tools::getValue('service_qty');
                $serviceUnitPrices = Tools::getValue('service_price');
                foreach ($selectedServicesOrderDetails as $idRoomTypeServiceProductOrderDetail) {
                    if (Validate::isLoadedObject($objServiceProductOrderDetail = new ServiceProductOrderDetail($idRoomTypeServiceProductOrderDetail))) {
                        $objOrderDetail = new OrderDetail($objServiceProductOrderDetail->id_order_detail);
                        if ($objOrderDetail->product_allow_multiple_quantity) {
                            if (!isset($serviceQuantities[$idRoomTypeServiceProductOrderDetail])) {
                                $response['hasError'] = true;
                                $response['errors'][] = Tools::displayError('Quantity not provided for service').': '.$objServiceProductOrderDetail->name;
                            } else if (!Validate::isUnsignedInt($serviceQuantities[$idRoomTypeServiceProductOrderDetail])) {
                                $response['hasError'] = true;
                                $response['errors'][] = Tools::displayError('Invalid quantity provided for service').': '.$objServiceProductOrderDetail->name;
                            }
                        } elseif ($serviceQuantities[$idRoomTypeServiceProductOrderDetail] > 1) {
                            $response['hasError'] = true;
                            $response['errors'][] = Tools::displayError('Can not order multiple quanitity for service').': '.$objServiceProductOrderDetail->name;
                        }

                        if (!isset($serviceUnitPrices[$idRoomTypeServiceProductOrderDetail])
                            || !ValidateCore::isPrice($serviceUnitPrices[$idRoomTypeServiceProductOrderDetail])
                        ) {
                            $response['hasError'] = true;
                            $response['errors'][] = Tools::displayError('Invalid unit price for service').': '.$objServiceProductOrderDetail->name;
                        }
                    } else {
                        $response['hasError'] = true;
                        $response['errors'][] = Tools::displayError('Service not found for service').': '.$objServiceProductOrderDetail->name;
                    }
                }

                if (!$response['hasError']) {
                    $result = true;
                    foreach ($selectedServicesOrderDetails as $idRoomTypeServiceProductOrderDetail) {
                        $objServiceProductOrderDetail = new ServiceProductOrderDetail($idRoomTypeServiceProductOrderDetail);
                        $quantity = $serviceQuantities[$idRoomTypeServiceProductOrderDetail];
                        $unitPrice = $serviceUnitPrices[$idRoomTypeServiceProductOrderDetail];

                        $objHotelBookingDetail = new HotelBookingDetail($objServiceProductOrderDetail->id_htl_booking_detail);
                        $objOrder = new Order($objServiceProductOrderDetail->id_order);
                        $objOrderDetail = new OrderDetail($objServiceProductOrderDetail->id_order_detail);

                        $oldUnitPriceTaxExcl = $objServiceProductOrderDetail->unit_price_tax_excl;
                        $oldUnitPriceTaxIncl = $objServiceProductOrderDetail->unit_price_tax_incl;

                        $oldTotalPriceTaxExcl = $objServiceProductOrderDetail->total_price_tax_excl;
                        $oldTotalPriceTaxIncl = $objServiceProductOrderDetail->total_price_tax_incl;

                        $oldQuantity = $objServiceProductOrderDetail->quantity;
                        if ($oldUnitPriceTaxExcl > 0) {
                            $oldTaxMultiplier = $oldUnitPriceTaxIncl / $oldUnitPriceTaxExcl;
                        } else {
                            $oldTaxMultiplier = 1;
                        }

                        if (!$objOrderDetail->product_allow_multiple_quantity || $quantity <= 0) {
                            $quantity = 1;
                        }

                        $objServiceProductOrderDetail->quantity = $quantity;
                        $objServiceProductOrderDetail->unit_price_tax_excl = $unitPrice;
                        $objServiceProductOrderDetail->unit_price_tax_incl = $unitPrice * $oldTaxMultiplier;
                        $numDays = 1;
                        if ($objOrderDetail->product_price_calculation_method == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                            $numDays = HotelHelper::getNumberOfDays(
                                $objHotelBookingDetail->date_from,
                                $objHotelBookingDetail->date_to
                            );
                        }
                        $objServiceProductOrderDetail->total_price_tax_excl = Tools::processPriceRounding(
                            ($objServiceProductOrderDetail->unit_price_tax_excl * $numDays),
                            $quantity,
                            $objOrder->round_type,
                            $objOrder->round_mode
                        );
                        $objServiceProductOrderDetail->total_price_tax_incl = Tools::processPriceRounding(
                            ($objServiceProductOrderDetail->unit_price_tax_incl * $numDays),
                            $quantity,
                            $objOrder->round_type,
                            $objOrder->round_mode
                        );

                        if ($result &= $objServiceProductOrderDetail->save()) {
                            $priceDiffTaxExcl = $objServiceProductOrderDetail->total_price_tax_excl - $oldTotalPriceTaxExcl;
                            $priceDiffTaxIncl = $objServiceProductOrderDetail->total_price_tax_incl - $oldTotalPriceTaxIncl;

                            if ($objOrderDetail->product_price_calculation_method == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                                $numDays = HotelHelper::getNumberOfDays(
                                    $objHotelBookingDetail->date_from,
                                    $objHotelBookingDetail->date_to
                                );
                                $quantityDiff = ($objServiceProductOrderDetail->quantity - $oldQuantity)*$numDays;
                            } else {
                                $quantityDiff = $objServiceProductOrderDetail->quantity - $oldQuantity;
                            }

                            $objOrderDetail->product_quantity += $quantityDiff;
                            $objOrderDetail->total_price_tax_excl += $priceDiffTaxExcl;
                            $objOrderDetail->total_price_tax_incl += $priceDiffTaxIncl;

                            $objOrderDetail->unit_price_tax_excl = ($objOrderDetail->total_price_tax_excl / $objOrderDetail->product_quantity);
                            $objOrderDetail->unit_price_tax_incl = ($objOrderDetail->total_price_tax_incl / $objOrderDetail->product_quantity);

                            $result &= $objOrderDetail->updateTaxAmount($objOrder);

                            $result &= $objOrderDetail->update();

                            if ($objOrderDetail->id_order_invoice != 0) {
                                // values changes as values are calculated accoding to the quantity of the product by webkul
                                $order_invoice = new OrderInvoice($objOrderDetail->id_order_invoice);
                                $order_invoice->total_paid_tax_excl = Tools::ps_round((float)($order_invoice->total_paid_tax_excl + $priceDiffTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                                $order_invoice->total_paid_tax_incl = Tools::ps_round((float)($order_invoice->total_paid_tax_incl + $priceDiffTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                                $result &= $order_invoice->update();
                            }

                            $objOrder->total_products = Tools::ps_round((float)($objOrder->total_products + $priceDiffTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                            $objOrder->total_products = $objOrder->total_products > 0 ? $objOrder->total_products : 0;

                            $objOrder->total_products_wt = Tools::ps_round((float)($objOrder->total_products_wt + $priceDiffTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                            $objOrder->total_products_wt = $objOrder->total_products_wt > 0 ? $objOrder->total_products_wt : 0;

                            $objOrder->total_paid = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                            $objOrder->total_paid_tax_incl = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                            $objOrder->total_paid_tax_excl = Tools::ps_round($objOrder->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                            $result &= $objOrder->update();
                        }
                    }

                    if (!$result) {
                        $response['hasError'] = true;
                        $response['errors'][] = Tools::displayError('Error while updating service, please try again after refresing the page');
                    } else {
                        $response['service_panel']= $servicesBlock = $this->processRenderServicesPanel(
                            $objOrderDetail->id_order,
                            $objHotelBookingDetail->id_product,
                            $objHotelBookingDetail->date_from,
                            $objHotelBookingDetail->date_to,
                            $objHotelBookingDetail->id_room,
                            $objHotelBookingDetail->id,
                            true
                        );
                    }
                }
            } else {
                $response['hasError'] = true;
                $response['errors'][] = Tools::displayError('Service not found');
            }
        } else {
            $response['hasError'] = true;
            $response['errors'][] = Tools::displayError('You do not have permission to edit this order.');
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessAddExistingRoomServices()
    {
        $response = array('hasError' => false);
        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            $idBookingDetail = Tools::getValue('id_booking_detail');
            if ($selectedServices = Tools::getValue('selected_service')) {
                // valiadate services being added
                if (Validate::isLoadedObject($objHotelBookingDetail = new HotelBookingDetail($idBookingDetail))) {
                    $objOrder = new Order($objHotelBookingDetail->id_order);
                    $objOrderDetail = new OrderDetail($objHotelBookingDetail->id_order_detail);
                    $objOrderInvoice = new OrderInvoice($objOrderDetail->id_order_invoice);

                    // set context currency So that we can get prices in the order currency
                    $this->context->currency = new Currency($objOrder->id_currency);

                    $objRoomTypeServiceProduct = new RoomTypeServiceProduct();
                    $qty = Tools::getValue('service_qty', []);
                    $price = Tools::getValue('service_price');
                    foreach ($selectedServices as $key => $service) {
                        $objProduct = new Product($service, false, $this->context->language->id);
                        if ($objProduct->allow_multiple_quantity) {
                            if (!Validate::isUnsignedInt($qty[$service])) {
                                $response['hasError'] = true;
                                $response['errors'][] = sprintf($this->l('The quantity you\'ve entered is invalid for %s.'), $objProduct->name);
                            }
                        } else {
                            $qty[$service] = 1;
                        }
                        if (!ValidateCore::isPrice($price[$service])) {
                            $response['hasError'] = true;
                            $response['errors'][] = sprintf($this->l('Invalid unit price for %s.'), $objProduct->name);
                        }
                        $selectedServices[$key] = array(
                            'id' => $service,
                            'qty' => $qty[$service],
                            'price' => $price[$service],
                            'name' => $objProduct->name,
                        );
                    }

                    $objHotelCartBookingData = new HotelCartBookingData();
                    $roomHtlCartInfo = $objHotelCartBookingData->getRoomRowByIdProductIdRoomInDateRange(
                        $objHotelBookingDetail->id_cart,
                        $objHotelBookingDetail->id_product,
                        $objHotelBookingDetail->date_from,
                        $objHotelBookingDetail->date_to,
                        $objHotelBookingDetail->id_room
                    );

                    // add services in room
                    $order = new Order($objHotelBookingDetail->id_order);
                    if (!$response['hasError']) {
                        $this->context->currency = new Currency($order->id_currency);
                        foreach ($selectedServices as $service) {
                            // Create new cart
                            $cart = new Cart();
                            $cart->id_shop_group = $order->id_shop_group;
                            $cart->id_shop = $order->id_shop;
                            $cart->id_customer = $order->id_customer;
                            $cart->id_carrier = $order->id_carrier;
                            $cart->id_address_delivery = $order->id_address_delivery;
                            $cart->id_address_invoice = $order->id_address_invoice;
                            $cart->id_currency = $order->id_currency;
                            $cart->id_lang = $order->id_lang;
                            $cart->secure_key = $order->secure_key;

                            // Save new cart
                            $cart->add();

                            $this->context->cart = $cart;
                            $this->context->customer = new Customer($order->id_customer);

                            $numDays = 1;
                            if (Product::getProductPriceCalculation($service['id']) == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                                $numDays = HotelHelper::getNumberOfDays($objHotelBookingDetail->date_from, $objHotelBookingDetail->date_to);
                            }

                            $objRoomTypeServiceProductPrice = new RoomTypeServiceProductPrice();
                            $initialServicePrice = Product::getServiceProductPrice(
                                (int)$service['id'],
                                0,
                                false,
                                $roomHtlCartInfo['id_product'],
                                false,
                                1,
                                $objHotelBookingDetail->date_from,
                                $objHotelBookingDetail->date_to
                            ) / $numDays;

                            if ($initialServicePrice != $service['price']) {
                                $specific_price = new SpecificPrice();
                                $specific_price->id_shop = 0;
                                $specific_price->id_shop_group = 0;
                                $specific_price->id_cart = $cart->id;
                                $specific_price->id_currency = $order->id_currency;
                                $specific_price->id_country = 0;
                                $specific_price->id_group = 0;
                                $specific_price->id_customer = $order->id_customer;
                                $specific_price->id_product = $service['id'];
                                $specific_price->id_product_attribute = 0;
                                $specific_price->id_htl_cart_booking = $roomHtlCartInfo['id'];
                                $specific_price->price = $service['price'];
                                $specific_price->from_quantity = 1;
                                $specific_price->reduction = 0;
                                $specific_price->reduction_type = 'amount';
                                $specific_price->reduction_tax = 0;
                                $specific_price->from = '0000-00-00 00:00:00';
                                $specific_price->to = '0000-00-00 00:00:00';
                                $specific_price->add();
                            }

                            $objServiceProductCartDetail = new ServiceProductCartDetail();
                            $objServiceProductCartDetail->addServiceProductInCart(
                                $cart->id,
                                $service['id'],
                                $service['qty'],
                                false,
                                $roomHtlCartInfo['id']
                            );
                            $productList = $cart->getProducts();
                            $objHotelRoomType = new HotelRoomType();
                            $roomInfo = $objHotelRoomType->getRoomTypeInfoByIdProduct($objHotelBookingDetail->id_product);
                            $totalPriceChangeTaxExcl = 0;
                            $totalPriceChangeTaxIncl = 0;
                            $unitPriceTaxIncl = 0;
                            $unitPriceTaxExcl = 0;
                            foreach ($productList as &$product) {
                                // This is used to get the actual quanity of the service as it is calculated incorrectly if the service is per night
                                if ($id_service_product_cart_detail = $objServiceProductCartDetail->alreadyExists(
                                    $cart->id,
                                    $service['id'],
                                    $roomHtlCartInfo['id'])
                                ) {
                                    $objServiceProductCartDetail = new ServiceProductCartDetail((int) $id_service_product_cart_detail);
                                    $unitPriceTaxExcl = Product::getServiceProductPrice(
                                        (int) $product['id_product'],
                                        0,
                                        false,
                                        $roomHtlCartInfo['id_product'],
                                        false,
                                        1,
                                        $objHotelBookingDetail->date_from,
                                        $objHotelBookingDetail->date_to,
                                        $cart->id,
                                        null,
                                        1,
                                        null,
                                        $roomHtlCartInfo['id']
                                    ) / $numDays;
                                    $unitPriceTaxIncl = Product::getServiceProductPrice(
                                        (int) $product['id_product'],
                                        0,
                                        false,
                                        $roomHtlCartInfo['id_product'],
                                        true,
                                        1,
                                        $objHotelBookingDetail->date_from,
                                        $objHotelBookingDetail->date_to,
                                        $cart->id,
                                        null,
                                        1,
                                        null,
                                        $roomHtlCartInfo['id']
                                    ) / $numDays;

                                    $totalPriceChangeTaxExcl += $totalPriceTaxExcl = Tools::processPriceRounding(
                                        ($unitPriceTaxExcl * $numDays),
                                        $objServiceProductCartDetail->quantity,
                                        $objOrder->round_type,
                                        $objOrder->round_mode
                                    );
                                    $totalPriceChangeTaxIncl += $totalPriceTaxIncl = Tools::processPriceRounding(
                                        ($unitPriceTaxIncl * $numDays),
                                        $objServiceProductCartDetail->quantity,
                                        $objOrder->round_type,
                                        $objOrder->round_mode
                                    );

                                    $product['total'] = $totalPriceTaxExcl;
                                    $product['total_wt'] = $totalPriceTaxIncl;

                                    if ($product['selling_preference_type'] == Product::SELLING_PREFERENCE_HOTEL_STANDALONE_AND_WITH_ROOM_TYPE) {
                                        $product['selling_preference_type'] = Product::SELLING_PREFERENCE_WITH_ROOM_TYPE;
                                    }
                                }
                            }

                            $order_detail = new OrderDetail();
                            $order_detail->createList($order, $cart, $order->getCurrentOrderState(), $productList, (isset($objOrderInvoice) ? $objOrderInvoice->id : 0), true);

                            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
                            $objServiceProductOrderDetail->id_product = $service['id'];
                            $objServiceProductOrderDetail->id_order = $objHotelBookingDetail->id_order;
                            $objServiceProductOrderDetail->id_order_detail = $order_detail->id;
                            $objServiceProductOrderDetail->id_cart = $cart->id;
                            $objServiceProductOrderDetail->id_htl_booking_detail = $objHotelBookingDetail->id;
                            $objServiceProductOrderDetail->unit_price_tax_excl = $unitPriceTaxExcl;
                            $objServiceProductOrderDetail->unit_price_tax_incl = $unitPriceTaxIncl;
                            $objServiceProductOrderDetail->total_price_tax_excl = $totalPriceTaxExcl;
                            $objServiceProductOrderDetail->total_price_tax_incl = $totalPriceTaxIncl;
                            $objServiceProductOrderDetail->name = $service['name'];
                            $objServiceProductOrderDetail->quantity = $objServiceProductCartDetail->quantity;
                            $objServiceProductOrderDetail->save();

                            // update totals amount of order
                            $order->total_products = Tools::ps_round((float)($order->total_products + $totalPriceChangeTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                            $order->total_products_wt = Tools::ps_round((float)($order->total_products_wt + $totalPriceChangeTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);

                            $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                            $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                            $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                            // update invoice total
                            if (isset($objOrderInvoice) && Validate::isLoadedObject($objOrderInvoice)) {
                                $objOrderInvoice->total_paid_tax_excl += Tools::ps_round((float)$totalPriceChangeTaxExcl, _PS_PRICE_COMPUTE_PRECISION_);
                                $objOrderInvoice->total_paid_tax_incl += Tools::ps_round((float)$totalPriceChangeTaxIncl, _PS_PRICE_COMPUTE_PRECISION_);
                                $objOrderInvoice->save();
                            }

                            // discount
                            $order->total_discounts += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
                            $order->total_discounts_tax_excl += (float)abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
                            $order->total_discounts_tax_incl += (float)abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));

                            // Save changes of order
                            $order->update();
                            if (isset($specific_price) && Validate::isLoadedObject($specific_price)) {
                                $specific_price->delete();
                                unset($specific_price);
                            }
                        }
                    }
                    $response['service_panel'] = $this->processRenderServicesPanel(
                        $order->id,
                        $objHotelBookingDetail->id_product,
                        $objHotelBookingDetail->date_from,
                        $objHotelBookingDetail->date_to,
                        $objHotelBookingDetail->id_room,
                        $objHotelBookingDetail->id,
                        true
                    );
                } else {
                    $response['hasError'] = true;
                    $response['errors'][] = Tools::displayError('Room not found');
                }
            }
        } else {
            $response['hasError'] = true;
            $response['errors'][] = Tools::displayError('You do not have permission to edit this order.');
        }

        $this->ajaxDie(json_encode($response));
    }

    // Add new custom services to room
    public function ajaxProcessAddNewRoomServices()
    {
        $response = array('hasError' => false);
        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            if (Configuration::get('PS_ALLOW_CREATE_CUSTOM_SERVICES_IN_BOOKING')) {
                $idBookingDetail = Tools::getValue('id_booking_detail');
                // valiadate services being added
                if (Validate::isLoadedObject($objHotelBookingDetail = new HotelBookingDetail($idBookingDetail))) {
                    $objHotelCartBookingData = new HotelCartBookingData();
                    if ($roomHtlCartInfo = $objHotelCartBookingData->getRoomRowByIdProductIdRoomInDateRange(
                        $objHotelBookingDetail->id_cart,
                        $objHotelBookingDetail->id_product,
                        $objHotelBookingDetail->date_from,
                        $objHotelBookingDetail->date_to,
                        $objHotelBookingDetail->id_room
                    )) {
                        $name = trim(Tools::getValue('new_service_name'));
                        $price = Tools::getValue('new_service_price');
                        $priceCalcMethod = Tools::getValue('new_service_price_calc_method');
                        $priceAdditionType = Tools::getValue('new_service_price_addition_type');
                        $productQty = Tools::getValue('new_service_qty');
                        $idTaxRuleGroup = Tools::getValue('new_service_price_tax_rule_group');
                        $autoAdded = Tools::getValue('new_service_auto_added');

                        // adjust values for auto added services
                        $objOrderDetail = new OrderDetail($objHotelBookingDetail->id_order_detail);
                        if ($autoAdded) {
                            $productQty = 1;
                            // check if booking room type tax rule is still exist
                            if (Validate::isLoadedObject($objTaxRuleGroup = new TaxRulesGroup($objOrderDetail->id_tax_rules_group))) {
                                $idTaxRuleGroup = $objOrderDetail->id_tax_rules_group;
                            }
                        } else {
                            $priceAdditionType = 0;
                            if (empty($productQty)) {
                                $response['hasError'] = true;
                                $response['errors'][] = Tools::displayError('Service quantity is required');
                            } elseif (!Validate::isUnsignedInt($productQty)) {
                                $response['hasError'] = true;
                                $response['errors'][] = Tools::displayError('Invalid service quantity');
                            }
                        }

                        // Validate service data provided
                        if (empty($name)) {
                            $response['hasError'] = true;
                            $response['errors'][] = Tools::displayError('Service name is required');
                        } elseif (!Validate::isCatalogName($name)) {
                            $response['hasError'] = true;
                            $response['errors'][] = Tools::displayError('Invalid service name');
                        }

                        if (!isset($price)) {
                            $response['hasError'] = true;
                            $response['errors'][] = Tools::displayError('Service price is required');
                        } elseif (!Validate::isPrice($price)) {
                            $response['hasError'] = true;
                            $response['errors'][] = Tools::displayError('Invalid service price');
                        }

                        // if no validation errors then add service
                        if (!$response['hasError']) {
                            // ======= START: Create Service product  =========
                            $objServiceProduct = new Product();
                            $objServiceProduct->price_calculation_method = $priceCalcMethod;
                            $objServiceProduct->selling_preference_type = Product::SELLING_PREFERENCE_WITH_ROOM_TYPE;
                            $objServiceProduct->id_category_default = Configuration::get('PS_SERVICE_CATEGORY');
                            $objServiceProduct->name[Configuration::get('PS_LANG_DEFAULT')] = $name;
                            $objServiceProduct->id_shop_default = Configuration::get('PS_SHOP_DEFAULT');
                            $objServiceProduct->allow_multiple_quantity = !$autoAdded;
                            $objServiceProduct->id_tax_rules_group = $idTaxRuleGroup;
                            $objServiceProduct->price = $price;
                            $objServiceProduct->wholesale_price = 0;
                            $languages = Language::getLanguages(false);
                            foreach ($languages as $lang) {
                                $objServiceProduct->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($name);
                            }
                            $objServiceProduct->available_for_order = true;
                            $objServiceProduct->show_price = true;
                            $objServiceProduct->is_virtual = true;
                            $objServiceProduct->auto_add_to_cart = $autoAdded;
                            $objServiceProduct->price_addition_type = $priceAdditionType;
                            $objServiceProduct->booking_product = false;
                            $objServiceProduct->show_at_front = false;
                            $objServiceProduct->indexed = false;
                            $objServiceProduct->minimal_quantity = 1;
                            $objServiceProduct->active = true;

                            // ======= Create Service product END =========
                            if ($objServiceProduct->save()) {
                                // If service product is create successfully the start adding the product in cart and order
                                $objOrder = new Order($objHotelBookingDetail->id_order);
                                $objServiceProduct = new Product($objServiceProduct->id, false, $objOrder->id_lang);

                                // Create new cart
                                $objCart = new Cart();
                                $objCart->id_shop_group = $objOrder->id_shop_group;
                                $objCart->id_shop = $objOrder->id_shop;
                                $objCart->id_customer = $objOrder->id_customer;
                                $objCart->id_carrier = $objOrder->id_carrier;
                                $objCart->id_address_delivery = $objOrder->id_address_delivery;
                                $objCart->id_address_invoice = $objOrder->id_address_invoice;
                                $objCart->id_currency = $objOrder->id_currency;
                                $objCart->id_lang = $objOrder->id_lang;
                                $objCart->secure_key = $objOrder->secure_key;
                                if ($objCart->add()) {
                                    $this->context->cart = $objCart;
                                    $this->context->customer = new Customer($objOrder->id_customer);
                                    // set context currency So that we can get prices in the order currency
                                    $this->context->currency = new Currency($objOrder->id_currency);

                                    $objServiceProductCartDetail = new ServiceProductCartDetail();
                                    if ($objServiceProductCartDetail->addServiceProductInCart(
                                        $objCart->id,
                                        $objServiceProduct->id,
                                        $productQty,
                                        0,
                                        $roomHtlCartInfo['id']
                                    )) {
                                        if ($productList = $objCart->getProducts()) {
                                            $totalPriceChangeTaxExcl = 0;
                                            $totalPriceChangeTaxIncl = 0;
                                            $unitPriceTaxIncl = 0;
                                            $unitPriceTaxExcl = 0;

                                            $objRoomTypeServiceProductPrice = new RoomTypeServiceProductPrice();
                                            $cartProductProcessed = 1;
                                            $numDays = 1;
                                            if (Product::getProductPriceCalculation($objServiceProduct->id) == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                                                $numDays = HotelHelper::getNumberOfDays($objHotelBookingDetail->date_from, $objHotelBookingDetail->date_to);
                                            }
                                            foreach ($productList as &$product) {
                                                // This is used to get the actual quanity of the service as it is calculated incorrectly if the service is per night
                                                if ($idRoomTypeServProductCart = $objServiceProductCartDetail->alreadyExists(
                                                    $objCart->id,
                                                    $product['id_product'],
                                                    $roomHtlCartInfo['id'])
                                                ) {
                                                    // Lets create a specific price for the service to match the price provided by the user
                                                    $objSpecificPrice = new SpecificPrice();
                                                    $objSpecificPrice->id_shop = 0;
                                                    $objSpecificPrice->id_shop_group = 0;
                                                    $objSpecificPrice->id_cart = $objCart->id;
                                                    $objSpecificPrice->id_currency = $objOrder->id_currency;
                                                    $objSpecificPrice->id_country = 0;
                                                    $objSpecificPrice->id_group = 0;
                                                    $objSpecificPrice->id_customer = $objOrder->id_customer;
                                                    $objSpecificPrice->id_product = $objServiceProduct->id;
                                                    $objSpecificPrice->id_product_attribute = 0;
                                                    $objSpecificPrice->price = $price;
                                                    $objSpecificPrice->from_quantity = 1;
                                                    $objSpecificPrice->reduction = 0;
                                                    $objSpecificPrice->reduction_type = 'amount';
                                                    $objSpecificPrice->reduction_tax = 0;
                                                    $objSpecificPrice->from = '0000-00-00 00:00:00';
                                                    $objSpecificPrice->to = '0000-00-00 00:00:00';
                                                    if ($objSpecificPrice->add()) {
                                                        if (Validate::isLoadedObject($objServiceProductCartDetail = new ServiceProductCartDetail((int) $idRoomTypeServProductCart))) {
                                                            $unitPriceTaxExcl = Product::getServiceProductPrice(
                                                                (int)$product['id_product'],
                                                                0,
                                                                $roomHtlCartInfo['id_hotel'],
                                                                $roomHtlCartInfo['id_product'],
                                                                false,
                                                                1,
                                                                $objHotelBookingDetail->date_from,
                                                                $objHotelBookingDetail->date_to,
                                                                $objCart->id
                                                            ) / $numDays;
                                                            $unitPriceTaxIncl = Product::getServiceProductPrice(
                                                                (int)$product['id_product'],
                                                                0,
                                                                $roomHtlCartInfo['id_hotel'],
                                                                $roomHtlCartInfo['id_product'],
                                                                true,
                                                                1,
                                                                $objHotelBookingDetail->date_from,
                                                                $objHotelBookingDetail->date_to,
                                                                $objCart->id
                                                            ) / $numDays;

                                                            $totalPriceChangeTaxExcl += $totalPriceTaxExcl = Tools::processPriceRounding(
                                                                ($unitPriceTaxExcl * $numDays),
                                                                $objServiceProductCartDetail->quantity,
                                                                $objOrder->round_type,
                                                                $objOrder->round_mode
                                                            );
                                                            $totalPriceChangeTaxIncl += $totalPriceTaxIncl = Tools::processPriceRounding(
                                                                ($unitPriceTaxIncl * $numDays),
                                                                $objServiceProductCartDetail->quantity,
                                                                $objOrder->round_type,
                                                                $objOrder->round_mode
                                                            );

                                                            $product['total'] = $totalPriceTaxExcl;
                                                            $product['total_wt'] = $totalPriceTaxIncl;

                                                            $cartProductProcessed = 1;
                                                        } else {
                                                            $cartProductProcessed = 0;
                                                            break;
                                                        }
                                                    } else {
                                                        $cartProductProcessed = 0;
                                                        break;
                                                    }
                                                } else {
                                                    $cartProductProcessed = 0;
                                                    break;
                                                }
                                            }

                                            // If all cart products processed then only we will proceed further
                                            if ($cartProductProcessed) {
                                                $objOrderInvoice = new OrderInvoice($objOrderDetail->id_order_invoice);

                                                // add in order detail table
                                                $orderDetail = new OrderDetail();
                                                $orderDetail->createList($objOrder, $objCart, $objOrder->getCurrentOrderState(), $productList, (isset($objOrderInvoice) ? $objOrderInvoice->id : 0), true);

                                                // Add in room type service product order detail
                                                $objServiceProductOrderDetail = new ServiceProductOrderDetail();
                                                $objServiceProductOrderDetail->id_product = $objServiceProduct->id;
                                                $objServiceProductOrderDetail->id_order = $objHotelBookingDetail->id_order;
                                                $objServiceProductOrderDetail->id_order_detail = $orderDetail->id;
                                                $objServiceProductOrderDetail->id_cart = $objCart->id;
                                                $objServiceProductOrderDetail->id_htl_booking_detail = $objHotelBookingDetail->id;
                                                $objServiceProductOrderDetail->unit_price_tax_excl = $unitPriceTaxExcl;
                                                $objServiceProductOrderDetail->unit_price_tax_incl = $unitPriceTaxIncl;
                                                $objServiceProductOrderDetail->total_price_tax_excl = $totalPriceTaxExcl;
                                                $objServiceProductOrderDetail->total_price_tax_incl = $totalPriceTaxIncl;
                                                $objServiceProductOrderDetail->name = $name;
                                                $objServiceProductOrderDetail->quantity = $productQty;
                                                $objServiceProductOrderDetail->auto_added = $autoAdded;
                                                $objServiceProductOrderDetail->save();

                                                // update totals amount of order
                                                $objOrder->total_products = Tools::ps_round((float)($objOrder->total_products + $totalPriceChangeTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                                                $objOrder->total_products_wt = Tools::ps_round((float)($objOrder->total_products_wt + $totalPriceChangeTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);

                                                $objOrder->total_paid = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                                                $objOrder->total_paid_tax_incl = Tools::ps_round($objOrder->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                                                $objOrder->total_paid_tax_excl = Tools::ps_round($objOrder->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                                                // update invoice total
                                                if (isset($objOrderInvoice) && Validate::isLoadedObject($objOrderInvoice)) {
                                                    $objOrderInvoice->total_paid_tax_excl += Tools::ps_round((float)$totalPriceChangeTaxExcl, _PS_PRICE_COMPUTE_PRECISION_);
                                                    $objOrderInvoice->total_paid_tax_incl += Tools::ps_round((float)$totalPriceChangeTaxIncl, _PS_PRICE_COMPUTE_PRECISION_);
                                                    $objOrderInvoice->save();
                                                }

                                                // discounts calculation for order
                                                $objOrder->total_discounts += (float)abs($objCart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
                                                $objOrder->total_discounts_tax_excl += (float)abs($objCart->getOrderTotal(false, Cart::ONLY_DISCOUNTS));
                                                $objOrder->total_discounts_tax_incl += (float)abs($objCart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));

                                                // Save changes of order
                                                if ($objOrder->update()) {
                                                    $response['service_panel'] = $this->processRenderServicesPanel(
                                                        $objOrder->id,
                                                        $objHotelBookingDetail->id_product,
                                                        $objHotelBookingDetail->date_from,
                                                        $objHotelBookingDetail->date_to,
                                                        $objHotelBookingDetail->id_room,
                                                        $objHotelBookingDetail->id,
                                                        true
                                                    );
                                                } else {
                                                    $response['hasError'] = true;
                                                    $response['errors'][] = Tools::displayError('Some error occurred while updating the order');
                                                }

                                                // at last delete the specific price created for the service
                                                if (Validate::isLoadedObject($objSpecificPrice)) {
                                                    $objSpecificPrice->delete();
                                                    unset($objSpecificPrice);
                                                }
                                            } else {
                                                $response['hasError'] = true;
                                                $response['errors'][] = Tools::displayError('Some error occurred while updating the order');
                                            }
                                        } else {
                                            $response['hasError'] = true;
                                            $response['errors'][] = Tools::displayError('Unable to add service to the room');
                                        }
                                    } else {
                                        $response['hasError'] = true;
                                        $response['errors'][] = Tools::displayError('Unable to add service to the room');
                                    }
                                } else {
                                    $response['hasError'] = true;
                                    $response['errors'][] = Tools::displayError('Unable to add service to the room');
                                }
                            } else {
                                $response['hasError'] = true;
                                $response['errors'][] = Tools::displayError('Unable to add service to the room');
                            }
                            // at last delete the created service
                            $objServiceProduct->delete();
                        }
                    } else {
                        $response['hasError'] = true;
                        $response['errors'][] = Tools::displayError('Unable to add service to the room');
                    }
                } else {
                    $response['hasError'] = true;
                    $response['errors'][] = Tools::displayError('Room not found');
                }
            } else {
                $response['hasError'] = true;
                $response['errors'][] = Tools::displayError('Adding a new custom service is not allowed.');
            }
        } else {
            $response['hasError'] = true;
            $response['errors'][] = Tools::displayError('You do not have permission to edit this order.');
        }

        $this->ajaxDie(json_encode($response));
    }


    public function ajaxProcessDeleteRoomAdditionalService()
    {
        $response = array('hasError' => false);
        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            $idServiceProductOrderDetail = Tools::getValue('id_service_product_order_detail');
            if (Validate::isLoadedObject($objServiceProductOrderDetail = new ServiceProductOrderDetail($idServiceProductOrderDetail))) {
                $objOrderDetail = new OrderDetail($objServiceProductOrderDetail->id_order_detail);
                $priceTaxExcl = $objServiceProductOrderDetail->total_price_tax_excl;
                $priceTaxIncl = $objServiceProductOrderDetail->total_price_tax_incl;
                $quantity = $objServiceProductOrderDetail->quantity;
                $res = true;
                $objHotelBookingDetail = new HotelBookingDetail($objServiceProductOrderDetail->id_htl_booking_detail);
                $objHotelCartBookingData = new HotelCartBookingData();
                $objServiceProductCartDetail = new ServiceProductCartDetail();

                if ($roomHtlCartInfo = $objHotelCartBookingData->getRoomRowByIdProductIdRoomInDateRange(
                    $objHotelBookingDetail->id_cart,
                    $objHotelBookingDetail->id_product,
                    $objHotelBookingDetail->date_from,
                    $objHotelBookingDetail->date_to,
                    $objHotelBookingDetail->id_room
                )) {
                    if ($idServiceProductCartDetail = $objServiceProductCartDetail->alreadyExists(
                        $objServiceProductOrderDetail->id_cart,
                        $objServiceProductOrderDetail->id_product,
                        $roomHtlCartInfo['id']
                    )) {
                        $objServiceProductCartDetail = new ServiceProductCartDetail($idServiceProductCartDetail);
                        if ($objServiceProductCartDetail->delete()
                            // Since the service product is deleted after adding them directly from the order the cart will throw exception on the service delete process.
                            && Validate::isLoadedObject(new Product($objServiceProductOrderDetail->id_product))
                        ) {
                            $objCart = new Cart($objServiceProductOrderDetail->id_cart);
                            $objCart->updateQty((int) abs($quantity), $objServiceProductOrderDetail->id_product, null, 0, 'down', 0, null, 0);
                        }
                    }
                }

                if ($res &= $objServiceProductOrderDetail->delete()) {
                    $order = new Order($objServiceProductOrderDetail->id_order);
                    if ($objOrderDetail->product_price_calculation_method == Product::PRICE_CALCULATION_METHOD_PER_DAY) {
                        $numDays = HotelHelper::getNumberOfDays(
                            $objHotelBookingDetail->date_from,
                            $objHotelBookingDetail->date_to
                        );
                        $quantity = $quantity * $numDays;
                    }
                    if ($quantity >= $objOrderDetail->product_quantity) {
                        $objOrderDetail->delete();
                    } else {
                        $objOrderDetail->product_quantity -= $quantity;

                        $objOrderDetail->total_price_tax_excl -= Tools::processPriceRounding(
                            $priceTaxExcl,
                            1,
                            $order->round_type,
                            $order->round_mode
                        );
                        $objOrderDetail->total_price_tax_incl -= Tools::processPriceRounding(
                            $priceTaxIncl,
                            1,
                            $order->round_type,
                            $order->round_mode
                        );

                        $res &= $objOrderDetail->updateTaxAmount($order);

                        $res &= $objOrderDetail->update();
                    }

                    if ($objOrderDetail->id_order_invoice != 0) {
                        // values changes as values are calculated
                        $objOrderInvoice = new OrderInvoice($objOrderDetail->id_order_invoice);
                        $objOrderInvoice->total_paid_tax_excl = Tools::ps_round((float)($objOrderInvoice->total_paid_tax_excl - $priceTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                        $objOrderInvoice->total_paid_tax_incl = Tools::ps_round((float)($objOrderInvoice->total_paid_tax_incl - $priceTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                        $res &= $objOrderInvoice->update();
                    }

                    $order->total_products = Tools::ps_round((float)($order->total_products - $priceTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_products = $order->total_products > 0 ? $order->total_products : 0;

                    $order->total_products_wt = Tools::ps_round((float)($order->total_products_wt - $priceTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_products_wt = $order->total_products_wt > 0 ? $order->total_products_wt : 0;

                    $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                    $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                    $res &= $order->update();
                }
                if ($res) {
                    $response['service_panel']= $servicesBlock = $this->processRenderServicesPanel(
                        $objOrderDetail->id_order,
                        $objHotelBookingDetail->id_product,
                        $objHotelBookingDetail->date_from,
                        $objHotelBookingDetail->date_to,
                        $objHotelBookingDetail->id_room,
                        $objHotelBookingDetail->id,
                        true
                    );
                }
            } else {
                $response['hasError'] = true;
                $response['errors'] = $this->l('Additional service not found');
            }
        } else {
            $response['hasError'] = true;
            $response['errors'] = $this->l('You do not have permission to edit this order.');
        }

        $this->ajaxDie(json_encode($response));
    }

    // Process when admin changes extra demands of any room while order creation process form.tpl
    public function ajaxProcessChangeRoomDemands()
    {
        if ($idCartBooking = Tools::getValue('id_cart_booking')) {
            if (Validate::isLoadedObject($objCartbookingCata = new HotelCartBookingData($idCartBooking))) {
                $roomDemands = Tools::getValue('room_demands');
                $roomDemands = json_decode($roomDemands, true);
                $roomDemands = json_encode($roomDemands);
                $objCartbookingCata->extra_demands = $roomDemands;
                if ($objCartbookingCata->save()) {
                    die('1');
                }
            }
        }
        die('0');
    }

    // Process when admin edit rooms and edit rooms additional facilities
    public function ajaxProcessAddRoomExtraDemands()
    {
        $response = array('success' => false, 'hasError' => false);
        if ($this->tabAccess['edit'] === 1) {
            if ($idHtlBooking = Tools::getValue('id_htl_booking')) {
                if (Validate::isLoadedObject($objBookingDetail = new HotelBookingDetail($idHtlBooking))) {
                    $roomDemands = Tools::getValue('room_demands');
                    if ($roomDemands = json_decode($roomDemands, true)) {
                        $order = new Order($objBookingDetail->id_order);
                        // set context currency So that we can get prices in the order currency
                        $this->context->currency = new Currency($order->id_currency);

                        $vatAddress = new Address((int)$order->id_address_tax);
                        $idLang = (int)$order->id_lang;
                        $idProduct = $objBookingDetail->id_product;
                        $objHtlBkDtl = new HotelBookingDetail();
                        $objRoomDemandPrice = new HotelRoomTypeDemandPrice();
                        foreach ($roomDemands as $demand) {
                            if (!ValidateCore::isPrice($demand['unit_price'])) {
                                $objGlobalDemand = new HotelRoomTypeGlobalDemand($demand['id_global_demand'], $idLang);
                                $response['hasError'] = true;
                                $response['errors'][] = sprintf($this->l('Invalid unit price for %s.'), $objGlobalDemand->name);
                            }
                        }
                        if (!$response['hasError']) {
                            foreach ($roomDemands as $demand) {
                                $idGlobalDemand = $demand['id_global_demand'];
                                $idOption = $demand['id_option'];
                                $objBookingDemand = new HotelBookingDemands();
                                $objBookingDemand->id_htl_booking = $idHtlBooking;
                                $objGlobalDemand = new HotelRoomTypeGlobalDemand($idGlobalDemand, $idLang);
                                if ($idOption) {
                                    $objOption = new HotelRoomTypeGlobalDemandAdvanceOption($idOption, $idLang);
                                    $objBookingDemand->name = $objOption->name;
                                } else {
                                    $idOption = 0;
                                    $objBookingDemand->name = $objGlobalDemand->name;
                                }
                                $objBookingDemand->unit_price_tax_excl = $demand['unit_price'];
                                $objBookingDemand->unit_price_tax_incl = HotelRoomTypeDemand::getPriceStatic(
                                    $idProduct,
                                    $idGlobalDemand,
                                    $idOption,
                                    1,
                                    6,
                                    null,
                                    null,
                                    null,
                                    null,
                                    $demand['unit_price']
                                );
                                $numDays = 1;
                                if ($objGlobalDemand->price_calc_method == HotelRoomTypeGlobalDemand::WK_PRICE_CALC_METHOD_EACH_DAY) {
                                    $numDays = HotelHelper::getNumberOfDays(
                                        $objBookingDetail->date_from,
                                        $objBookingDetail->date_to
                                    );
                                }
                                $objBookingDemand->total_price_tax_excl = $totalPriceTaxIncl = Tools::processPriceRounding(
                                    ($objBookingDemand->unit_price_tax_excl * $numDays),
                                    1,
                                    $order->round_type,
                                    $order->round_mode
                                );
                                $objBookingDemand->total_price_tax_incl = $totalPriceTaxIncl = Tools::processPriceRounding(
                                    ($objBookingDemand->unit_price_tax_incl * $numDays),
                                    1,
                                    $order->round_type,
                                    $order->round_mode
                                );

                                $order_detail = new OrderDetail($objBookingDetail->id_order_detail);
                                // Update OrderInvoice of this OrderDetail
                                if ($order_detail->id_order_invoice != 0) {
                                    // values changes as values are calculated accoding to the quantity of the product by webkul
                                    $order_invoice = new OrderInvoice($order_detail->id_order_invoice);
                                    $order_invoice->total_paid_tax_excl = Tools::ps_round((float)($order_invoice->total_paid_tax_excl + $objBookingDemand->total_price_tax_excl), _PS_PRICE_COMPUTE_PRECISION_);
                                    $order_invoice->total_paid_tax_incl = Tools::ps_round((float)($order_invoice->total_paid_tax_incl + $objBookingDemand->total_price_tax_incl), _PS_PRICE_COMPUTE_PRECISION_);
                                    $res &= $order_invoice->update();
                                }

                                $objBookingDemand->price_calc_method = $objGlobalDemand->price_calc_method;
                                $objBookingDemand->id_tax_rules_group = $objGlobalDemand->id_tax_rules_group;
                                $taxManager = TaxManagerFactory::getManager(
                                    $vatAddress,
                                    $objGlobalDemand->id_tax_rules_group
                                );
                                $taxCalc = $taxManager->getTaxCalculator();
                                $objBookingDemand->tax_computation_method = (int)$taxCalc->computation_method;
                                if ($objBookingDemand->save()) {
                                    $objBookingDemand->tax_calculator = $taxCalc;
                                    // Now save tax details of the extra demand
                                    $objBookingDemand->setBookingDemandTaxDetails();
                                }
                            }

                            $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                            $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                            $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);
                            if ($order->save()) {
                                $response['facilities_panel'] = $this->processRenderFacilitiesBlock(
                                    $order->id,
                                    $objBookingDetail->id_product,
                                    $objBookingDetail->date_from,
                                    $objBookingDetail->date_to,
                                    $objBookingDetail->id_room,
                                    $objBookingDetail->id,
                                    true
                                );
                                $response['success'] = true;
                            }
                        }
                    }
                }
            }
        } else {
            $response['hasError'] = true;
            $response['errors'][] = Tools::displayError('You do not have permission to edit this order.');
        }
        die(json_encode($response));
    }

    public function ajaxProcessUpdateRoomExtraDemands()
    {
        $response = array('success' => false);
        if ($this->tabAccess['edit'] === 1) {
            if ($idBookingDemand = Tools::getValue('id_booking_demand')) {
                if (Validate::isLoadedObject($objBookingDemand = new HotelBookingDemands($idBookingDemand))) {
                    $unitPrice = Tools::getValue('unit_price');
                    if (!$unitPrice || !ValidateCore::isPrice($unitPrice)) {
                        $response['hasError'] = true;
                        $response['errors'] = $this->l('Invalid unit price');
                    }
                    $oldPriceTaxExcl = $objBookingDemand->unit_price_tax_excl;
                    $oldPriceTaxIncl = $objBookingDemand->unit_price_tax_incl;
                    if ($oldPriceTaxExcl > 0) {
                        $oldTaxMultiplier = $oldPriceTaxIncl / $oldPriceTaxExcl;
                    } else {
                        $oldTaxMultiplier = 1;
                    }

                    $objBookingDemand->unit_price_tax_excl = $unitPrice;
                    $objBookingDemand->unit_price_tax_incl = $unitPrice * $oldTaxMultiplier;

                    $objBookingDetail = new HotelBookingDetail($objBookingDemand->id_htl_booking);
                    $order = new Order($objBookingDetail->id_order);
                    if ($objBookingDemand->price_calc_method == HotelRoomTypeGlobalDemand::WK_PRICE_CALC_METHOD_EACH_DAY) {
                        $numDays = HotelHelper::getNumberOfDays(
                            $objBookingDetail->date_from,
                            $objBookingDetail->date_to
                        );

                        $objBookingDemand->total_price_tax_excl = Tools::processPriceRounding(
                            ($objBookingDemand->unit_price_tax_excl * $numDays),
                            1,
                            $order->round_type,
                            $order->round_mode
                        );
                        $objBookingDemand->total_price_tax_excl = Tools::processPriceRounding(
                            ($objBookingDemand->unit_price_tax_incl * $numDays),
                            1,
                            $order->round_type,
                            $order->round_mode
                        );
                    } else {
                        $objBookingDemand->total_price_tax_excl = Tools::processPriceRounding(
                            $objBookingDemand->unit_price_tax_excl,
                            1,
                            $order->round_type,
                            $order->round_mode
                        );
                        $objBookingDemand->total_price_tax_incl = Tools::processPriceRounding(
                            $objBookingDemand->unit_price_tax_incl,
                            1,
                            $order->round_type,
                            $order->round_mode
                        );
                    }
                    if ($objBookingDemand->save()) {
                        $objBookingDemand->setBookingDemandTaxDetails(1);

                        $priceDiffTaxExcl = $objBookingDemand->total_price_tax_excl - $oldPriceTaxExcl;
                        $priceDiffTaxIncl = $objBookingDemand->total_price_tax_incl - $oldPriceTaxIncl;


                        $order_detail = new OrderDetail($objBookingDetail->id_order_detail);
                        // Update OrderInvoice of this OrderDetail
                        if ($order_detail->id_order_invoice != 0) {
                            // values changes as values are calculated accoding to the quantity of the product by webkul
                            $order_invoice = new OrderInvoice($order_detail->id_order_invoice);

                            $order_invoice->total_paid_tax_excl = Tools::ps_round((float)($order_invoice->total_paid_tax_excl + $priceDiffTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                            $order_invoice->total_paid_tax_incl = Tools::ps_round((float)($order_invoice->total_paid_tax_incl + $priceDiffTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                            $order_invoice->update();
                        }

                        $order->total_paid_tax_excl = Tools::ps_round((float)($order->total_paid_tax_excl + $priceDiffTaxExcl), _PS_PRICE_COMPUTE_PRECISION_);
                        $order->total_paid_tax_incl = Tools::ps_round((float)($order->total_paid_tax_incl + $priceDiffTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                        $order->total_paid = Tools::ps_round((float)($order->total_paid + $priceDiffTaxIncl), _PS_PRICE_COMPUTE_PRECISION_);
                        if ($order->save()) {
                            $response['facilities_panel'] = $this->processRenderFacilitiesBlock(
                                $order->id,
                                $objBookingDetail->id_product,
                                $objBookingDetail->date_from,
                                $objBookingDetail->date_to,
                                $objBookingDetail->id_room,
                                $objBookingDetail->id,
                                true
                            );
                            $response['success'] = true;
                        }
                    }
                }
            }
        }
        die(json_encode($response));
    }

    // delete room extra demand while order edit
    public function ajaxProcessDeleteRoomExtraDemand()
    {
        $response = array('success' => false);
        // Check tab access is allowed to edit
        if ($this->tabAccess['edit'] === 1) {
            $res = true;
            if ($idBookingDemand = Tools::getValue('id_booking_demand')) {
                if (Validate::isLoadedObject($objBookingDemand = new HotelBookingDemands($idBookingDemand))) {
                    // first delete the tax details of the booking demand
                    if ($objBookingDemand->deleteBookingDemandTaxDetails($idBookingDemand)) {
                        if ($objBookingDemand->delete()) {
                            if (Validate::isLoadedObject($objBookingDetail = new HotelBookingDetail($objBookingDemand->id_htl_booking))) {
                                // change order total
                                $order = new Order($objBookingDetail->id_order);

                                $order->total_paid = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                                $order->total_paid_tax_incl = Tools::ps_round($order->getOrderTotal(), _PS_PRICE_COMPUTE_PRECISION_);
                                $order->total_paid_tax_excl = Tools::ps_round($order->getOrderTotal(false), _PS_PRICE_COMPUTE_PRECISION_);

                                $order->save();

                                $order_detail = new OrderDetail($objBookingDetail->id_order_detail);
                                // Update OrderInvoice of this OrderDetail
                                if ($order_detail->id_order_invoice != 0) {
                                    // values changes as values are calculated accoding to the quantity of the product by webkul
                                    $order_invoice = new OrderInvoice($order_detail->id_order_invoice);
                                    $order_invoice->total_paid_tax_excl = Tools::ps_round((float)($order_invoice->total_paid_tax_excl - $objBookingDemand->total_price_tax_excl), _PS_PRICE_COMPUTE_PRECISION_);
                                    $order_invoice->total_paid_tax_incl = Tools::ps_round((float)($order_invoice->total_paid_tax_incl - $objBookingDemand->total_price_tax_incl), _PS_PRICE_COMPUTE_PRECISION_);
                                    $res &= $order_invoice->update();
                                }
                                if ($res) {
                                    $response['facilities_panel'] = $this->processRenderFacilitiesBlock(
                                        $order->id,
                                        $objBookingDetail->id_product,
                                        $objBookingDetail->date_from,
                                        $objBookingDetail->date_to,
                                        $objBookingDetail->id_room,
                                        $objBookingDetail->id,
                                        true
                                    );
                                    $response['success'] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        die(json_encode($response));
    }

    public function ajaxProcessUpdateServiceProduct()
    {
        $operator = Tools::getValue('operator', 'up');
        $idServiceProduct = Tools::getValue('id_product');
        $idCartBooking = Tools::getValue('id_cart_booking');
        $qty = Tools::getValue('qty');

        if (Validate::isLoadedObject($objHotelCartBookingData = new HotelCartBookingData($idCartBooking))) {
            $objServiceProductCartDetail = new ServiceProductCartDetail();

            if ($operator == 'up') {
                $objRoomTypeServiceProduct = new RoomTypeServiceProduct();
                if ($objRoomTypeServiceProduct->isRoomTypeLinkedWithProduct($objHotelCartBookingData->id_product, $idServiceProduct)) {
                    // validate quanitity
                    if (Validate::isLoadedObject($objProduct = new Product($idServiceProduct))) {
                        if ($objProduct->allow_multiple_quantity) {
                            if (!Validate::isUnsignedInt($qty)) {
                                $this->errors[] = Tools::displayError('The quantity code you\'ve entered is invalid.');
                            }
                        } else {
                            $qty = 1;
                        }
                    } else {
                        $this->errors[] = Tools::displayError('This Service is not available.');
                    }
                } else {
                    $this->errors[] = Tools::displayError('This Service is not available with selected room.');
                }
            }

            if (empty($this->errors)) {
                if ($objServiceProductCartDetail->updateCartServiceProduct(
                    $objHotelCartBookingData->id_cart,
                    $idServiceProduct,
                    $operator,
                    $qty,
                    false,
                    $idCartBooking

                )) {
                    $this->ajaxDie(json_encode(array(
                        'hasError' => false
                    )));
                } else {
                    $this->errors[] = Tools::displayError('Unable to update services. Please try reloading the page.');
                }
            }
        } else {
            $this->errors[] = Tools::displayError('Room not found. Please try reloading the page.');
        }
        $this->ajaxDie(json_encode(array(
            'hasError' => true,
            'errors' => $this->errors
        )));
    }

    // To change the status of the room
    public function changeRoomStatus()
    {
        $idHotelBookingDetail = (int) Tools::getValue('id_hotel_booking_detail');
        if (Validate::isLoadedObject($objHotelBookingDetail = new HotelBookingDetail($idHotelBookingDetail))) {
            $newStatus = (int) Tools::getValue('booking_order_status');
            // date choosen for the status change
            if ($statusDate = Tools::getValue('status_date')) {
                $statusDate = date('Y-m-d H:i:s', strtotime($statusDate));
            }

            $dateFrom = date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($objHotelBookingDetail->date_from))));
            $dateTo = date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($objHotelBookingDetail->date_to)).' 23:59:59'));
            if (!$newStatus) {
                $this->errors[] = Tools::displayError('Invalid booking status found.');
            } elseif ($newStatus == HotelBookingDetail::STATUS_CHECKED_IN
                || $newStatus == HotelBookingDetail::STATUS_CHECKED_OUT
            ) {
                if (!$statusDate || !Validate::isDate($statusDate)) {
                    $this->errors[] = Tools::displayError('Invalid dates found.');
                } elseif ((strtotime($statusDate) < strtotime($dateFrom))
                    || (strtotime($statusDate) > strtotime($dateTo))
                ) {
                    $this->errors[] = Tools::displayError('Date should be between booking from date and to date.');
                }
            }

            if ($objHotelBookingDetail->id_status == HotelBookingDetail::STATUS_CHECKED_OUT
                && (date('Y-m-d', strtotime($objHotelBookingDetail->check_out)) != date('Y-m-d', strtotime($objHotelBookingDetail->date_to)))
                && ($booking = $objHotelBookingDetail->chechRoomBooked($objHotelBookingDetail->id_room, $objHotelBookingDetail->check_out, $objHotelBookingDetail->date_to))
            ) {
                if ($booking['id_status'] != HotelBookingDetail::STATUS_CHECKED_OUT
                    || date('Y-m-d', strtotime($booking['check_out'])) > date('Y-m-d', strtotime($objHotelBookingDetail->date_from))
                ) {
                    $this->errors[] = Tools::displayError('You cannot update this room\'s booking status as a new booking has been created for this room. Please change the room to update the status.');
                }
            }
            if ($newStatus == HotelBookingDetail::STATUS_CHECKED_OUT) {
                if ($objHotelBookingDetail->check_in ==  '0000-00-00 00:00:00') {
                    $this->errors[] = Tools::displayError('Room status must be set to Check-In before setting the room status to Check-Out.');
                } elseif (strtotime($objHotelBookingDetail->check_in) > strtotime($statusDate)) {
                    $this->errors[] = sprintf(
                        Tools::displayError('Check-Out date can not be before Check-In date (%s)'),
                        date('d-m-Y', strtotime($objHotelBookingDetail->check_in))
                    );
                }
            }

            if (empty($this->errors)) {
                $objHotelBookingDetail->id_status = $newStatus;
                if ($newStatus == HotelBookingDetail::STATUS_CHECKED_IN) {
                    $objHotelBookingDetail->check_in = $statusDate;
                } elseif ($newStatus == HotelBookingDetail::STATUS_CHECKED_OUT) {
                    $objHotelBookingDetail->check_out = $statusDate;
                } else {
                    $objHotelBookingDetail->check_in = '';
                    $objHotelBookingDetail->check_out = '';
                }
                if ($objHotelBookingDetail->save()) {
                    Hook::exec(
                        'actionRoomBookingStatusUpdateAfter',
                        array(
                            'id_hotel_booking_detail' => $objHotelBookingDetail->id,
                            'id_order' => $objHotelBookingDetail->id_order,
                            'id_room' => $objHotelBookingDetail->id_room,
                            'date_from' => $objHotelBookingDetail->date_from,
                            'date_to' => $objHotelBookingDetail->date_to
                        )
                    );

                    Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int) $objHotelBookingDetail->id_order.'&vieworder&token='.$this->token.'&conf=4');
                } else {
                    $this->errors[] = Tools::displayError('Some error occurred. Please try again.');
                }
            }
        } else {
            $this->errors[] = Tools::displayError('Invalid booking information. Please try again.');
        }
    }

    // ajax request to get room type upgrade/degrade changes information to reallocate
    public function ajaxProcessChangeRoomTypeToReallocate()
    {
        $result = array();
        $result['success'] = 0;
        $result['has_price_changes'] = 0;
        $result['has_room_type_change'] = 0;

        $idHotelBooking = Tools::getValue('id_htl_booking');
        $idNewRoomType = Tools::getValue('id_new_room_type');

        // if room type id is present in the ajax request or not
        if ($idNewRoomType) {
            // validate the booking is valid or not
            if (Validate::isLoadedObject($objHotelBooking = new HotelBookingDetail($idHotelBooking))) {
                $result['success'] = 1;
                // if room is changing in the reallocation
                if ($objHotelBooking->id_product != $idNewRoomType) {
                    $result['has_room_type_change'] = 1;
                    // set context currency So that we can get prices in the order currency
                    $objOrder = new Order($objHotelBooking->id_order);
                    $this->context->currency = new Currency($objOrder->id_currency);
                    $occupancy = array(
                        array(
                            'adults' => $objHotelBooking->adults,
                            'children' => $objHotelBooking->children,
                            'child_ages' => json_decode($objHotelBooking->child_ages)
                        )
                    );
                    $newRoomTotalPrice = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice(
                        $idNewRoomType,
                        $objHotelBooking->date_from,
                        $objHotelBooking->date_to,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    );
                    if ($objHotelBooking->total_price_tax_excl != $newRoomTotalPrice['total_price_tax_excl']) {
                        $result['has_price_changes'] = 1;
                        $result['price_diff'] = Tools::ps_round((float) ($newRoomTotalPrice['total_price_tax_excl'] - $objHotelBooking->total_price_tax_excl), _PS_PRICE_COMPUTE_PRECISION_);
                    }
                }
            } else {
                $result['error'] = $this->l('Invalid booking information. Please try again.');
            }
        } else {
            $result['error'] = $this->l('Selected room type not found. Please try again.');
        }

        $this->ajaxDie(json_encode($result));
    }
}

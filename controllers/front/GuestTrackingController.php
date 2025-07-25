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

class GuestTrackingControllerCore extends FrontController
{
    public $ssl = true;
    public $php_self = 'guest-tracking';

    /**
     * Initialize guest tracking controller
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();
        if ($this->context->customer->isLogged()) {
            Tools::redirect('history.php');
        }
    }

    /**
     * Start forms process
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitGuestTracking') || Tools::isSubmit('submitTransformGuestToCustomer')) {
            // These lines are here for retrocompatibility with old theme
            $idOrder = Tools::getValue('id_order');
            $order_collection = array();
            if ($idOrder) {
                if (is_numeric($idOrder)) {
                    $order = new Order((int)$idOrder);
                    if (Validate::isLoadedObject($order)) {
                        $order_collection = Order::getByReference($order->reference);
                    }
                } else {
                    $order_collection = Order::getByReference($idOrder);
                }
            }

            // Get order reference, ignore package reference (after the #, on the order reference)
            $order_reference = current(explode('#', Tools::getValue('order_reference')));
            // Ignore $result_number
            if (!empty($order_reference)) {
                $order_collection = Order::getByReference($order_reference);
            }

            $email = Tools::getValue('email');

            if (empty($order_reference) && empty($idOrder)) {
                $this->errors[] = Tools::displayError('Please provide your order\'s reference number.');
            } elseif (empty($email)) {
                $this->errors[] = Tools::displayError('Please provide a valid email address.');
            } elseif (!Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Please provide a valid email address.');
            } elseif (!Customer::customerExists($email, false, false)) {
                $this->errors[] = Tools::displayError('There is no account associated with this email address.');
            } elseif (Customer::customerExists($email, false, true)) {
                $this->errors[] = Tools::displayError('This page is for guest accounts only. Since your guest account has already been transformed into a customer account, you can no longer view your order here. Please log in to your customer account to view this order');
                $this->context->smarty->assign('show_login_link', true);
            } elseif (!count($order_collection)) {
                $this->errors[] = Tools::displayError('Invalid order reference');
            } elseif (!$order_collection->getFirst()->isAssociatedAtGuest($email)) {
                $this->errors[] = Tools::displayError('Invalid order reference');
            } else {
                $this->assignOrderTracking($order_collection);
                if (Tools::isSubmit('submitTransformGuestToCustomer')) {
                    $customer = new Customer((int)$order->id_customer);
                    if (!Validate::isLoadedObject($customer)) {
                        $this->errors[] = Tools::displayError('Invalid customer');
                    } elseif (!Tools::getValue('password')
                        || !Validate::isPasswd(Tools::getValue('password')
                    )) {
                        $this->errors[] = Tools::displayError('Invalid password.');
                    } elseif (!$customer->transformToCustomer($this->context->language->id, Tools::getValue('password'))) {
                        $this->errors[] = Tools::displayError('An error occurred while transforming a guest into a registered customer.');
                    } else {
                        $this->context->smarty->assign('transformSuccess', true);
                    }
                }
            }
        }
    }

    public function displayAjaxGetRoomTypeBookingDemands()
    {
        $response = array('extra_demands' => false);

        if (($idProduct = Tools::getValue('id_product'))
            && ($idOrder = Tools::getValue('id_order'))
            && ($dateFrom = Tools::getValue('date_from'))
            && ($dateTo = Tools::getValue('date_to'))
        ) {
            $objHotelBookingDemands = new HotelBookingDemands();
            $useTax = 0;
            if (Group::getPriceDisplayMethod($this->context->customer->id_default_group) == PS_TAX_INC) {
                $useTax = 1;
            }
            if ($extraDemands = $objHotelBookingDemands->getRoomTypeBookingExtraDemands(
                $idOrder,
                $idProduct,
                0,
                $dateFrom,
                $dateTo,
                1,
                0,
                $useTax
            )) {
                $this->context->smarty->assign(array(
                    'useTax' => $useTax,
                    'extraDemands' => $extraDemands,
                ));
            }
            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
            if ($additionalServices = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                $idOrder,
                0,
                0,
                $idProduct,
                $dateFrom,
                $dateTo,
                0,
                0,
                $useTax,
                0
            )) {
                $this->context->smarty->assign(array(
                    'useTax' => $useTax,
                    'additionalServices' => $additionalServices,
                ));
            }

            $this->context->smarty->assign(array(
                'objOrder' => new Order($idOrder),
            ));

            $response['extra_demands'] = $this->context->smarty->fetch(_PS_THEME_DIR_.'_partials/order-extra-services.tpl');
        }

        $this->ajaxDie(json_encode($response));
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        /* Handle brute force attacks */
        if (count($this->errors)) {
            sleep(1);
        }

        $totalRoomsBooked = 0;
        if ($orderReference = Tools::getValue('id_order')) {
            // Get rooms bookings in the order
            $objHotelBooking = new HotelBookingDetail();
            if ($cartRoomBookings = $objHotelBooking->getBookingDataByOrderReference($orderReference)) {
                $totalRoomsBooked = count($cartRoomBookings);
            }
        }
        $this->context->smarty->assign(array(
            'total_rooms_booked' => $totalRoomsBooked,
            'action' => $this->context->link->getPageLink('guest-tracking.php', true),
            'errors' => $this->errors,
        ));
        $this->setTemplate(_PS_THEME_DIR_.'guest-tracking.tpl');
    }

    /**
     * Assigns template vars related to order tracking information
     *
     * @param PrestaShopCollection $order_collection
     *
     * @throws PrestaShopException
     */
    protected function assignOrderTracking($order_collection)
    {
        $customer = new Customer((int)$order_collection->getFirst()->id_customer);

        $order_collection = ($order_collection->getAll());

        $order_list = array();
        foreach ($order_collection as $order) {
            $order_list[] = $order;
        }

        //by webkul to show order details properly on order history page
        if ($hotelresInstalled = Module::isInstalled('hotelreservationsystem')) {
            include_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';
            $objHtlBranchInfo = new HotelBranchInformation();
            $objBookingDetail = new HotelBookingDetail();
            $objServiceProductOrderDetail = new ServiceProductOrderDetail();
            $objRoomType = new HotelRoomType();

            $anyBackOrder = 0;

            foreach ($order_list as &$order) {
                $idOrder = $order->id;

                $processedProducts = array();
                $cartHotelData = array();

                $order->id_order_state = (int)$order->getCurrentState();
                $order->invoice = (OrderState::invoiceAvailable((int)$order->id_order_state) && $order->invoice_number);
                $order->order_history = $order->getHistory((int)$this->context->language->id, false, true);
                $order->overbooking_order_states = OrderState::getOverBookingStates();
                $order->carrier = new Carrier((int)$order->id_carrier, (int)$order->id_lang);
                $order->address_invoice = new Address((int)$order->id_address_invoice);
                $order->address_delivery = new Address((int)$order->id_address_delivery);
                $order->inv_adr_fields = AddressFormat::getOrderedAddressFields($order->address_invoice->id_country);
                $order->dlv_adr_fields = AddressFormat::getOrderedAddressFields($order->address_delivery->id_country);
                $order->invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($order->address_invoice, $order->inv_adr_fields);
                $order->deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($order->address_delivery, $order->dlv_adr_fields);
                $order->currency = new Currency($order->id_currency);
                $order->discounts = $order->getCartRules();
                $order->invoiceState = (Validate::isLoadedObject($order->address_invoice) && $order->address_invoice->id_state) ? new State((int)$order->address_invoice->id_state) : false;
                $order->deliveryState = (Validate::isLoadedObject($order->address_delivery) && $order->address_delivery->id_state) ? new State((int)$order->address_delivery->id_state) : false;
                $order->products = $order->getProducts();
                $order->customizedDatas = Product::getAllCustomizedDatas((int)$order->id_cart);
                Product::addCustomizationPrice($order->products, $order->customizedDatas);
                $order->total_old = $order->total_discounts > 0 ? (float)$order->total_paid - (float)$order->total_discounts : false;

                if ($order->carrier->url && $order->shipping_number) {
                    $order->followup = str_replace('@', $order->shipping_number, $order->carrier->url);
                }
                $order->hook_orderdetaildisplayed = Hook::exec('displayOrderDetail', array('order' => $order));

                // enter the details of the booking the order
                $standaloneServiceProducts = array();
                $hotelServiceProducts = array();
                if ($hotelresInstalled) {
                    if ($orderProducts = $order->getProducts()) {
                        $total_demands_price_te = 0;
                        $total_demands_price_ti = 0;
                        $total_convenience_fee_te = 0;
                        $total_convenience_fee_ti = 0;

                        $objOrderReturn = new OrderReturn();
                        $refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($order->id, 0, 1);

                        foreach ($orderProducts as $type_key => $type_value) {
                            if (in_array($type_value['product_id'], $processedProducts)) {
                                continue;
                            }

                            $product = new Product($type_value['product_id'], false, $this->context->language->id);
                            if ($type_value['is_booking_product']) {
                                $processedProducts[] = $type_value['product_id'];

                                $cover_image_arr = $product->getCover($type_value['product_id']);

                                if (!empty($cover_image_arr)) {
                                    $cover_img = $this->context->link->getImageLink($product->link_rewrite, $product->id.'-'.$cover_image_arr['id_image'], 'small_default');
                                } else {
                                    $cover_img = $this->context->link->getImageLink($product->link_rewrite, $this->context->language->iso_code.'-default', 'small_default');
                                }

                                if (isset($customer->id)) {
                                    $obj_cart = new Cart($order->id_cart);
                                    $order_bk_data = $objBookingDetail->getOnlyOrderBookingData($order->id, $obj_cart->id_guest, $type_value['product_id'], $customer->id);
                                } else {
                                    $order_bk_data = $objBookingDetail->getOnlyOrderBookingData($order->id, $customer->id_guest, $type_value['product_id']);
                                }
                                $cartHotelData[$type_key]['id_product'] = $type_value['product_id'];
                                $cartHotelData[$type_key]['cover_img'] = $cover_img;


                                $objBookingDemand = new HotelBookingDemands();
                                foreach ($order_bk_data as $data_k => $data_v) {
                                    $date_join = strtotime($data_v['date_from']).strtotime($data_v['date_to']);

                                    $cartHotelData[$type_key]['adults'] = $data_v['adults'];
                                    $cartHotelData[$type_key]['children'] = $data_v['children'];
                                    /*Product price when order was created*/
                                    $order_details_obj = new OrderDetail($data_v['id_order_detail']);
                                    $cartHotelData[$type_key]['name'] = $order_details_obj->product_name;
                                    $cartHotelData[$type_key]['paid_unit_price_tax_excl'] = ($order_details_obj->total_price_tax_excl)/$order_details_obj->product_quantity;
                                    $cartHotelData[$type_key]['paid_unit_price_tax_incl'] = ($order_details_obj->total_price_tax_incl)/$order_details_obj->product_quantity;

                                    if (isset($cartHotelData[$type_key]['date_diff'][$date_join])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['num_rm'] += 1;

                                        $num_days = $cartHotelData[$type_key]['date_diff'][$date_join]['num_days'];
                                        $var_quant = (int) $cartHotelData[$type_key]['date_diff'][$date_join]['num_rm'];

                                        $cartHotelData[$type_key]['date_diff'][$date_join]['adults'] += $data_v['adults'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['children'] += $data_v['children'];

                                        $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_excl'] = $data_v['total_price_tax_excl']/$num_days;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_incl'] = $data_v['total_price_tax_incl']/$num_days;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['avg_paid_unit_price_tax_excl'] += $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_excl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['avg_paid_unit_price_tax_incl'] += $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_incl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['amount_tax_incl'] += $data_v['total_price_tax_incl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['amount_tax_excl'] += $data_v['total_price_tax_excl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['is_backorder'] = $data_v['is_back_order'];
                                        if ($data_v['is_back_order']) {
                                            $anyBackOrder = 1;
                                        }

                                        if ($refundReqBookings && in_array($data_v['id'], $refundReqBookings) && $data_v['is_refunded']) {
                                            if ($data_v['is_cancelled']) {
                                                $cartHotelData[$type_key]['date_diff'][$date_join]['count_cancelled'] += 1;
                                            } else {
                                                $cartHotelData[$type_key]['date_diff'][$date_join]['count_refunded'] += 1;
                                            }
                                        }
                                    } else {
                                        $num_days = HotelHelper::getNumberOfDays($data_v['date_from'], $data_v['date_to']);
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['num_rm'] = 1;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['data_form'] = $data_v['date_from'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['data_to'] = $data_v['date_to'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['num_days'] = $num_days;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['adults'] = $data_v['adults'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['children'] = $data_v['children'];

                                        $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_excl'] = $data_v['total_price_tax_excl']/$num_days;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_incl'] = $data_v['total_price_tax_incl']/$num_days;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['avg_paid_unit_price_tax_excl'] = $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_excl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['avg_paid_unit_price_tax_incl'] = $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_incl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['amount_tax_incl'] = $data_v['total_price_tax_incl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['amount_tax_excl'] = $data_v['total_price_tax_excl'];
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['is_backorder'] = $data_v['is_back_order'];
                                        if ($data_v['is_back_order']) {
                                            $anyBackOrder = 1;
                                        }

                                        $cartHotelData[$type_key]['date_diff'][$date_join]['count_cancelled'] = 0;
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['count_refunded'] = 0;
                                        if ($refundReqBookings && in_array($data_v['id'], $refundReqBookings) && $data_v['is_refunded']) {
                                            if ($data_v['is_cancelled']) {
                                                $cartHotelData[$type_key]['date_diff'][$date_join]['count_cancelled'] += 1;
                                            } else {
                                                $cartHotelData[$type_key]['date_diff'][$date_join]['count_refunded'] += 1;
                                            }
                                        }
                                    }

                                    $cartHotelData[$type_key]['date_diff'][$date_join]['is_refunded'] = $data_v['is_refunded'];

                                    $cartHotelData[$type_key]['date_diff'][$date_join]['ids_htl_booking_detail'][] = $data_v['id'];

                                    $cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands'] = $objBookingDemand->getRoomTypeBookingExtraDemands(
                                        $idOrder,
                                        $type_value['product_id'],
                                        0,
                                        $data_v['date_from'],
                                        $data_v['date_to']
                                    );
                                    if (empty($cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands_price_ti'])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands_price_ti'] = 0;
                                    }
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands_price_ti'] += $extraDemandPriceTI = $objBookingDemand->getRoomTypeBookingExtraDemands(
                                        $idOrder,
                                        $type_value['product_id'],
                                        $data_v['id_room'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        0,
                                        1,
                                        1
                                    );
                                    if (empty($cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands_price_te'])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands_price_te'] = 0;
                                    }
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['extra_demands_price_te'] += $extraDemandPriceTE = $objBookingDemand->getRoomTypeBookingExtraDemands(
                                        $idOrder,
                                        $type_value['product_id'],
                                        $data_v['id_room'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        0,
                                        1,
                                        0
                                    );
                                    $total_demands_price_ti += $extraDemandPriceTI;
                                    $total_demands_price_te += $extraDemandPriceTE;
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['product_price_tax_excl'] = $order_details_obj->unit_price_tax_excl;
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['product_price_tax_incl'] = $order_details_obj->unit_price_tax_incl;
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['product_price_without_reduction_tax_excl'] = $order_details_obj->unit_price_tax_excl + $order_details_obj->reduction_amount_tax_excl;
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['product_price_without_reduction_tax_incl'] = $order_details_obj->unit_price_tax_incl + $order_details_obj->reduction_amount_tax_incl;

                                    $feature_price_diff = (float)($cartHotelData[$type_key]['date_diff'][$date_join]['product_price_without_reduction_tax_incl'] - $cartHotelData[$type_key]['date_diff'][$date_join]['paid_unit_price_tax_incl']);
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['feature_price_diff'] = $feature_price_diff;

                                    $cartHotelData[$type_key]['hotel_name'] = $data_v['hotel_name'];
                                    // add additional services products in hotel detail.
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services'] = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        0,
                                        0,
                                        $type_value['product_id'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        0,
                                        0,
                                        null,
                                        0
                                    );

                                    if (empty($cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_ti'])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_ti'] = 0;
                                    }
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_ti'] += $additionalServicesPriceTI = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        0,
                                        0,
                                        $type_value['product_id'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        $data_v['id_room'],
                                        1,
                                        1,
                                        0
                                    );
                                    if (empty($cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_te'])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_te'] = 0;
                                    }
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_te'] += $additionalServicesPriceTE = $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        0,
                                        0,
                                        $type_value['product_id'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        $data_v['id_room'],
                                        1,
                                        0,
                                        0
                                    );
                                    // get auto added price to be displayed with room price
                                    if (empty($cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_auto_add_ti'])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_auto_add_ti'] = 0;
                                    }
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_auto_add_ti'] += $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        0,
                                        0,
                                        $type_value['product_id'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        $data_v['id_room'],
                                        1,
                                        1,
                                        1,
                                        Product::PRICE_ADDITION_TYPE_WITH_ROOM
                                    );
                                    if (empty($cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_auto_add_te'])) {
                                        $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_auto_add_te'] = 0;
                                    }
                                    $cartHotelData[$type_key]['date_diff'][$date_join]['additional_services_price_auto_add_te'] += $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        0,
                                        0,
                                        $type_value['product_id'],
                                        $data_v['date_from'],
                                        $data_v['date_to'],
                                        $data_v['id_room'],
                                        1,
                                        0,
                                        1,
                                        Product::PRICE_ADDITION_TYPE_WITH_ROOM
                                    );
                                }

                                // calculate averages now
                                foreach ($cartHotelData[$type_key]['date_diff'] as $key => &$value) {
                                    $value['avg_paid_unit_price_tax_excl'] = Tools::ps_round($value['avg_paid_unit_price_tax_excl'] / $value['num_rm'], 6);
                                    $value['avg_paid_unit_price_tax_incl'] = Tools::ps_round($value['avg_paid_unit_price_tax_incl'] / $value['num_rm'], 6);

                                    $value['avg_price_diff_tax_excl'] = abs(Tools::ps_round($value['avg_paid_unit_price_tax_excl'] - $value['product_price_tax_excl'], 6));
                                    $value['avg_price_diff_tax_incl'] = abs(Tools::ps_round($value['avg_paid_unit_price_tax_incl'] - $value['product_price_tax_incl'], 6));
                                }
                            } elseif ($type_value['selling_preference_type'] == Product::SELLING_PREFERENCE_WITH_ROOM_TYPE) {
                                if ($type_value['product_auto_add'] && $type_value['product_price_addition_type'] == Product::PRICE_ADDITION_TYPE_INDEPENDENT) {
                                    $total_convenience_fee_ti += $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        $type_value['product_id'],
                                        0,
                                        0,
                                        0,
                                        0,
                                        0,
                                        1,
                                        1,
                                        1
                                    );
                                    $total_convenience_fee_te += $objServiceProductOrderDetail->getRoomTypeServiceProducts(
                                        $idOrder,
                                        $type_value['product_id'],
                                        0,
                                        0,
                                        0,
                                        0,
                                        0,
                                        1,
                                        0,
                                        1
                                    );
                                }
                            } else if ($type_value['selling_preference_type'] == Product::SELLING_PREFERENCE_HOTEL_STANDALONE) {
                                $cover_image_arr = $product->getCover($type_value['product_id']);

                                if (!empty($cover_image_arr)) {
                                    $type_value['cover_img'] = $this->context->link->getImageLink($product->link_rewrite, $product->id.'-'.$cover_image_arr['id_image'], 'small_default');
                                } else {
                                    $type_value['cover_img'] = $this->context->link->getImageLink($product->link_rewrite, $this->context->language->iso_code.'-default', 'small_default');
                                }
                                $hotelProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($idOrder, $type_value['id_order_detail'], $type_value['product_id']);
                                foreach ($hotelProducts as $hotelProduct) {
                                    $hotelServiceProducts[] = array_merge($type_value, $hotelProduct);
                                }
                            } else if ($type_value['selling_preference_type'] == Product::SELLING_PREFERENCE_STANDALONE) {
                                $cover_image_arr = $product->getCover($type_value['product_id']);

                                if (!empty($cover_image_arr)) {
                                    $type_value['cover_img'] = $this->context->link->getImageLink($product->link_rewrite, $product->id.'-'.$cover_image_arr['id_image'], 'small_default');
                                } else {
                                    $type_value['cover_img'] = $this->context->link->getImageLink($product->link_rewrite, $this->context->language->iso_code.'-default', 'small_default');
                                }
                                $standaloneProducts = $objServiceProductOrderDetail->getServiceProductsInOrder($idOrder, $type_value['id_order_detail'], $type_value['product_id']);
                                foreach ($standaloneProducts as $standaloneProduct) {
                                    $standaloneServiceProducts[] = array_merge($type_value, $standaloneProduct);
                                }
                            }
                        }
                    }
                }
                // end booking details entries
                $redirectTermsLink = $this->context->link->getCMSLink(
                    new CMS(3, $this->context->language->id),
                    null, $this->context->language->id
                );

                $customerGuestDetail = false;
                if ($id_customer_guest_detail = OrderCustomerGuestDetail::isCustomerGuestBooking($order->id)) {
                    $customerGuestDetail = new OrderCustomerGuestDetail($id_customer_guest_detail);
                }

                $objHotelBranchInformation = null;
                if ($idHotel = HotelBookingDetail::getIdHotelByIdOrder($order->id)) {
                    $objHotelBranchInformation = new HotelBranchInformation($idHotel, $this->context->language->id);
                }
                $hotelAddressInfo = HotelBranchInformation::getAddress($idHotel);

                $objHotelBranchRefundRules = new HotelBranchRefundRules();
                $hotelRefundRules = $objHotelBranchRefundRules->getHotelRefundRules($idHotel, 0, 1);

                // set order specific values
                $order->total_convenience_fee_ti = $total_convenience_fee_ti;
                $order->total_convenience_fee_te = $total_convenience_fee_te;
                $order->total_demands_price_ti = $total_demands_price_ti;
                $order->total_demands_price_te = $total_demands_price_te;
                $order->any_back_order = $anyBackOrder;
                $order->shw_bo_msg = Configuration::get('WK_SHOW_MSG_ON_BO');
                $order->back_ord_msg = Configuration::get('WK_BO_MESSAGE');
                $order->order_has_invoice = $order->hasInvoice();
                $order->cart_htl_data = $cartHotelData;
                $order->hotel_service_products = $hotelServiceProducts;
                $order->standalone_service_products = $standaloneServiceProducts;
                $order->customerGuestDetail = $customerGuestDetail;
                $order->obj_hotel_branch_information = $objHotelBranchInformation;
                $order->hotel_address_info = $hotelAddressInfo;
                $order->hotel_refund_rules = $hotelRefundRules;
                //end

                Hook::exec('actionOrderDetail', array('carrier' => $order->carrier, 'order' => $order));
            }
        }

        $this->context->smarty->assign(array(
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'order_collection' => $order_list,
            'refund_allowed' => false,
            'invoiceAllowed' => (int)Configuration::get('PS_INVOICE'),
            'is_guest' => true,
            'group_use_tax' => (Group::getPriceDisplayMethod($customer->id_default_group) == PS_TAX_INC),
            'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
            'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
            'use_tax' => Configuration::get('PS_TAX'),
            'guestInformations' => (array)$customer,
            'view_on_map' => Configuration::get('WK_GOOGLE_ACTIVE_MAP'),
        ));
    }

    public function setMedia()
    {
        parent::setMedia();

        Media::addJsDef(
            array(
                'historyUrl' => $this->context->link->getPageLink('guest-tracking.php', true)
            )
        );

        if (Tools::getValue('ajax') != 'true') {
            $this->addCSS(_THEME_CSS_DIR_.'order-detail.css');

            $this->addJS(array(
                _THEME_JS_DIR_.'order-detail.js',
                _THEME_JS_DIR_.'tools.js',
            ));

            $this->addJqueryPlugin(array('fancybox', 'scrollTo', 'footable', 'footable-sort'));
            $this->addJqueryUI(array('ui.tooltip'), 'base', true);

            // load Google Maps library if configured
            if (!count($this->errors)) {
                if ($ordersCollection = Order::getByReference(Tools::getValue('order_reference'))) {
                    foreach ($ordersCollection as $objOrder) {
                        if ($idHotel = HotelBookingDetail::getIdHotelByIdOrder($objOrder->id)) {
                            $objHotelBranchInformation = new HotelBranchInformation($idHotel, $this->context->language->id);
                            if (Validate::isLoadedObject($objHotelBranchInformation)) {
                                if (($apiKey = Configuration::get('PS_API_KEY'))
                                    && Configuration::get('WK_GOOGLE_ACTIVE_MAP')
                                    && ($PS_MAP_ID = Configuration::get('PS_MAP_ID'))
                                ) {
                                    if (floatval($objHotelBranchInformation->latitude) != 0
                                        && floatval($objHotelBranchInformation->longitude) != 0
                                    ) {
                                        Media::addJsDef(array(
                                            'PS_STORES_ICON' => $this->context->link->getMediaLink(_PS_IMG_.Configuration::get('PS_STORES_ICON')),
                                            'initiateMap' => 1,
                                            'PS_MAP_ID' => $PS_MAP_ID,
                                        ));

                                        $this->addJS(
                                            'https://maps.googleapis.com/maps/api/js?key='.$apiKey.'&libraries=places,marker&loading=async&callback=initMap&language='.
                                            $this->context->language->iso_code.'&region='.$this->context->country->iso_code
                                        );

                                        // just need to load the map once for the first order with map details. So break the loop.
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function processAddressFormat(Address $delivery, Address $invoice)
    {
        $inv_adr_fields = AddressFormat::getOrderedAddressFields($invoice->id_country, false, true);
        $dlv_adr_fields = AddressFormat::getOrderedAddressFields($delivery->id_country, false, true);

        $this->context->smarty->assign('inv_adr_fields', $inv_adr_fields);
        $this->context->smarty->assign('dlv_adr_fields', $dlv_adr_fields);
    }
}

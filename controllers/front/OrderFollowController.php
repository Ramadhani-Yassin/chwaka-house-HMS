<?php
/*
Ramadhani
*/

class OrderFollowControllerCore extends FrontController
{
    public $auth = true;
    public $php_self = 'order-follow';
    public $authRedirection = 'order-follow';
    public $ssl = true;

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->show_breadcrump = true;

        parent::initContent();
        if (isset($this->context->customer->id) && $this->context->customer->id) {
            $hasProductRefunds = 0;
            $hasRoomRefunds = 0;
            if ($ordersReturns = OrderReturn::getOrdersReturn($this->context->customer->id, Tools::getValue('id_order'))) {
                $objOrderReturn = new OrderReturn();
                foreach ($ordersReturns as $key => $orderReturn) {
                    $ordersReturns[$key]['total_rooms_refunds'] = 0;
                    $ordersReturns[$key]['total_products_refunds'] = 0;
                    if ($refundReqBookings = $objOrderReturn->getOrderRefundRequestedBookings($orderReturn['id_order'], $orderReturn['id_order_return'])) {
                        $hasRoomRefunds = 1;
                    }

                    if ($refundReqProducts = $objOrderReturn->getOrderRefundRequestedProducts($orderReturn['id_order'], $orderReturn['id_order_return'])) {
                        $hasProductRefunds = 1;
                    }
                }
            }

            $this->context->smarty->assign(array(
                'ordersReturns' => $ordersReturns,
                'hasRoomRefunds' => $hasRoomRefunds,
                'hasProductRefunds' => $hasProductRefunds
            ));

            $this->setTemplate(_PS_THEME_DIR_.'order-follow.tpl');
        } else {
            Tools::redirect(
                'index.php?controller=authentication&back='.urlencode($this->context->link->getpageLink('my-account'))
            );
        }
    }
}

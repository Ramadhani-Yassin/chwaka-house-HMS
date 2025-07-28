<?php
/*
Ramadhani
*/

class HistoryControllerCore extends FrontController
{
    public $auth = true;
    public $php_self = 'history';
    public $authRedirection = 'history';
    public $ssl = true;

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(array(
            _THEME_CSS_DIR_.'history.css',
        ));

        $this->addJqueryPlugin(array('footable', 'footable-sort'));
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->show_breadcrump = true;

        parent::initContent();
        if ($orders = Order::getCustomerOrders($this->context->customer->id)) {
            foreach ($orders as &$order) {
                $myOrder = new Order((int)$order['id_order']);
                if (Validate::isLoadedObject($myOrder)) {
                    $order['virtual'] = $myOrder->isVirtual(false);
                }
            }
        }
        $this->context->smarty->assign(array(
            'orders' => $orders,
            'overbooking_order_states' => OrderState::getOverBookingStates(),
            'invoiceAllowed' => (int)Configuration::get('PS_INVOICE'),
            'reorderingAllowed' => !(bool)Configuration::get('PS_DISALLOW_HISTORY_REORDERING'),
            'slowValidation' => Tools::isSubmit('slowvalidation')
        ));

        $this->setTemplate(_PS_THEME_DIR_.'history.tpl');
    }
}

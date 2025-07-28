<?php
/*
Ramadhani
*/

class PdfOrderReturnControllerCore extends FrontController
{
    public $php_self = 'pdf-order-return';
    protected $display_header = false;
    protected $display_footer = false;

    public function postProcess()
    {
        $from_admin = (Tools::getValue('adtoken') == Tools::getAdminToken('AdminReturn'.(int)Tab::getIdFromClassName('AdminReturn').(int)Tools::getValue('id_employee')));

        if (!$from_admin && !$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication&back=order-follow');
        }

        if (Tools::getValue('id_order_return') && Validate::isUnsignedId(Tools::getValue('id_order_return'))) {
            $this->orderReturn = new OrderReturn(Tools::getValue('id_order_return'));
        }

        if (!isset($this->orderReturn) || !Validate::isLoadedObject($this->orderReturn)) {
            die(Tools::displayError('Order return not found.'));
        } elseif (!$from_admin && $this->orderReturn->id_customer != $this->context->customer->id) {
            die(Tools::displayError('Order return not found.'));
        } elseif ($this->orderReturn->state < 2) {
            die(Tools::displayError('Order return not confirmed.'));
        }
    }

    public function display()
    {
        $pdf = new PDF($this->orderReturn, PDF::TEMPLATE_ORDER_RETURN, $this->context->smarty);
        $pdf->render();
    }
}

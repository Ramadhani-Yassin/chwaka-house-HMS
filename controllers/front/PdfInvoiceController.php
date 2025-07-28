<?php
/*
Ramadhani
*/

class PdfInvoiceControllerCore extends FrontController
{
    public $php_self = 'pdf-invoice';
    protected $display_header = false;
    protected $display_footer = false;

    public $content_only = true;

    protected $template;
    public $filename;

    public function postProcess()
    {
        if (!$this->context->customer->isLogged() && !Tools::getValue('secure_key')) {
            Tools::redirect('index.php?controller=authentication&back=pdf-invoice');
        }

        if (!(int)Configuration::get('PS_INVOICE')) {
            die(Tools::displayError('Invoices are disabled in this shop.'));
        }

        $id_order = (int)Tools::getValue('id_order');
        if (Validate::isUnsignedId($id_order)) {
            $order = new Order((int)$id_order);
        }

        if (!isset($order) || !Validate::isLoadedObject($order)) {
            die(Tools::displayError('The invoice was not found.'));
        }

        if ((isset($this->context->customer->id) && $order->id_customer != $this->context->customer->id) || (Tools::isSubmit('secure_key') && $order->secure_key != Tools::getValue('secure_key'))) {
            die(Tools::displayError('The invoice was not found.'));
        }

        if (!OrderState::invoiceAvailable($order->getCurrentState()) && !$order->invoice_number) {
            die(Tools::displayError('No invoice is available.'));
        }

        $this->order = $order;
    }

    public function display()
    {
        $order_invoice_list = $this->order->getInvoicesCollection();
        Hook::exec('actionPDFInvoiceRender', array('order_invoice_list' => $order_invoice_list));

        $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $this->context->smarty);
        $pdf->render();
    }
}

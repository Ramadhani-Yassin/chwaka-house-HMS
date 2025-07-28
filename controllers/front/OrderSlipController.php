<?php
/*
Ramadhani
*/

class OrderSlipControllerCore extends FrontController
{
    public $auth = true;
    public $php_self = 'order-slip';
    public $authRedirection = 'order-slip';
    public $ssl = true;

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(array(_THEME_CSS_DIR_.'history.css', _THEME_CSS_DIR_.'addresses.css'));
        $this->addJqueryPlugin(array('scrollTo', 'footable', 'footable-sort'));
        $this->addJS(array(
            _THEME_JS_DIR_.'history.js',
            _THEME_JS_DIR_.'tools.js') // retro compat themes 1.5
        );
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->show_breadcrump = true;

        parent::initContent();

        $this->context->smarty->assign('ordersSlip', OrderSlip::getOrdersSlip((int)$this->context->cookie->id_customer));
        $this->setTemplate(_PS_THEME_DIR_.'order-slip.tpl');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('generateVoucher')) {
            $idOrderSlip = Tools::getValue('id_order_slip');
            $objOrderSlip = new OrderSlip($idOrderSlip);
            if (!Validate::isLoadedObject($objOrderSlip)) {
                $this->errors[] = Tools::displayError('The credit slip is invalid.');
            } elseif ($objOrderSlip->redeem_status == OrderSlip::REDEEM_STATUS_REDEEMED) {
                $this->errors[] = Tools::displayError('The credit slip has already been redeemed.');
            } elseif ($objOrderSlip->id_customer != $this->context->customer->id) {
                $this->errors[] = Tools::displayError('Invalid request.');
            }

            if (!count($this->errors)) {
                if ($objOrderSlip->generateVoucher()) {
                    Tools::redirect($this->context->link->getPageLink(
                        $this->php_self,
                        $this->ssl,
                        $this->context->language->id,
                        'confirmation=1'
                    ));
                }

                $this->errors[] = Tools::displayError('The voucher code for this credit slip could not be generated.');
            }
        }

        parent::postProcess();
    }
}

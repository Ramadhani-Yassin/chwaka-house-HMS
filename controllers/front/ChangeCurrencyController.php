<?php
/*
Ramadhani
*/

class ChangeCurrencyControllerCore extends FrontController
{
    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $currency = new Currency((int)Tools::getValue('id_currency'));
        if (Validate::isLoadedObject($currency) && !$currency->deleted) {
            $this->context->cookie->id_currency = (int)$currency->id;
            $this->ajaxDie('1');
        }
        $this->ajaxDie('0');
    }
}

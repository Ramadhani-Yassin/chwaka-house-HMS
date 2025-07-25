<?php
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class ChequePaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        if ($cart->is_advance_payment) {
			$total = $cart->getOrderTotal(true, Cart::ADVANCE_PAYMENT);
        } else {
            $total = $cart->getOrderTotal(true, Cart::BOTH);
		}

        // check all service products are available
        ServiceProductCartDetail::validateServiceProductsInCart();

        $restrict_order = false;
        /*Check Order restrict condition before Payment by the customer*/
        if (Module::isInstalled('hotelreservationsystem') && Module::isEnabled('hotelreservationsystem')) {
            require_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';
            if (HotelOrderRestrictDate::validateOrderRestrictDateOnPayment($this)) {
                $restrict_order = true;
            }
        }
        /*END*/

        if (count($this->errors)) {
            $restrict_order = true;
        }

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'total' => $total,
            'isoCode' => $this->context->language->iso_code,
            'chequeName' => $this->module->chequeName,
            'chequeAddress' => Tools::nl2br($this->module->address),
            'this_path' => $this->module->getPathUri(),
            'this_path_cheque' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
            'restrict_order' => $restrict_order
        ));

        $this->setTemplate('payment_execution.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJS($this->module->getLocalPath().'views/js/front/payment.js');
    }
}

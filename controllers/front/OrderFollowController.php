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

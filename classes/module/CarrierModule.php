<?php
/*
Ramadhani
*/

abstract class CarrierModuleCore extends Module
{
    abstract public function getOrderShippingCost($params, $shipping_cost);
    abstract public function getOrderShippingCostExternal($params);
}

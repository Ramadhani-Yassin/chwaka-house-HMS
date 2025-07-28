<?php
/*
Ramadhani
*/

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include_once(_PS_ADMIN_DIR_.'/../config/config.inc.php');

$module = Tools::getValue('module');
$render = Tools::getValue('render');
$type = Tools::getValue('type');
$option = Tools::getValue('option');
$layers = Tools::getValue('layers');
$width = Tools::getValue('width');
$height = Tools::getValue('height');
$id_employee = Tools::getValue('id_employee');
$id_lang = Tools::getValue('id_lang');

if (!isset($cookie->id_employee) || !$cookie->id_employee  || $cookie->id_employee != $id_employee) {
    die(Tools::displayError());
}
    
if (!Validate::isModuleName($module)) {
    die(Tools::displayError());
}

if (!Tools::file_exists_cache($module_path = _PS_ROOT_DIR_.'/modules/'.$module.'/'.$module.'.php')) {
    die(Tools::displayError());
}

$shop_id = '';
Shop::setContext(Shop::CONTEXT_ALL);
if (Context::getContext()->cookie->shopContext) {
    $split = explode('-', Context::getContext()->cookie->shopContext);
    if (count($split) == 2) {
        if ($split[0] == 'g') {
            if (Context::getContext()->employee->hasAuthOnShopGroup($split[1])) {
                Shop::setContext(Shop::CONTEXT_GROUP, $split[1]);
            } else {
                $shop_id = Context::getContext()->employee->getDefaultShopID();
                Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
            }
        } elseif (Shop::getShop($split[1]) && Context::getContext()->employee->hasAuthOnShop($split[1])) {
            $shop_id = $split[1];
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
        } else {
            $shop_id = Context::getContext()->employee->getDefaultShopID();
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
        }
    }
}

// Check multishop context and set right context if need
if (Shop::getContext()) {
    if (Shop::getContext() == Shop::CONTEXT_SHOP && !Shop::CONTEXT_SHOP) {
        Shop::setContext(Shop::CONTEXT_GROUP, Shop::getContextShopGroupID());
    }
    if (Shop::getContext() == Shop::CONTEXT_GROUP && !Shop::CONTEXT_GROUP) {
        Shop::setContext(Shop::CONTEXT_ALL);
    }
}

// Replace existing shop if necessary
if (!$shop_id) {
    Context::getContext()->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
} elseif (Context::getContext()->shop->id != $shop_id) {
    Context::getContext()->shop = new Shop($shop_id);
}

require_once($module_path);

$graph = new $module();
$graph->setEmployee($id_employee);
$graph->setLang($id_lang);
if ($option) {
    $graph->setOption($option, $layers);
}

$graph->create($render, $type, $width, $height, $layers);
$graph->draw();

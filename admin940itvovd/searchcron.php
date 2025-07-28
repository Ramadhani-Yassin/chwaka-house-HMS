<?php
/*
Ramadhani
*/

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include(_PS_ADMIN_DIR_.'/../config/config.inc.php');

if (!Tools::getValue('id_shop')) {
    Context::getContext()->shop->setContext(Shop::CONTEXT_ALL);
} else {
    Context::getContext()->shop->setContext(Shop::CONTEXT_SHOP, (int)Tools::getValue('id_shop'));
}

if (substr(_COOKIE_KEY_, 34, 8) != Tools::getValue('token')) {
    die;
}

ini_set('max_execution_time', 7200);
Search::indexation(Tools::getValue('full'));
if (Tools::getValue('redirect') && isset($_SERVER['HTTP_REFERER'])) {
    Tools::redirectAdmin($_SERVER['HTTP_REFERER'].'&conf=4');
}

<?php
/*
Ramadhani
*/

$timer_start = microtime(true);
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}

if (!defined('PS_ADMIN_DIR')) {
    define('PS_ADMIN_DIR', _PS_ADMIN_DIR_);
}

require(_PS_ADMIN_DIR_.'/../config/config.inc.php');
require(_PS_ADMIN_DIR_.'/functions.php');

//small test to clear cache after upgrade
if (Configuration::get('PS_UPGRADE_CLEAR_CACHE')) {
    header('Cache-Control: max-age=0, must-revalidate');
    header('Expires: Mon, 06 Jun 1985 06:06:00 GMT+1');
    Configuration::updateValue('PS_UPGRADE_CLEAR_CACHE', 0);
}

// For retrocompatibility with "tab" parameter
if (!isset($_GET['controller']) && isset($_GET['tab'])) {
    $_GET['controller'] = strtolower($_GET['tab']);
}
if (!isset($_POST['controller']) && isset($_POST['tab'])) {
    $_POST['controller'] = strtolower($_POST['tab']);
}
if (!isset($_REQUEST['controller']) && isset($_REQUEST['tab'])) {
    $_REQUEST['controller'] = strtolower($_REQUEST['tab']);
}

// Prepare and trigger admin dispatcher
Dispatcher::getInstance()->dispatch();

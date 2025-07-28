<?php
/*
Ramadhani
*/

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
require_once(_PS_ADMIN_DIR_.'/../config/config.inc.php');
require_once(_PS_ADMIN_DIR_.'/init.php');

if (isset($_GET['img']) and Validate::isMd5($_GET['img']) and isset($_GET['name']) and Validate::isGenericName($_GET['name']) and file_exists(_PS_UPLOAD_DIR_.$_GET['img'])) {
    header('Content-type: image/jpeg');
    header('Content-Disposition: attachment; filename="'.$_GET['name'].'.jpg"');
    echo file_get_contents(_PS_UPLOAD_DIR_.$_GET['img']);
}

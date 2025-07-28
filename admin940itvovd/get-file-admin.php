<?php
/*
Ramadhani
*/

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
require(_PS_ADMIN_DIR_.'/../config/config.inc.php');
Controller::getController('GetFileController')->run();

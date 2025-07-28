<?php
/*
Ramadhani
*/
require_once (_PS_TOOL_DIR_.'defuse/php-encryption/defuse-crypto.phar');
require_once (_PS_TOOL_DIR_.'random_compat/lib/random.php');


// Include some alias functions
require_once(_PS_CONFIG_DIR_.'alias.php');
require_once(_PS_CLASS_DIR_.'PrestaShopAutoload.php');

spl_autoload_register(array(PrestaShopAutoload::getInstance(), 'load'));

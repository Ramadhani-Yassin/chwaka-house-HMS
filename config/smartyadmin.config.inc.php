<?php
/*
Ramadhani
*/

global $smarty;
$smarty->debugging = false;
$smarty->debugging_ctrl = 'NONE';

// Let user choose to force compilation
$smarty->force_compile = (Configuration::get('PS_SMARTY_FORCE_COMPILE') == _PS_SMARTY_FORCE_COMPILE_) ? true : false;
// But force compile_check since the performance impact is small and it is better for debugging
$smarty->compile_check = true;

function smartyTranslate($params, $smarty)
{
    $htmlentities = !isset($params['js']);
    $pdf = isset($params['pdf']);
    $addslashes = (isset($params['slashes']) || isset($params['js']));
    $sprintf = isset($params['sprintf']) ? $params['sprintf'] : null;

    if (!isset($params['lang'])) {
        $params['lang'] = null;
    } else {
        if (!is_object($params['lang'])) {
            $params['lang'] = new Language((int)$params['lang']);
        }
        if (!Validate::isLoadedObject($params['lang'])) {
            $params['lang'] = null;
        }
    }

    if ($pdf) {
        return Translate::smartyPostProcessTranslation(Translate::getPdfTranslation($params['s'], $sprintf, $params['lang']), $params);
    }

    $filename = ((!isset($smarty->compiler_object) || !is_object($smarty->compiler_object->template)) ? $smarty->template_resource : $smarty->compiler_object->template->getTemplateFilepath());

    // If the template is part of a module
    if (!empty($params['mod'])) {
        return Translate::smartyPostProcessTranslation(Translate::getModuleTranslation($params['mod'], $params['s'], basename($filename, '.tpl'), $sprintf, isset($params['js']), $params['lang']), $params);
    }

    // If the tpl is at the root of the template folder
    if (dirname($filename) == '.') {
        $class = 'index';
    }

    // If the tpl is used by a Helper
    $className = isset(Context::getContext()->controller->controller_name) ? Tools::toUnderscoreCase(substr(Context::getContext()->controller->controller_name, 5)) : '';
    if (strpos($filename, 'helpers') === 0 && $className && strpos($smarty->source->name, '/template/controllers/'.$className.'/') === false) {
        $class = 'Helper';
    }
    // If the tpl is used by a Controller
    else {
        if (!empty(Context::getContext()->override_controller_name_for_translations)) {
            $class = Context::getContext()->override_controller_name_for_translations;
        } elseif (isset(Context::getContext()->controller)) {
            $class_name = get_class(Context::getContext()->controller);
            $class = substr($class_name, 0, strpos(Tools::strtolower($class_name), 'controller'));
        } else {
            // Split by \ and / to get the folder tree for the file
        $folder_tree = preg_split('#[/\\\]#', $filename);
            $key = array_search('controllers', $folder_tree);

        // If there was a match, construct the class name using the child folder name
        // Eg. xxx/controllers/customers/xxx => AdminCustomers
        if ($key !== false) {
            $class = 'Admin'.Tools::toCamelCase($folder_tree[$key + 1], true);
        } elseif (isset($folder_tree[0])) {
            $class = 'Admin'.Tools::toCamelCase($folder_tree[0], true);
        }
        }
    }

    return Translate::smartyPostProcessTranslation(Translate::getAdminTranslation($params['s'], $class, $addslashes, $htmlentities, $sprintf), $params);
}

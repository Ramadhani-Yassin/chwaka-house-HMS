<?php
/*
Ramadhani
*/
$con = new AdminController();
$tab = new Tab((int)Tab::getIdFromClassName(Tools::getValue('controller')));
$con->id = $tab->id;
$con->init();
$con->initToolbar();
$con->initPageHeaderToolbar();
$con->setMedia();
$con->initHeader();
$con->initFooter();

$title = array($tab->getFieldByLang('name'));

Context::getContext()->smarty->assign(array(
    'navigationPipe', Configuration::get('PS_NAVIGATION_PIPE'),
    'meta_title' => implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $title),
    'display_header' => true,
    'display_header_javascript' => true,
    'display_footer' => true,
));
$dir = Context::getContext()->smarty->getTemplateDir(0).'controllers'.DIRECTORY_SEPARATOR.trim($con->override_folder, '\\/').DIRECTORY_SEPARATOR;
$header_tpl = file_exists($dir.'header.tpl') ? $dir.'header.tpl' : 'header.tpl';
$tool_tpl = file_exists($dir.'page_header_toolbar.tpl') ? $dir.'page_header_toolbar.tpl' : 'page_header_toolbar.tpl';
Context::getContext()->smarty->assign(array(
    'show_page_header_toolbar' => true,
    'title' => implode(' '.Configuration::get('PS_NAVIGATION_PIPE').' ', $title),
    'toolbar_btn' => array()
));
echo Context::getContext()->smarty->fetch($header_tpl);
echo Context::getContext()->smarty->fetch($tool_tpl);

<?php
/*
Ramadhani
*/

if (isset(Context::getContext()->controller)) {
    $controller = Context::getContext()->controller;
} else {
    $controller = new FrontController();
    $controller->init();
    $controller->setMedia();
}
Tools::displayFileAsDeprecated();
$controller->displayHeader();

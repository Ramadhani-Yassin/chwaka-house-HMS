<?php
/*
Ramadhani
*/

if (isset(Context::getContext()->controller)) {
    $controller = Context::getContext()->controller;
} else {
    $controller = new FrontController();
    $controller->init();
}

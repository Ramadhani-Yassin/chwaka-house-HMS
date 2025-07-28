<?php
/*
Ramadhani
*/

function fd($var)
{
    return (Tools::fd($var));
}

function p($var)
{
    return (Tools::p($var));
}

function d($var)
{
    Tools::d($var);
}

function ppp($var)
{
    return (Tools::p($var));
}

function ddd($var)
{
    Tools::d($var);
}

function epr($var, $message_type = null, $destination = null, $extra_headers = null)
{
    return Tools::error_log($var, $message_type, $destination, $extra_headers);
}

/**
 * Sanitize data which will be injected into SQL query
 *
 * @param string $string SQL data which will be injected into SQL query
 * @param bool $htmlOK Does data contain HTML code ? (optional)
 * @return string Sanitized data
 */
function pSQL($string, $htmlOK = false)
{
    return Db::getInstance()->escape($string, $htmlOK);
}

function bqSQL($string)
{
    return str_replace('`', '\`', pSQL($string));
}

function displayFatalError()
{
    $error = null;
    if (function_exists('error_get_last')) {
        $error = error_get_last();
    }
    if ($error !== null && in_array($error['type'], array(E_ERROR, E_PARSE, E_COMPILE_ERROR ))) {
        echo '[QloApps] Fatal error in module file :'.$error['file'].':<br />'.$error['message'];
    }
}

/**
 * @deprecated
 */
function nl2br2($string)
{
    Tools::displayAsDeprecated();
    return Tools::nl2br($string);
}

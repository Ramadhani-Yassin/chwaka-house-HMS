<?php
/*
Ramadhani
*/

/**
 * @since 1.5.0
 */
class PrestaShopDatabaseExceptionCore extends PrestaShopException
{
    public function __toString()
    {
        return $this->message;
    }
}

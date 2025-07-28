<?php
/*
Ramadhani
*/

class AdminAddonsCatalogControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        $parent_domain = Tools::getHttpHost(true).substr($_SERVER['REQUEST_URI'], 0, -1 * strlen(basename($_SERVER['REQUEST_URI'])));
        $iso_lang = $this->context->language->iso_code;
        $iso_currency = $this->context->currency->iso_code;
        $iso_country = $this->context->country->iso_code;
        $activity = Configuration::get('PS_SHOP_ACTIVITY');
        $addons_url = 'http://addons.prestashop.com/iframe/search-1.6.php?psVersion='._PS_VERSION_.'&isoLang='.$iso_lang.'&isoCurrency='.$iso_currency.'&isoCountry='.$iso_country.'&activity='.(int)$activity.'&parentUrl='.$parent_domain;
        $addons_content = Tools::file_get_contents($addons_url);

        $this->context->smarty->assign(array(
            'iso_lang' => $iso_lang,
            'iso_currency' => $iso_currency,
            'iso_country' => $iso_country,
            'display_addons_content' => $addons_content !== false,
            'addons_content' => $addons_content,
            'parent_domain' => $parent_domain,
        ));

        parent::initContent();
    }
}

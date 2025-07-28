<?php
/*
Ramadhani
*/

/**
 * @since 1.5.0
 */
class ModuleFrontControllerCore extends FrontController
{
    /** @var Module */
    public $module;

    public function __construct()
    {
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        if (!$this->module->active) {
            Tools::redirect('index');
        }

        $this->page_name = 'module-'.$this->module->name.'-'.Dispatcher::getInstance()->getController();

        parent::__construct();

        $this->controller_type = 'modulefront';

        $in_base = isset($this->page_name) && is_object(Context::getContext()->theme) && Context::getContext()->theme->hasColumnsSettings($this->page_name);

        $tmp = isset($this->display_column_left) ? (bool)$this->display_column_left : true;
        $this->display_column_left = $in_base ? Context::getContext()->theme->hasLeftColumn($this->page_name) : $tmp;

        $tmp = isset($this->display_column_right) ? (bool)$this->display_column_right : true;
        $this->display_column_right = $in_base ? Context::getContext()->theme->hasRightColumn($this->page_name) : $tmp;
    }

    /**
     * Assigns module template for page content
     *
     * @param string $template Template filename
     * @throws PrestaShopException
     */
    public function setTemplate($template)
    {
        if (!$path = $this->getTemplatePath($template)) {
            throw new PrestaShopException("Template '$template' not found");
        }

        $this->template = $path;
    }

    /**
     * Finds and returns module front template that take the highest precedence
     *
     * @param string $template Template filename
     * @return string|false
     */
    public function getTemplatePath($template)
    {
        if (Tools::file_exists_cache(_PS_THEME_DIR_.'modules/'.$this->module->name.'/'.$template)) {
            return _PS_THEME_DIR_.'modules/'.$this->module->name.'/'.$template;
        } elseif (Tools::file_exists_cache(_PS_THEME_DIR_.'modules/'.$this->module->name.'/views/templates/front/'.$template)) {
            return _PS_THEME_DIR_.'modules/'.$this->module->name.'/views/templates/front/'.$template;
        } elseif (Tools::file_exists_cache(_PS_MODULE_DIR_.$this->module->name.'/views/templates/front/'.$template)) {
            return _PS_MODULE_DIR_.$this->module->name.'/views/templates/front/'.$template;
        }

        return false;
    }

    public function initContent()
    {
        if (Tools::isSubmit('module') && Tools::getValue('controller') == 'payment') {
            $currency = Currency::getCurrency((int)$this->context->cart->id_currency);
            $orderTotal = $this->context->cart->getOrderTotal();
            $minimal_purchase = Tools::convertPrice((float)Configuration::get('PS_PURCHASE_MINIMUM'), $currency);
            if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $minimal_purchase) {
                Tools::redirect('index.php?controller=order&step=1');
            }
        }
        parent::initContent();
    }
}

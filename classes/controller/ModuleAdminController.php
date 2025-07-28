<?php
/*
Ramadhani
*/

/**
 * @since 1.5.0
 */
abstract class ModuleAdminControllerCore extends AdminController
{
    /** @var Module */
    public $module;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->controller_type = 'moduleadmin';

        $tab = new Tab($this->id);
        if (!$tab->module) {
            throw new PrestaShopException('Admin tab '.get_class($this).' is not a module tab');
        }

        $this->module = Module::getInstanceByName($tab->module);
        if (!$this->module->id) {
            throw new PrestaShopException("Module {$tab->module} not found");
        }
    }

    /**
     * Creates a template object
     *
     * @param string $tpl_name Template filename
     * @return Smarty_Internal_Template
     */
    public function createTemplate($tpl_name)
    {
        if (file_exists(_PS_THEME_DIR_.'modules/'.$this->module->name.'/views/templates/admin/'.$tpl_name) && $this->viewAccess()) {
            return $this->context->smarty->createTemplate(_PS_THEME_DIR_.'modules/'.$this->module->name.'/views/templates/admin/'.$tpl_name, $this->context->smarty);
        } elseif (file_exists($this->getTemplatePath().$this->override_folder.$tpl_name) && $this->viewAccess()) {
            return $this->context->smarty->createTemplate($this->getTemplatePath().$this->override_folder.$tpl_name, $this->context->smarty);
        }

        return parent::createTemplate($tpl_name);
    }

    /**
     * Get path to back office templates for the module
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/';
    }
}

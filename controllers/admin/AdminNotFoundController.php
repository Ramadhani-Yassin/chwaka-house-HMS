<?php
/*
Ramadhani
*/

class AdminNotFoundControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function checkAccess()
    {
        return true;
    }

    public function viewAccess($disable = false)
    {
        return true;
    }

    public function initContent()
    {
        $this->errors[] = Tools::displayError('Controller not found');
        $tpl_vars['controller'] = Tools::getvalue('controllerUri', Tools::getvalue('controller'));
        $this->context->smarty->assign($tpl_vars);
        parent::initContent();
    }
}

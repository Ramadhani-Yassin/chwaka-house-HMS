<?php
/*
Ramadhani
*/

class AdminMarketingControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        $this->display = 'view';
        return parent::initContent();
    }
    
    public function initToolbarTitle()
    {
        $this->toolbar_title = array_unique($this->breadcrumbs);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn = array();
    }
    
    public function initToolbar()
    {
        return false;
    }
    
    public function renderView()
    {
        $this->tpl_view_vars = array(
            'modules_list' => $this->renderModulesList(),
        );
        return parent::renderView();
    }
}

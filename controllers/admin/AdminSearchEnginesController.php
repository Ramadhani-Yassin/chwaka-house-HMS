<?php
/*
Ramadhani
*/

/**
 * @property SearchEngine $object
 */
class AdminSearchEnginesControllerCore extends AdminController
{
    public function __construct()
    {
        // redirect to Dashboard for stats related to Search Engines
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminDashboard'));

        $this->bootstrap = true;
        $this->table = 'search_engine';
        $this->className = 'SearchEngine';
        $this->lang = false;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->fields_list = array(
            'id_search_engine' => array('title' => $this->l('ID'), 'width' => 25),
            'server' => array('title' => $this->l('Server')),
            'getvar' => array('title' => $this->l('GET variable'), 'width' => 100)
        );

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Referrer')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Server'),
                    'name' => 'server',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('$_GET variable'),
                    'name' => 'getvar',
                    'size' => 40,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_search_engine'] = array(
                'href' => self::$currentIndex.'&addsearch_engine&token='.$this->token,
                'desc' => $this->l('Add new search engine', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        $this->identifier_name = 'server';
        
        parent::initPageHeaderToolbar();
    }
}

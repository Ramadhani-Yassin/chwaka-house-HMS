<?php
/*
Ramadhani
*/

/**
 * @property Contact $object
 */
class AdminContactsControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'contact';
        $this->className = 'Contact';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            )
        );

        $this->fields_list = array(
            'id_contact' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'name' => array('title' => $this->l('Title')),
            'email' => array('title' => $this->l('Email address')),
            'description' => array('title' => $this->l('Description')),
        );

        parent::__construct();
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Contacts'),
                'icon' => 'icon-envelope-alt'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Title'),
                    'name' => 'name',
                    'required' => true,
                    'lang' => true,
                    'col' => 4,
                    'hint' => $this->l('Contact name (e.g. Customer Support).'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email address'),
                    'name' => 'email',
                    'required' => false,
                    'col' => 4,
                    'hint' => $this->l('Emails will be sent to this address.'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Save messages?'),
                    'name' => 'customer_service',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'hint' => $this->l('If enabled, all messages will be saved in the "Customer Service" page under the "Customers" menu.'),
                    'values' => array(
                        array(
                            'id' => 'customer_service_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'customer_service_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'required' => false,
                    'lang' => true,
                    'col' => 6,
                    'hint' => $this->l('Further information regarding this contact.'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Save and stay'),
                    'name' => 'submitAdd'.$this->table.'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                ),
            ),
        );
        
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            );
        }

        return parent::renderForm();
    }

    public function initPageHeaderToolbar()
    {
        $this->initToolbar();
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_contact'] = array(
                'href' => self::$currentIndex.'&addcontact&token='.$this->token,
                'desc' => $this->l('Add new contact', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }
}

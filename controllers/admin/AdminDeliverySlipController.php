<?php
/*
Ramadhani
*/

class AdminDeliverySlipControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'delivery';

        $this->context = Context::getContext();

        $this->fields_options = array(
            'general' => array(
                'title' =>    $this->l('Delivery slip options'),
                'fields' =>    array(
                    'PS_DELIVERY_PREFIX' => array(
                        'title' => $this->l('Delivery prefix'),
                        'desc' => $this->l('Prefix used for delivery slips.'),
                        'type' => 'textLang'
                    ),
                    'PS_DELIVERY_NUMBER' => array(
                        'title' => $this->l('Delivery number'),
                        'desc' => $this->l('The next delivery slip will begin with this number and then increase with each additional slip.'),
                        'cast' => 'intval',
                        'type' => 'text'
                    ),
                    'PS_PDF_IMG_DELIVERY' => array(
                        'title' => $this->l('Enable product image'),
                        'hint' => $this->l('Adds an image before product name on Delivery-slip'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                ),
                'submit' => array('title' => $this->l('Save'))
            )
        );

        parent::__construct();
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Print PDF delivery slips'),
                'icon' => 'icon-print'
            ),
            'input' => array(
                array(
                    'type' => 'date',
                    'label' => $this->l('From'),
                    'name' => 'date_from',
                    'maxlength' => 10,
                    'required' => true,
                    'hint' => $this->l('Format: 2011-12-31 (inclusive).')
                ),
                array(
                    'type' => 'date',
                    'label' => $this->l('To'),
                    'name' => 'date_to',
                    'maxlength' => 10,
                    'required' => true,
                    'hint' => $this->l('Format: 2012-12-31 (inclusive).')
                )
            ),
            'submit' => array(
                'title' => $this->l('Generate PDF file'),
                'icon' => 'process-icon-download-alt'
            )
        );

        $this->fields_value = array(
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d')
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdddelivery')) {
            if (!Validate::isDate(Tools::getValue('date_from'))) {
                $this->errors[] = Tools::displayError('Invalid \'from\' date');
            }
            if (!Validate::isDate(Tools::getValue('date_to'))) {
                $this->errors[] = Tools::displayError('Invalid \'to\' date');
            }
            if (!count($this->errors)) {
                if (count(OrderInvoice::getByDeliveryDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to')))) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminPdf').'&submitAction=generateDeliverySlipsPDF&date_from='.urlencode(Tools::getValue('date_from')).'&date_to='.urlencode(Tools::getValue('date_to')));
                } else {
                    $this->errors[] = Tools::displayError('No delivery slip was found for this period.');
                }
            }
        } else {
            parent::postProcess();
        }
    }

    public function initContent()
    {
        $this->initTabModuleList();
        $this->initPageHeaderToolbar();
        $this->show_toolbar = false;
        $this->content .= $this->renderForm();
        $this->content .= $this->renderOptions();
        $this->context->smarty->assign(array(
            'content' => $this->content,
            'url_post' => self::$currentIndex.'&token='.$this->token,
            'show_page_header_toolbar' => $this->show_page_header_toolbar,
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn' => $this->page_header_toolbar_btn
        ));
    }
}

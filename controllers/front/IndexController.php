<?php
/*
Ramadhani
*/

class IndexControllerCore extends FrontController
{
    public $php_self = 'index';

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $this->addJS(_THEME_JS_DIR_.'index.js');

        $this->context->smarty->assign(array('HOOK_HOME' => Hook::exec('displayHome'),
            'HOOK_HOME_TAB' => Hook::exec('displayHomeTab'),
            'HOOK_HOME_TAB_CONTENT' => Hook::exec('displayHomeTabContent')
        ));
        $this->setTemplate(_PS_THEME_DIR_.'index.tpl');
    }
}

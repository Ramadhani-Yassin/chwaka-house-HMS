<?php
/*
Ramadhani
*/

class PasswordControllerCore extends FrontController
{
    public $php_self = 'password';
    public $auth = false;

    /**
     * Start forms process
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (Tools::isSubmit('email')) {
            if (!($email = trim(Tools::getValue('email'))) || !Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } else {
                $customer = new Customer();
                $customer->getByemail($email);
                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('There is no account registered for this email address.');
                } elseif (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } elseif ((strtotime($customer->last_passwd_gen.'+'.($min_time = (int)Configuration::get('PS_PASSWD_TIME_FRONT')).' minutes') - time()) > 0) {
                    $this->errors[] = sprintf(Tools::displayError('You can regenerate your password only every %d minute(s)'), (int)$min_time);
                } else {
                    $mail_params = array(
                        '{email}' => $customer->email,
                        '{lastname}' => $customer->lastname,
                        '{firstname}' => $customer->firstname,
                        '{url}' => $this->context->link->getPageLink('password', true, null, 'token='.$customer->secure_key.'&id_customer='.(int)$customer->id)
                    );
                    if (Mail::Send($this->context->language->id, 'password_query', Mail::l('Password query confirmation'), $mail_params, $customer->email, $customer->firstname.' '.$customer->lastname)) {
                        $this->context->smarty->assign(array('confirmation' => 2, 'customer_email' => $customer->email));
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                    }
                }
            }
        } elseif (($token = Tools::getValue('token')) && ($id_customer = (int)Tools::getValue('id_customer'))) {
            $email = Db::getInstance()->getValue('SELECT `email` FROM '._DB_PREFIX_.'customer c WHERE c.`secure_key` = \''.pSQL($token).'\' AND c.id_customer = '.(int)$id_customer);
            if ($email) {
                $customer = new Customer();
                $customer->getByemail($email);
                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('Customer account not found');
                } elseif (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } elseif ((strtotime($customer->last_passwd_gen.'+'.(int)Configuration::get('PS_PASSWD_TIME_FRONT').' minutes') - time()) > 0) {
                    Tools::redirect('index.php?controller=authentication&error_regen_pwd');
                } else {
                    $customer->passwd = Tools::encrypt($password = Tools::passwdGen(MIN_PASSWD_LENGTH, 'RANDOM'));
                    $customer->last_passwd_gen = date('Y-m-d H:i:s', time());
                    if ($customer->update()) {
                        Hook::exec('actionPasswordRenew', array('customer' => $customer, 'password' => $password));
                        $mail_params = array(
                            '{email}' => $customer->email,
                            '{lastname}' => $customer->lastname,
                            '{firstname}' => $customer->firstname,
                            '{passwd}' => $password
                        );
                        if (Mail::Send($this->context->language->id, 'password', Mail::l('Your new password'), $mail_params, $customer->email, $customer->firstname.' '.$customer->lastname)) {
                            $this->context->smarty->assign(array('confirmation' => 1, 'customer_email' => $customer->email));
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                        }
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred with your account, which prevents us from sending you a new password. Please report this issue using the contact form.');
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
            }
        } elseif (Tools::getValue('token') || Tools::getValue('id_customer')) {
            $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
        }
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $this->setTemplate(_PS_THEME_DIR_.'password.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        if (count($this->errors)) {
            return;
        }
    }
}

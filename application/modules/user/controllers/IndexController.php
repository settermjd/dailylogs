<?php

class User_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }

    protected function _getForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/user/config/forms.xml', 'login');
        return new User_Form_Login($config);
    }

    public function loginAction()
    {
        $form = $this->_getForm();

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();
                if (!empty($formInput)) {

                } else {

                }
            } else {
                // login failure!
                $this->view->form = $form;
            }
        }
    }

    public function logoutAction()
    {
        // action body
    }

    public function updateProfileAction()
    {
        // action body
    }

    public function updatePasswordAction()
    {
        // action body
    }

}
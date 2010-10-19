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
                    $authAdapter = $this->_getAuthAdapter($formInput);
                    $auth = Zend_Auth::getInstance();
                    $result = $auth->authenticate($authAdapter);
                    if (!$result->isValid()) {
                        $this->_flashMessage('Login failed');
                        // login failure - needs to display error message!
                        $this->view->form = $form;
                    } else {
                        $data = $authAdapter->getResultRowObject(null, 'password');
                        $auth->getStorage()->write($data);
                        $this->_redirect($this->_redirectUrl);
                        return;
                    }
                } else {
                    // login failure!
                    $this->view->form = $form;
                }
            } else {
                // login failure!
                $this->view->form = $form;
            }
        }
    }

    public function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        $this->_redirect('/');
    }

    public function updateProfileAction()
    {
        // action body
    }

    public function updatePasswordAction()
    {
        // action body
    }

    protected function _getAuthAdapter($formData)
    {
        $dbAdapter = Zend_Registry::get('db');
                $config = Zend_Registry::get('config');
                $password = $formData['password'];
                $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

                $authAdapter->setTableName('users')
                            ->setIdentityColumn('username')
                            ->setCredentialColumn('password')
                            ->setCredentialTreatment('MD5(?)')
                            ->setIdentity($formData['username'])
                            ->setCredential($password);

                return $authAdapter;
    }

    protected function _flashMessage($message)
    {
        $flashMessenger = $this->_helper->FlashMessenger;
        $flashMessenger->setNamespace('actionErrors');
        $flashMessenger->addMessage($message);
    }

    public function accessDeniedAction()
    {
        // action body
        $flashMessenger = $this->_helper->getHelper('FlashMessenger');
        $this->view->messages = $flashMessenger->getMessages();
    }


}


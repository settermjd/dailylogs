<?php

class User_IndexController extends Zend_Controller_Action
{
    protected $_authObj = null;

    protected $_flashMessenger = null;

    protected $_redirector = null;

    public function init()
    {
        /* Initialize action controller here */
        $auth = Zend_Auth::getInstance();
        $this->_authObj = $auth->getStorage()->read();
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        $this->_redirector = $this->_helper->getHelper('Redirector');
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

    protected function _getUpdateProfileForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/user/config/forms.xml', 'profile');
        return new User_Form_UpdateProfile($config);
    }

    protected function _getUpdatePasswordForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/user/config/forms.xml', 'update_password');
        return new User_Form_UpdatePassword($config);
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
        $form = $this->_getUpdateProfileForm();
        $form->populate((array)Zend_Auth::getInstance()->getIdentity());
        $this->view->messages = $this->_flashMessenger->getMessages();

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();
                if (!empty($formInput)) {
                    $userObj = new User_Model_User();
                    $updateStatus = $userObj->update(
                        $formInput,
                        $userObj->getAdapter()->quoteInto('id = ?', $formInput['id'])
                    );
                    $this->_updateProfile($formInput);
                    $this->_flashMessenger->addMessage('Profile successfully updated');
                    $this->_redirector->setCode(303)
                          ->setExit(true)
                          ->setGotoSimple("update-profile");
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

    public function updatePasswordAction()
    {
        $form = $this->_getUpdatePasswordForm();
        $form->populate((array)Zend_Auth::getInstance()->getIdentity());
        $this->view->messages = $this->_flashMessenger->getMessages();

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();
                if (!empty($formInput)) {
                    $userObj = new User_Model_User();
                    $userObj->updatePassword(
                        $formInput['id'],
                        $formInput['password']
                    );
                    $this->_updateProfile($formInput);
                    $this->_flashMessenger->addMessage('Password successfully updated');
                    $this->_redirector->setCode(303)
                          ->setExit(true)
                          ->setGotoSimple("logout");
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

    protected function _updateProfile($formInput)
    {
        $auth = Zend_Auth::getInstance()->getIdentity();
        foreach($formInput as $key => $value) {
            $auth->$key = $value;
        }
    }

}


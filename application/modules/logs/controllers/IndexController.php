<?php

class Logs_IndexController extends Zend_Controller_Action
{
    protected $_authObj = null;
    protected $_flashMessenger = null;

    public function init()
    {
        /* Initialize action controller here */
        $auth = Zend_Auth::getInstance();
        $this->_authObj = $auth->getStorage()->read();
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
    }

    public function indexAction()
    {
        // action body
    }

    protected function _getAddForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/logs/config/forms.xml', 'addlog');
        $form = new Logs_Form_AddLog($config);

        $createdDate = new ZendX_JQuery_Form_Element_DatePicker(
            'created_date',
            array(
                'label' => 'Date Created:',
                'order' => 0
            )
        );

        $form->addElement($createdDate);

        // populate with default information
        $form->populate(
            array(
                'user_id' => $this->_authObj->id
            )
        );

        return $form;
    }

    protected function _getEditForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/logs/config/forms.xml', 'editlog');
        $form = new Logs_Form_AddLog($config);
        $logObj = new Logs_Model_Log();

        $createdDate = new ZendX_JQuery_Form_Element_DatePicker(
            'created_date',
            array(
                'label' => 'Date Created:',
                'order' => 0,
                'dateFormat' => 'dd/mm/yy'
            )
        );

        $form->addElement($createdDate);

        //var_dump($this->getRequest()->getParam('logid')); exit;

        $userLog = $logObj->getLog(
            $this->getRequest()->getParam('id'),
            $this->_authObj->id
        );

        $formData = array(
            'user_id' => $this->_authObj->id,
            'id' => $this->getRequest()->getParam('id'),
        );

        $date = new Zend_Date();
        $date->set($userLog->created_date, Zend_Date::DATES);
        $userLog->created_date = $date->get('dd/MM/yyyy');

        if(!empty($userLog)) {
            $formData = array_merge($formData, $userLog->toArray());
        }

        // populate with default information
        $form->populate($formData);

        return $form;
    }

    protected function _getDeleteForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/logs/config/forms.xml', 'deletelog');
        $form = new Logs_Form_DeleteLog($config);

        // populate with default information
        $form->populate(
            array(
                'user_id' => $this->_authObj->id,
                'id' => $this->getRequest()->getParam('logid', NULL),
            )
        );

        return $form;
    }

    public function listLogsAction()
    {
        $logObj = new Logs_Model_Log();
        $currentLogs = $logObj->findLogs($this->_authObj->id);
        $this->view->logs = $currentLogs;
        $this->view->authUser = $this->_authObj;
        $this->view->messages = $this->_flashMessenger->getMessages();
    }

    public function editLogAction()
    {
        $form = $this->_getEditForm();

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();
                if (!empty($formInput)) {
                    $auth = Zend_Auth::getInstance();
                    $logObj = new Logs_Model_Log();
                    $currentLogs = $logObj->editLog(
                        $auth->getIdentity()->id,
                        $formInput
                    );
                    $this->_flashMessenger->addMessage('Record successfully updated');
                    $this->_forward('list-logs');
                } else {
                    $this->view->form = $form;
                }
            } else {
                $this->view->form = $form;
            }
        }
    }

    public function deleteLogAction()
    {
        $form = $this->_getDeleteForm();

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();

                if (!is_null($form->getValue('submit'))) {
                    $auth = Zend_Auth::getInstance();
                    $logObj = new Logs_Model_Log();
                    $currentLogs = $logObj->deleteLog(
                        $auth->getIdentity()->id,
                        $formInput['id']
                    );
                    $this->_forward('list-logs');
                } else {
                    $this->_forward('list-logs');
                }
            } else {
                $this->view->form = $form;
            }
        }
    }

    public function addLogAction()
    {
        $form = $this->_getAddForm();

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();
                if (!empty($formInput)) {
                    $auth = Zend_Auth::getInstance();
                    $logObj = new Logs_Model_Log();
                    $currentLogs = $logObj->addLog(
                        $auth->getIdentity()->id,
                        $formInput
                    );
                    $this->_forward('list-logs');
                } else {
                    $this->view->form = $form;
                }
            } else {
                $this->view->form = $form;
            }
        }
    }
}








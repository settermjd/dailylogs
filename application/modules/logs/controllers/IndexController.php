<?php

class Logs_IndexController extends Zend_Controller_Action
{
    protected $_authObj = null;

    public function init()
    {
        /* Initialize action controller here */
        $auth = Zend_Auth::getInstance();
        $this->_authObj = $auth->getStorage()->read();
    }

    public function indexAction()
    {
        // action body
    }

    protected function _getAddForm()
    {
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/modules/logs/config/forms.xml', 'addlog');
        $form = new Logs_Form_AddLog($config);

        // populate with default information
        $form->populate(
            array(
                'user_id' => $this->_authObj->id
            )
        );

        return $form;
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

    public function listLogsAction()
    {
        $logObj = new Logs_Model_Log();
        $currentLogs = $logObj->findLogs(
            $this->_authObj->id,
            array(
                /*'startDate' => $startDate,
                'endDate' => $startDate,*/
            )
        );
        $this->view->logs = $currentLogs;
    }

}






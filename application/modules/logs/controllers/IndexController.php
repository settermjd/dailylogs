<?php

class Logs_IndexController extends Zend_Controller_Action
{
    protected $_authObj = null;

    protected $_flashMessenger = null;

    protected $_cache = null;

    public function init()
    {
        /* Initialize action controller here */
        $auth = Zend_Auth::getInstance();
        $this->_authObj = $auth->getStorage()->read();
        $this->_cache = Zend_Registry::get('cache');
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

        $this->view->ckeditor = true;

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

        $this->view->ckeditor = true;

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
        $currentLogs = NULL;

        if ($this->_cache) {
            if ($this->_cache->test('_listlogs_' . $this->_authObj->id)) {
                $currentLogs = $this->_cache->load('_listlogs_' . $this->_authObj->id);
            } else {
                $logObj = new Logs_Model_Log();
                $currentLogs = $logObj->findLogsByUserId($this->_authObj->id);
                $this->_cache->save(
                    $currentLogs,
                    '_listlogs_' . $this->_authObj->id,
                    array(
                        $this->_authObj->id,
                        $this->_authObj->username
                    )
                );
            }
        } else {
            $logObj = new Logs_Model_Log();
            $currentLogs = $logObj->findLogsByUserId($this->_authObj->id);
        }

        Common_Util_Logger::writeLog('Listing logs for: [username]: ' . $this->_authObj->username, Zend_Log::INFO);

        $paginator = Zend_Paginator::factory($currentLogs);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $this->view->paginator = $paginator;
        $this->view->authUser = $this->_authObj;
        $this->view->messages = $this->_flashMessenger->getMessages();
    }

    public function userAction()
    {
        $logObj = new Logs_Model_Log();
        $userObj = new User_Model_User();
        $username = $this->_request->getParam('username', '');
        $currentLogs = NULL;

        if (!empty($username)) {
            $currentLogs = $logObj->findLogsByUsername($username);
        } else {
            $currentLogs = $logObj->findUserLogs();
        }

        $paginator = Zend_Paginator::factory($currentLogs);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $this->view->paginator = $paginator;

        $this->view->usersList = $userObj->getUserList();
        $this->view->selectedUser = $this->_request->getParam('username', '');
        $this->view->authUser = $this->_authObj;
        $this->view->messages = $this->_flashMessenger->getMessages();
    }

    public function editLogAction()
    {
        $form = $this->_getEditForm();
        $servicesMap = $this->getInvokeArg('bootstrap')->getResource('services');

        if (!$this->getRequest()->isPost()) {
            $this->view->form = $form;
        } else {
            if ($form->isValid($_POST)) {
                // success!
                $formInput = $form->getValues();

                if (!empty($formInput)) {
                    // check if the data submitted contains spam
                    $spamStatus = $this->_filterSpamInput($servicesMap, $formInput);

                    $logObj = new Logs_Model_Log();
                    $currentLogs = $logObj->editLog(
                        $this->_authObj->id,
                        $formInput
                    );
                    Common_Util_Logger::writeLog(
                        'Deleted log id: ' . $formInput['id'] . ' for: [username]: ' . $this->_authObj->username,
                        Zend_Log::INFO
                    );
                    $this->_clearCachedRecord('_listlogs_' . $this->_authObj->id);
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
                $formInput = $form->getValues();
                if (!is_null($form->getValue('submit'))) {
                    $logObj = new Logs_Model_Log();
                    $currentLogs = $logObj->deleteLog(
                        $this->_authObj->id,
                        $formInput['id']
                    );
                    Common_Util_Logger::writeLog(
                        'Deleted log id: ' . $formInput['id'] . ' for: [username]: ' . $this->_authObj->username,
                        Zend_Log::INFO
                    );
                    $this->_clearCachedRecord('_listlogs_' . $this->_authObj->id);
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
                    Common_Util_Logger::writeLog(
                        'Added log id: ' . $currentLogs . ' for: [username]: ' . $this->_authObj->username,
                        Zend_Log::INFO
                    );
                    $this->_clearCachedRecord('_listlogs_' . $this->_authObj->id);
                    $this->_forward('list-logs');
                } else {
                    $this->view->form = $form;
                }
            } else {
                $this->view->form = $form;
            }
        }
    }

    protected function _clearCachedRecord($cacheId)
    {
        if ($this->_cache) {
            if ($this->_cache->test($cacheId)) {
                $this->_cache->remove($cacheId);
            }
        }
    }

    /**
     * Manages the record if there's spam in it and adds it to the spam queue.
     *
     * @param array $servicesMap
     */
    protected function _filterSpamInput($servicesMap, $inputData)
    {
        if (array_key_exists('akismet', $servicesMap)) {
            $akismet = $servicesMap['akismet'];
            $isSpam = $akismet->isSpam(
                $akismet->getFilterData($inputData)
            );
            return $isSpam;
        }
    }
}
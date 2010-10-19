<?php
/**
 * Manages the log information
 *
 * @author settermj
 */
class Logs_DataController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->contextSwitch()
            ->setAutoJsonSerialization(false)
            ->addActionContext(
                'listLogs',
                array(
                    'xml',
                    'json'
                )
            )
            ->initContext();
    }

    public function indexAction()
    {
        // action body
        $this->_forward('list-logs');
    }

    public function addLogAction()
    {
        $logObj = new Logs_Model_Log();
        $userId = $this->_request->getParam('userId', '');
        $logBody = $this->_request->getParam('body', '');
        $logDate = $this->_request->getParam('log_date', '');

        $currentLogs = $logObj->addLog(
            $userId,
            array(
                'logBody' => $logBody,
                'logDate' => $logDate
            )
        );
    }

    public function editLogAction()
    {
        $logObj = new Logs_Model_Log();
        $userId = $this->_request->getParam('userId', '');
        $logId = $this->_request->getParam('logId', '');
        $logBody = $this->_request->getParam('body', '');

        $currentLogs = $logObj->editLog(
            $userId,
            array(
                'logBody' => $logBody,
                'logId' => $logId
            )
        );
    }

    public function deleteLogAction()
    {
        $logObj = new Logs_Model_Log();
        $userId = $this->_request->getParam('userId', '');
        $logId = $this->_request->getParam('userId', '');

        $currentLogs = $logObj->deleteLog($userId, $logId);
    }

    public function listLogsAction()
    {
        $userId = $this->_request->getParam('userId', '');
        $startDate = $this->_request->getParam('startDate', '');
        $endDate = $this->_request->getParam('endDate', '');
        $logObj = new Logs_Model_Log();

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $currentLogs = $logObj->findLogs(
            $userId,
            array(
                /*'startDate' => $startDate,
                'endDate' => $startDate,*/
            )
        );
    }

}


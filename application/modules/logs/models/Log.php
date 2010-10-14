<?php

class Logs_Model_Log extends Zend_Db_Table
{
    protected $_name = 'logs';
    protected $_primary = 'id';

    public function addLog($userId, $logDetails)
    {
        return $this->insert($logDetails);
    }

    public function editLog($userId, $logDetails)
    {
        return $this->update($logDetails, '');
    }

    public function deleteLog($userId, $logId)
    {
        return $this->delete();
    }

    public function findLogs($userId, $logOptions)
    {
        $select = $this->select()->order('created_date ASC');
        foreach($logOptions as $key => $value) {
            $select->where("$key = ?", $value);
        }

        return $this->fetchAll($select);
    }
}


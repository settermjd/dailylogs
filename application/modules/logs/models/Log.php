<?php

class Logs_Model_Log extends Zend_Db_Table
{
    protected $_name = 'logs';
    protected $_primary = 'id';

    public function addLog($userId, $logDetails)
    {
        $date = new Zend_Date($logDetails['created_date'], Zend_Date::ISO_8601);
        $logDetails['created_date'] = $date->toString('yyyy-MM-dd HH:mm:ss');
        return $this->insert($logDetails);
    }

    public function editLog($userId, $logDetails)
    {
        $date = new Zend_Date();
        $date->set($logDetails['created_date'], Zend_Date::DATES);
        $logDetails['created_date'] = $date->get('yyyy-MM-dd HH:mm:ss');
        $where = array(
            $this->getAdapter()->quoteInto('user_id = ?', $userId),
            $this->getAdapter()->quoteInto('id = ?', $logDetails['id']),
        );
        return $this->update($logDetails, $where);
    }

    public function deleteLog($userId, $logId)
    {
        $where = array(
            $this->getAdapter()->quoteInto('user_id = ?', $userId),
            $this->getAdapter()->quoteInto('id = ?', $logId),
        );
        return $this->delete($where);
    }

    public function getLog($logId, $userId)
    {
        $select = $this->select()
                       ->where('id = ?', $logId)
                       ->where('user_id = ?', $userId);
        $row = $this->fetchRow($select);
        return $row;
    }

    public function findLogs($userId, $logOptions=array())
    {
        $select = $this->select()->order('created_date DESC');
        foreach($logOptions as $key => $value) {
            $select->where("$key = ?", $value);
        }
        return $this->fetchAll($select);
    }

    public function findLogsByUsername($userName)
    {
        $select = $this->select(Zend_Db_Table::SELECT_WITH_FROM_PART)->setIntegrityCheck(false)
                    ->join(
                        "users",
                        "users.id = $this->_name.user_id",
                        array('id', 'username', 'firstname', 'lastname', 'email')
                    )
                    ->where("users.username = ?", $userName)
                    ->order('created_date DESC');

        return $this->fetchAll($select);
    }

    protected function getCacheIdTag($idTag)
    {

    }
}


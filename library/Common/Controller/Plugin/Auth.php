<?php
/**
 * Ensure's that a user is logged in before using the application
 *
 */
class Common_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    private $_redirector = NULL;

    /**
     * Authenticate all requests to the service
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            if (Zend_Controller_Action_HelperBroker::hasHelper('redirector')) {
                $this->_redirector = Zend_Controller_Action_HelperBroker::getExistingHelper('redirector');
            }
        }
    }
}
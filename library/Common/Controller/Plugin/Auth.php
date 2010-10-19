<?php
/**
 * Ensure's that a user is logged in before using the application
 *
 */
class Common_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    protected $_redirector = NULL;

    /**
     * Authenticate all requests to the service
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $auth = Zend_Auth::getInstance();
        if (!($auth instanceof Zend_Auth) || !$auth->hasIdentity()) {
            if ($request->getModuleName() == 'user' && $request->getControllerName() == 'index') {
                // avoid an infinite redirect loop when already in login
                return;
            }
            $this->_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $this->_redirector->setCode(303)
                              ->setExit(true)
                              ->setGotoRoute(array(), 'login', true);
        }
    }
}
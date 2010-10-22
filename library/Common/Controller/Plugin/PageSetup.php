<?php
/**
 * Ensure's that a user is logged in before using the application
 *
 */
class Common_Controller_Plugin_PageSetup extends Zend_Controller_Plugin_Abstract
{
    /**
     * Authenticate all requests to the service
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($request->getModuleName() == 'user' && $request->getControllerName() == 'index'
            && $request->getActionName() == 'login') {

            $front = Zend_Controller_Front::getInstance();
            $view = $front->getParam('bootstrap')->getResource('voew');
            $view->placeholder('foo')->set("fullwidth");
        }
    }
}
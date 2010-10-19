<?php
/**
 * Ensure's that a user is logged in before using the application
 *
 */
class Common_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var String
     */
    const DEFAULT_ROLE = 'guest';

    /**
     * @var String
     */
    const ACCESS_DENIED_ROUTE = 'access-denied';

    /**
     * Authenticate all requests to the service
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $acl = Zend_Registry::get('acl');
        $auth = Zend_Auth::getInstance();
        $role = self::DEFAULT_ROLE;
        $resource = $request->getModuleName() . ':' . $request->getControllerName();
        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');

        if ($auth->hasIdentity()) {
            $authObj = $auth->getStorage()->read();
            $role = $authObj->role;
        }

        if ($acl->has($resource) &&
            !$acl->isAllowed($role, $resource, $request->getActionName()))
        {
            $flashMessenger->addMessage(
                'You do not have the proper access level to view that page'
            );
            $redirector->setCode(303)
                       ->setExit(true)
                       ->setGotoRoute(array(), self::ACCESS_DENIED_ROUTE, true);
        }
    }
}
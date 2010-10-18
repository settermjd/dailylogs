<?php
/**
 * Ensure's that a user is logged in before using the application
 *
 */
class Common_Controller_Plugin_Cache extends Zend_Controller_Plugin_Abstract
{
    const USER_CACHE_KEY = '__app_userlogs_';

    /**
     * Authenticate all requests to the service
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        /* Initialize action controller here */
        $auth = Zend_Auth::getInstance();
        $authObj = $auth->getStorage()->read();
        $cache = Zend_Registry::get('cache');

        if ($cache) {
            if (!$cache->test(self::USER_CACHE_KEY . $cache->id)) {

            }
        }
    }
}
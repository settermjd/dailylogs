<?php

class User_Bootstrap extends Zend_Application_Module_Bootstrap
{
    /**
     * This allows for adding resources with configuration options
     */
    protected function _initPlugins()
    {
        $front = Zend_Controller_Front::getInstance();

        // add the auth setup plugin
        $front->registerPlugin(
            new Common_Controller_Plugin_Auth()
        );
    }
}
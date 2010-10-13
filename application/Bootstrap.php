<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    /**
     * Setup the application mail functionality - Sendmail
     */
    protected function _initMail()
    {
        $mailTransport = new Zend_Mail_Transport_Sendmail();
        Zend_Mail::setDefaultTransport($mailTransport);
    }

    /*
    protected function _initZFDebug()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
            'plugins' => array('Variables',
                               'File' => array('base_path' => APPLICATION_PATH),
                               'Memory',
                               'Time',
                               'Registry',
                               'Exception'),
            'z-index' => 255,
            'image_path' => APPLICATION_PATH . '/../public/sites/default/images/debugbar/',
            'jquery_path' => APPLICATION_PATH . '/../public/sites/default/js/'
        );

        // Instantiate the database adapter and setup the plugin.
        // Alternatively just add the plugin like above and rely on the autodiscovery feature.
        if ($this->hasPluginResource('db')) {
            $this->bootstrap('db');
            $db = $this->getPluginResource('db')->getDbAdapter();
            $options['plugins']['Database']['adapter'] = $db;
        }

        // Setup the cache plugin
        if ($this->hasPluginResource('cache')) {
            $this->bootstrap('cache');
            $cache = $this-getPluginResource('cache')->getDbAdapter();
            $options['plugins']['Cache']['backend'] = $cache->getBackend();
        }

        $debug = new ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debug);
    }
    */

    /**
     *  Instantiate the application database resource object
     *
     *  @return Zend_Db_Adapter
     */
    protected function _initDb()
    {
        /**
         * Boot the cache initialisation for use her.
         */
        $this->bootstrap('Cache');
        $this->_cache = $this->getResource('Cache');

        /**
         * Only attempt to cache the metadata if we have a cache available
         */
        if (!is_null($this->_cache)) {
            try {
                Zend_Db_Table_Abstract::setDefaultMetadataCache($this->_cache);
            } catch(Zend_Db_Table_Exception $e) {
                print $e->getMessage();
            }
        }

        $db = $this->getPluginResource('db')->getDbAdapter();

        /**
         * Set the default fetch mode to object throughout the application
         */
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        /**
         * Force the initial connection to handle error relating to caching etc.
         */
        try {
            $db->getConnection();
        } catch (Zend_Db_Adapter_Exception $e) {
            // perhaps a failed login credential, or perhaps the RDBMS is not running
        } catch (Zend_Exception $e) {
            // perhaps factory() failed to load the specified Adapter class
        }

        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);

        return $db;
    }

    /**
     * Setup the core config resource
     *
     * @return Zend_Config_Ini
     */
    protected function _initConfig()
    {
        $config = new Zend_Config_Ini(
            APPLICATION_PATH . "/configs/application.ini",
            $this->getEnvironment()
        );
        Zend_Registry::set('config', $config);
        return $config;
    }

    /** init cache
     *
     * @return Zend_Cache
     */
    protected function _initCache()
    {

    }

    protected function _initNavigation()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/configs/navigation.xml', 'nav');
        $navigation = new Zend_Navigation($config);
        $view->navigation($navigation); //Zend_View_Helper_Navigation
        Zend_Registry::set('Zend_Navigation', $navigation);

        Zend_Controller_Action_HelperBroker::addHelper(
            new Common_Controller_Action_Helper_NavigationManager()
        );

        return $navigation;
    }

    /**
     * Constructs the Zend_Navigation object from the config file
     */
    private function _buildNavigationObject($navigationConfig, $cacheObj=null, $cacheId=null)
    {
        $navigation = NULL;

        if (!empty($navigationConfig)) {
            $config = new Zend_Config_Xml(APPLICATION_PATH . $navigationConfig, 'nav');
            $navigation = new Zend_Navigation($config);
            if ($cacheObj && !empty($cacheId)) {
                $cacheObj->save($navigation, $cacheId);
            }
        }

        return $navigation;
    }


    /**
     * Initialises application pagination.
     *
     * @return NULL
     */
    protected function _initPaginator()
    {
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            'common/default_pagination.phtml'
        );
    }

}


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

    protected function _initViewHelpers()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();

        $view->doctype('HTML5');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->headTitle()->setSeparator(' | ');
        $view->headTitle('Daily Logs Application');

        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
        $viewRenderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
    }

    protected function _initAcls()
    {
        $acl = new Zend_Acl();

        $acl->addRole(new Zend_Acl_Role('guest'));
        $acl->addRole(new Zend_Acl_Role('developer', 'guest'));
        $acl->addRole(new Zend_Acl_Role('manager', 'guest'));

        $moduleResource = new Zend_Acl_Resource('logs');
        $acl->add($moduleResource)
            ->add(new Zend_Acl_Resource('logs:index'), $moduleResource)
            ->add(new Zend_Acl_Resource('default:index'), $moduleResource);

        $acl->allow(
            array('developer'),
            'logs:index',
            array('add-log', 'edit-log', 'delete-log', 'list-logs')
        );

        $acl->allow(
            array('manager'),
            'logs:index',
            array('user')
        );

        $acl->allow(null, 'default:index');

        Zend_Registry::set('acl', $acl);

        $front = Zend_Controller_Front::getInstance();

        // add the auth setup plugin
        $front->registerPlugin(
            new Common_Controller_Plugin_Acl()
        );

        return $acl;
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
        $this->bootstrap('Config');
        $appConfig = Zend_Registry::get('config');
        $cache = NULL;

        // only attempt to init the cache if turned on
        if ($appConfig->app->caching) {

            // get the cache settings
            $config = $appConfig->app->cache;

            if (NULL !== $this->_tmpFolder) {
                if ('File' == $config->backend->adapter && !isset($config->backend->options->cache_dir)) {
                    $config->backend->options->cache_dir = $this->_tmpFolder . '/cache';
                    if (!is_dir($config->backend->options->cache_dir)) {
                        mkdir($config->backend->options->cache_dir);
                    }
                }
            }

            try {
                $cache = Zend_Cache::factory(
                        $config->frontend->adapter,
                        $config->backend->adapter,
                        $config->frontend->options->toArray(),
                        $config->backend->options->toArray()
                );
                Zend_Registry::set('cache', $cache);
                return $cache;
            } catch (Zend_Cache_Exception $e) {
                // send email to alert caching failed
                Zend_Registry::get('log')->alert(
                        'Caching failed: adapter=' . $config->backend->adapter . ', message=' . $e->getMessage(
                ));
            }
        }
    }

    protected function _initNavigation()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/configs/navigation.xml', 'nav');
        $navigation = new Zend_Navigation($config);
        $view->navigation($navigation);
        Zend_Registry::set('Zend_Navigation', $navigation);

        Zend_Controller_Action_HelperBroker::addHelper(
            new Common_Controller_Action_Helper_NavigationManager()
        );

        $this->bootstrap('log');
        $logger = $this->getResource('log');
        $logger->log('Initialised Navigation', Zend_Log::INFO);

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


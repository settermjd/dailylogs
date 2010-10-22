<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    const DEFAULT_RECORDS_PER_PAGE = 10;

    /**
     * Setup the application mail functionality.
     *
     * By default, this uses Sendmail as the mail transport
     * @link http://framework.zend.com/manual/en/zend.mail.html
     */
    protected function _initMail()
    {
        $mailTransport = new Zend_Mail_Transport_Sendmail();
        Zend_Mail::setDefaultTransport($mailTransport);
    }

    /**
     * Setup the view helpers for the application.
     *
     *  This allows for the overriding of the layout and view. It also
     *  sets additional paths for the view scripts to be located in.
     *  @link http://framework.zend.com/manual/en/zend.view.html
     */
    protected function _initViewHelpers()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();

        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
        $viewRenderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
    }

    /**
     * Sets up the placeholders for the application.
     *
     * This resource, looks after the meta, links, doctype, favicons etc
     * @link http://framework.zend.com/manual/en/zend.view.helpers.html
     */
    protected function _initPlaceholders()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();

        $view->doctype('XHTML1_STRICT');
        $view->headTitle('Daily Logs')
             ->setSeparator(' | ');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8')
             ->appendHttpEquiv('X-UA-Compatible', 'IE=8');
        $view->headScript()->prependFile(
            'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js',
            $type = 'text/javascript'
        );
        $view->headLink()->headLink(
            array(
                'rel' => 'favicon',
                'href' => '/img/favicon.ico',
                'type' => 'image/x-icon'
            ), 'PREPEND'
        );
    }

    /**
     * Setup access control lists for the application.
     *
     * Currently it has three roles: guest, developer and manager. With this
     * it setups up a logs:index and default:index resource along with
     * permissions on those resources.
     * @link http://framework.zend.com/manual/en/zend.acl.html
     * @return Zend_Acl
     */
    protected function _initAcls()
    {
        $acl = new Zend_Acl();

        $acl->addRole(new Zend_Acl_Role('guest'));
        $acl->addRole(new Zend_Acl_Role('developer', 'guest'));
        $acl->addRole(new Zend_Acl_Role('manager', 'guest'));

        $moduleResource = new Zend_Acl_Resource('logs');
        $acl->add($moduleResource)
            ->add(new Zend_Acl_Resource('logs:index'), $moduleResource)
            ->add(new Zend_Acl_Resource('default:index'), $moduleResource)
            ->add(new Zend_Acl_Resource('user:index'), $moduleResource);

        $acl->allow(
            array('developer'),
            'logs:index',
            array('add-log', 'edit-log', 'delete-log', 'list-logs')
        );

        $acl->allow(
            array('manager'), 'logs:index', array('user')
        );

        $acl->allow(null, 'default:index');
        $acl->allow('developer', 'user:index');
        $acl->allow('manager', 'user:index');

        Zend_Registry::set('acl', $acl);

        $front = Zend_Controller_Front::getInstance();

        // add the auth setup plugin
        $front->registerPlugin(
            new Common_Controller_Plugin_Acl()
        );

        return $acl;
    }

    /**
     * Sets up the ZF Debug plugin.
     *
     * This displays a bar that gives debug statistics about the application
     * @see http://code.google.com/p/zfdebug/
     * @link http://code.google.com/p/zfdebug/wiki/Documentation
     */
    protected function _initZFDebug()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
            'plugins' => array('Variables',
                               'File' => array('base_path' => APPLICATION_PATH),
                               'Html',
                               'Memory',
                               'Time',
                               'Registry',
                               'Exception'),
            'z-index' => 255,
            'image_path' => '/images/debugbar',
            'jquery_path' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js',
        );

        // Instantiate the database adapter and setup the plugin.
        // Alternatively just add the plugin like above and rely on the autodiscovery feature.
        if ($this->hasPluginResource('Db')) {
            $this->bootstrap('Db');
            $db = $this->getPluginResource('Db')->getDbAdapter();
            $options['plugins']['Database']['adapter'] = $db;
        }

        // Setup the cache plugin
        if ($this->hasPluginResource('Cache')) {
            $this->bootstrap('Cache');
            $cache = $this-getPluginResource('Cache')->getDbAdapter();
            $options['plugins']['Cache']['backend'] = $cache->getBackend();
        }

        $debug = new ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debug);
    }

    /**
     *  Instantiate the application database resource object
     *
     *  @return Zend_Db_Adapter
     *  @link http://framework.zend.com/manual/en/zend.db.html
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
     * @link http://framework.zend.com/manual/en/zend.config.html
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

    /**
     * Setup the application cache.
     *
     * @return Zend_Cache
     * @link http://framework.zend.com/manual/en/zend.cache.html
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
            } catch (Zend_Cache_Exception $e) {
                // send email to alert caching failed
                Zend_Registry::get('log')->alert(
                        'Caching failed: adapter=' . $config->backend->adapter . ', message=' . $e->getMessage(
                ));
            }
        }
        Zend_Registry::set('cache', $cache);
        return $cache;
    }

    /**
     * Setup the application navigation.
     *
     * Covers support for menus, links, breadcrumbs and is translation enabled
     * @link http://framework.zend.com/manual/en/zend.navigation.html
     * @see _buildNavigationObject()
     */
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
     * Initialise translation support
     *
     * This setup uses the TMX adapter backend
     * @link http://framework.zend.com/manual/en/zend.translate.html
     */
    protected function _initLocale()
    {
        $this->bootstrap('Cache');
        $this->bootstrap('Log');

        $locale = new Zend_Locale();

        Zend_Registry::set('Zend_Locale', $locale);
        if (Zend_Registry::get('cache') !== NULL) {
            Zend_Translate::setCache(Zend_Registry::get('cache'));
        }

        $defaultLanguage = 'en';

        $options = array(
                'log' => $this->getResource('log'),
                'logUntranslated' => true,
                'clear' => true,
                'reload' => true
        );

        $translate = new Zend_Translate(
            'tmx', APPLICATION_PATH . '/data/language',
            'auto',
            $options
        );
        $actual = $translate->getLocale();

        Zend_Registry::set('Zend_Translate', $translate);

        // make the routes translatable
        Zend_Controller_Router_Route::setDefaultTranslator($translate);

        if (!$translate->isAvailable($locale->getLanguage())) {
            // not available languages are rerouted to another language
            $translate->setLocale($defaultlanguage);
        }

        $translate->getLocale();
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
     * It sets up the default template and records per page
     *
     * @link http://framework.zend.com/manual/en/zend.paginator.html
     */
    protected function _initPaginator()
    {
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        Zend_Paginator::setDefaultItemCountPerPage(self::DEFAULT_RECORDS_PER_PAGE);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            '/common/pagination/default.phtml'
        );
    }
}


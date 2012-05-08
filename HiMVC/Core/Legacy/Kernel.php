<?php
/**
 * Contains: Legacy kernel (4.x)
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace HiMVC\Core\Legacy
{

    use HiMVC\API\MVC\Values\Request as APIRequest;
    use HiMVC\Core\MVC\Controllable;

    use eZDebug;
    use eZDebugSetting;
    use eZDB;
    use eZINI;
    use eZSys;
    use eZTextCodec;
    use eZExecution;
    use eZLocale;
    use ezpEvent;
    use eZModule;
    use eZUserLoginHandler;
    use eZHTTPHeader;
    use eZURLAliasML;
    use eZURLWildcard;
    use eZURI;
    use eZSiteAccess;
    use eZSession;
    use eZExtension;
    use eZHTTPTool;
    use eZError;
    use eZUser;
    use eZTemplate;
    use eZPublishSDK;
    use eZNavigationPart;
    use ezpI18n;
    use eZSSLZone;

    /**
     * Provides a legacy kernel class
     *
     * Allows legacy kernel to be executes as Controller via run()
     * and
     * provides a way to execute old template code directly via legacyInclude()
     */
    class Kernel implements Controllable
    {
        /**
         * @var bool
         */
        private $isBooted = false;

        /**
         * @var \HiMVC\API\MVC\Values\Request
         */
        private $mainRequest;

        /**
         * @var array
         */
        private $legacyObjectConverters;

        /**
         * @param \HiMVC\API\MVC\Values\Request $mainRequest
         * @param array $legacyObjectConverters
         */
        public function __construct( APIRequest $mainRequest, array $legacyObjectConverters = array() )
        {
            $this->mainRequest = $mainRequest;
            $this->legacyObjectConverters = $legacyObjectConverters;
        }

        /**
         * Boot up old kernel
         *
         * @param \HiMVC\API\MVC\Values\Request $mainRequest
         */
        protected function boot( APIRequest $mainRequest )
        {
            if ( $this->isBooted )
                return;

            $siteBasics = array(
                'external-css' => true,
                'show-page-layout' => true,
                'module-run-required' => true,
                'policy-check-required' => true,
                'policy-check-omit-list' => array(),// List of module names which will skip policy checking
                'url-translator-allowed' => true,
                'validity-check-required' => false,
                'user-object-required' => true,
                'session-required' => true,
                'db-required' => false,
                'no-cache-adviced' => false,
                'site-design-override' => false,
                'module-repositories' => array(),// List of directories to search for modules
            );

            $GLOBALS['eZSiteBasics'] =& $siteBasics;

            $GLOBALS['eZRedirection'] = false;

            /*!
             Reads settings from i18n.ini and passes them to eZTextCodec.
            */
            $ini = eZINI::instance( 'i18n.ini' );

            list( $i18nSettings['internal-charset'], $i18nSettings['http-charset'], $i18nSettings['mbstring-extension'] ) =
                $ini->variableMulti( 'CharacterSettings', array( 'Charset', 'HTTPCharset', 'MBStringExtension' ), array( false, false, 'enabled' ) );

            eZTextCodec::updateSettings( $i18nSettings );// @todo Change so code only supports utf-8 in 5.0?

            // Initialize debug settings.
            \eZUpdateDebugSettings();

            // Set the different permissions/settings.
            $ini = eZINI::instance();

            // Set correct site timezone
            //$timezone = $ini->variable( "TimeZoneSettings", "TimeZone");
            //if ( $timezone )
            //{
            //    date_default_timezone_set( $timezone );
            //}


            list( $iniFilePermission, $iniDirPermission ) =
                $ini->variableMulti( 'FileSettings', array( 'StorageFilePermissions', 'StorageDirPermissions' ) );

            $iniVarDirectory = eZSys::cacheDirectory() ;

            // OPTIMIZATION:
            // Sets permission array as global variable, this avoids the eZCodePage include
            $GLOBALS['EZCODEPAGEPERMISSIONS'] = array( 'file_permission' => octdec( $iniFilePermission ),
                                                       'dir_permission'  => octdec( $iniDirPermission ),
                                                       'var_directory'   => $iniVarDirectory );


            eZExecution::addCleanupHandler( function()
            {
                if ( class_exists( 'eZDB', false ) && eZDB::hasInstance() )
                {
                    $db = eZDB::instance();
                    $db->setIsSQLOutputEnabled( false );
                }
            } );

            eZExecution::addFatalErrorHandler( function()
            {
                header("HTTP/1.1 500 Internal Server Error");
                print( "<b>Fatal error</b>: The web server did not finish its request<br/>" );
                if ( ini_get('display_errors') == 1 )
                {
                    if ( eZDebug::isDebugEnabled() )
                        print( "<p>The execution of eZ Publish was abruptly ended, the debug output is present below.</p>" );
                    else
                        print( "<p>Debug information can be found in the log files normally placed in var/log/* or by enabling 'DebugOutput' in site.ini</p>" );
                }
                else
                {
                    print( "<p>Contact website owner with current url and info on what you did, and owner will be able to debug the issue further (by enabling 'display_errors' in php.ini).</p>" );
                }
                $templateResult = null;
                \eZDisplayResult( $templateResult );
            } );

            // Enable this line to get eZINI debug output
            // eZINI::setIsDebugEnabled( true );
            // Enable this line to turn off ini caching
            // eZINI::setIsCacheEnabled( false);

            if ( $ini->variable( 'RegionalSettings', 'Debug' ) == 'enabled' )
                eZLocale::setIsDebugEnabled( true );

            eZDebug::setHandleType( eZDebug::HANDLE_FROM_PHP );

            $GLOBALS['eZGlobalRequestURI'] = eZSys::serverVariable( 'REQUEST_URI' );

            // Initialize basic settings, such as vhless dirs and separators
            eZSys::init( 'index.php', $mainRequest->indexDir === $mainRequest->wwwDir );


            // Check for extension
            eZExtension::activateExtensions( 'default' );
            // Extension check end

            // @todo Inject matched siteaccess from $mainRequest instead of re matching again
            // means some deprecated matching methods will go.
            $access = eZSiteAccess::match( eZURI::instance( $mainRequest->uri ),
                                  eZSys::hostname(),
                                  eZSys::serverPort(),
                                  eZSys::indexFile() );
            $access = eZSiteAccess::change( $access );
            eZDebugSetting::writeDebug( 'kernel-siteaccess', $access, 'current siteaccess' );

            // Check for siteaccess extension
            eZExtension::activateExtensions( 'access' );
            // Siteaccess extension check end

            // Now that all extensions are activated and siteaccess has been changed, reset
            // all eZINI instances as they may not take into account siteaccess specific settings.
            eZINI::resetAllInstances( false );

            ezpEvent::getInstance()->registerEventListeners();

            eZSession::setSessionArray( $mainRequest->session );

            /**
             * Check for activating Debug by user ID (Final checking. The first was in eZDebug::updateSettings())
             * @uses eZUser::instance() So needs to be executed after eZSession::start()|lazyStart()
             */
            eZDebug::checkDebugByUser();

            $this->isBooted = true;
        }

        /**
         * Execution point for controller actions
         *
         * Note: As this calls $action directly, make sure private functions are marked as private and not
         * protected so they can not be exposed directly by mistakes in routes.
         *
         * @param \HiMVC\API\MVC\Values\Request $request
         * @param string $action
         * @param array $params
         * @param array $viewParams Params to send to template (used for sending params from parent template to child)
         *
         * @return \HiMVC\API\MVC\Values\Response
         */
        public function run( APIRequest $request, $action, array $params = array(), array $viewParams = array() )
        {
            $this->boot( $this->mainRequest );

            $scriptStartTime = microtime( true );

            //ignore_user_abort( true );
            ob_start();

            $siteBasics =& $GLOBALS['eZSiteBasics'];
            $GLOBALS['eZRedirection'] = false;
            $access = eZSiteAccess::current();

            //error_reporting ( E_ALL | E_STRICT );

            $warningList = array();

            eZDebug::setScriptStart( $scriptStartTime );

            eZDebug::addTimingPoint( "Script start" );

            $uri = eZURI::instance( $request->originalUri );
            $GLOBALS['eZRequestedURI'] = $uri;

            // Be able to do general events early in process
            ezpEvent::getInstance()->notify( 'request/preinput', array( $uri ) );

            // Initialize module loading
            $siteBasics['module-repositories'] = eZModule::activeModuleRepositories();
            eZModule::setGlobalPathList( $siteBasics['module-repositories'] );

            // make sure we get a new $ini instance now that it has been reset
            $ini = eZINI::instance();
            if ( $siteBasics['session-required'] )
            {
                // Check if this should be run in a cronjob
                if ( $ini->variable( 'Session', 'BasketCleanup' ) !== 'cronjob' )
                {
                    eZSession::addCallback( 'destroy_pre', function( eZDB $db, $key, $escapedKey )
                    {
                        $basket = eZBasket::fetch( $key );
                        if ( $basket instanceof eZBasket )
                            $basket->remove();
                    });
                    eZSession::addCallback( 'gc_pre', function( eZDB $db, $time )
                    {
                        eZBasket::cleanupExpired( $time );
                    } );

                    eZSession::addCallback( 'cleanup_pre', function( eZDB $db )
                    {
                        eZBasket::cleanup();
                    } );
                }

                // addCallBack to update session id for shop basket on session regenerate
                eZSession::addCallback( 'regenerate_post', function( eZDB $db, $escNewKey, $escOldKey  )
                {
                    $db->query( "UPDATE ezbasket SET session_id='{$escNewKey}' WHERE session_id='{$escOldKey}'" );
                } );
            }

            // if $siteBasics['db-required'], open a db connection and check that db is connected
            if ( $siteBasics['db-required'] && !eZDB::instance()->isConnected() )
            {
                $warningList[] = array( 'error' => array( 'type' => 'kernel',
                                                          'number' => eZError::KERNEL_NO_DB_CONNECTION ),
                                        'text' => 'No database connection could be made, the system might not behave properly.' );
            }

            // eZCheckUser: pre check, RequireUserLogin & FORCE_LOGIN related so needs to be after session init
            if ( !isset( $check ) )
            {
                $check = eZUserLoginHandler::preCheck( $siteBasics, $uri );
            }

            ezpEvent::getInstance()->notify( 'request/input', array( $uri ) );

            // Initialize with locale settings
            $locale = eZLocale::instance();
            $languageCode = $locale->httpLocaleCode();
            //$phpLocale = trim( $ini->variable( 'RegionalSettings', 'SystemLocale' ) );
            //if ( $phpLocale != '' )
            //{
            //    setlocale( LC_ALL, explode( ',', $phpLocale ) );
            //}

            // send header information
            $headerList = array( 'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
                                 'Last-Modified' => gmdate( 'D, d M Y H:i:s' ) . ' GMT',
                                 'Cache-Control' => 'no-cache, must-revalidate',
                                 'Pragma' => 'no-cache',
                                 'X-Powered-By' => 'eZ Publish',
                                 'Content-Type' => 'text/html; charset=utf-8',
                                 'Served-by' => $_SERVER["SERVER_NAME"],
                                 'Content-language' => $languageCode );

            $site = array( 'title' => $ini->variable( 'SiteSettings', 'SiteName' ),
                           'design' => $ini->variable( 'DesignSettings', 'SiteDesign' ),
                           'http_equiv' => array( 'Content-Type' => 'text/html; charset=utf-8',
                                                  'Content-language' => $languageCode ) );


            $headerOverrideArray = eZHTTPHeader::headerOverrideArray( $uri );
            $headerList = array_merge( $headerList, $headerOverrideArray );
            foreach( $headerList as $key => $value )
            {
                header( $key . ': ' . $value );
            }

            // Read role settings
            $globalPolicyCheckOmitList = $ini->variable( 'RoleSettings', 'PolicyOmitList' );
            $siteBasics['policy-check-omit-list'] = array_merge( $siteBasics['policy-check-omit-list'], $globalPolicyCheckOmitList );
            $policyCheckViewMap = array();
            foreach ( $siteBasics['policy-check-omit-list'] as $omitItem )
            {
                $items = explode( '/', $omitItem );
                if ( count( $items ) > 1 )
                {
                    $module = $items[0];
                    $view = $items[1];
                    if ( !isset( $policyCheckViewMap[$module] ) )
                        $policyCheckViewMap[$module] = array();
                    $policyCheckViewMap[$module][] = $view;
                }
            }


            // Start the module loop
            while ( $siteBasics['module-run-required'] )
            {
                $objectHasMovedError = false;
                $objectHasMovedURI = false;
                $actualRequestedURI = $uri->uriString();

                // Extract user specified parameters
                $userParameters = $uri->userParameters();

                // Generate a URI which also includes the user parameters
                $completeRequestedURI = $uri->originalURIString();

                // Check for URL translation
                if ( $siteBasics['url-translator-allowed'] &&
                     eZURLAliasML::urlTranslationEnabledByUri( $uri ) )
                {
                    $translateResult = eZURLAliasML::translate( $uri );

                    if ( !is_string( $translateResult ) )
                    {
                        $useWildcardTranslation = $ini->variable( 'URLTranslator', 'WildcardTranslation' ) == 'enabled';
                        if ( $useWildcardTranslation )
                        {
                            $translateResult = eZURLWildcard::translate( $uri );
                        }
                    }

                    // Check if the URL has moved
                    if ( is_string( $translateResult ) )
                    {
                        $objectHasMovedURI = $translateResult;
                        foreach ( $userParameters as $name => $value )
                        {
                            $objectHasMovedURI .= '/(' . $name . ')/' . $value;
                        }

                        $objectHasMovedError = true;
                    }
                }

                if ( $uri->isEmpty() )
                {
                    $tmp_uri = new eZURI( $ini->variable( "SiteSettings", "IndexPage" ) );
                    $moduleCheck = eZModule::accessAllowed( $tmp_uri );
                }
                else
                {
                    $moduleCheck = eZModule::accessAllowed( $uri );
                }

                if ( !$moduleCheck['result'] )
                {
                    if ( $ini->variable( "SiteSettings", "ErrorHandler" ) == "defaultpage" )
                    {
                        $defaultPage = $ini->variable( "SiteSettings", "DefaultPage" );
                        $uri->setURIString( $defaultPage );
                        $moduleCheck['result'] = true;
                    }
                }

                $http = eZHTTPTool::instance();

                $displayMissingModule = false;
                $oldURI = $uri;

                if ( $uri->isEmpty() )
                {
                    if ( !fetchModule( $tmp_uri, $check, $module, $module_name, $function_name, $params ) )
                        $displayMissingModule = true;
                }
                else if ( !fetchModule( $uri, $check, $module, $module_name, $function_name, $params ) )
                {
                    if ( $ini->variable( "SiteSettings", "ErrorHandler" ) == "defaultpage" )
                    {
                        $tmp_uri = new eZURI( $ini->variable( "SiteSettings", "DefaultPage" ) );
                        if ( !fetchModule( $tmp_uri, $check, $module, $module_name, $function_name, $params ) )
                            $displayMissingModule = true;
                    }
                    else
                        $displayMissingModule = true;
                }

                if ( !$displayMissingModule &&
                     $moduleCheck['result'] &&
                     $module instanceof eZModule )
                {
                    // Run the module/function
                    eZDebug::addTimingPoint( "Module start '" . $module->attribute( 'name' ) . "'" );

                    $moduleAccessAllowed = true;
                    $omitPolicyCheck = true;
                    $runModuleView = true;

                    $availableViewsInModule = $module->attribute( 'views' );
                    if ( !isset( $availableViewsInModule[$function_name] )
                            && !$objectHasMovedError
                                && !isset( $module->Module['function']['script'] ) )
                    {
                        $moduleResult = $module->handleError( eZError::KERNEL_MODULE_VIEW_NOT_FOUND, 'kernel' );
                        $runModuleView = false;
                        $siteBasics['policy-check-required'] = false;
                        $omitPolicyCheck = true;
                    }

                    if ( $siteBasics['policy-check-required'] )
                    {
                        $omitPolicyCheck = false;
                        $moduleName = $module->attribute( 'name' );
                        $viewName = $function_name;
                        if ( in_array( $moduleName, $siteBasics['policy-check-omit-list'] ) )
                            $omitPolicyCheck = true;
                        else if ( isset( $policyCheckViewMap[$moduleName] ) and
                                  in_array( $viewName, $policyCheckViewMap[$moduleName] ) )
                            $omitPolicyCheck = true;
                    }
                    if ( !$omitPolicyCheck )
                    {
                        $currentUser = eZUser::currentUser();
                        $siteAccessResult = $currentUser->hasAccessTo( 'user', 'login' );

                        $hasAccessToSite = false;
                        if ( $siteAccessResult[ 'accessWord' ] == 'limited' )
                        {
                            $policyChecked = false;
                            foreach ( array_keys( $siteAccessResult['policies'] ) as $key )
                            {
                                $policy = $siteAccessResult['policies'][$key];
                                if ( isset( $policy['SiteAccess'] ) )
                                {
                                    $policyChecked = true;
                                    $crc32AccessName = eZSys::ezcrc32( $access[ 'name' ] );
                                    eZDebugSetting::writeDebug( 'kernel-siteaccess', $policy['SiteAccess'], $crc32AccessName );
                                    if ( in_array( $crc32AccessName, $policy['SiteAccess'] ) )
                                    {
                                        $hasAccessToSite = true;
                                        break;
                                    }
                                }
                                if ( $hasAccessToSite )
                                    break;
                            }
                            if ( !$policyChecked )
                                $hasAccessToSite = true;
                        }
                        else if ( $siteAccessResult[ 'accessWord' ] == 'yes' )
                        {
                            eZDebugSetting::writeDebug( 'kernel-siteaccess', "access is yes" );
                            $hasAccessToSite = true;
                        }
                        else if ( $siteAccessResult['accessWord'] == 'no' )
                        {
                            $accessList = $siteAccessResult['accessList'];
                        }

                        if ( $hasAccessToSite )
                        {
                            $accessParams = array();
                            $moduleAccessAllowed = $currentUser->hasAccessToView( $module, $function_name, $accessParams );
                            if ( isset( $accessParams['accessList'] ) )
                            {
                                $accessList = $accessParams['accessList'];
                            }
                        }
                        else
                        {
                            eZDebugSetting::writeDebug( 'kernel-siteaccess', $access, 'not able to get access to siteaccess' );
                            $moduleAccessAllowed = false;
                            $requireUserLogin = ( $ini->variable( "SiteAccessSettings", "RequireUserLogin" ) == "true" );
                            if ( $requireUserLogin )
                            {
                                $module = eZModule::exists( 'user' );
                                if ( $module instanceof eZModule )
                                {
                                    $moduleResult = $module->run( 'login', array(),
                                                                   array( 'SiteAccessAllowed' => false,
                                                                          'SiteAccessName' => $access['name'] ) );
                                    $runModuleView = false;
                                }
                            }
                        }
                    }

                    $GLOBALS['eZRequestedModule'] = $module;

                    if ( $runModuleView )
                    {
                        if ( $objectHasMovedError == true )
                        {
                            $moduleResult = $module->handleError( eZError::KERNEL_MOVED, 'kernel', array( 'new_location' => $objectHasMovedURI ) );
                        }
                        else if ( !$moduleAccessAllowed )
                        {
                            if ( isset( $availableViewsInModule[$function_name][ 'default_navigation_part' ] ) )
                            {
                                $defaultNavigationPart = $availableViewsInModule[$function_name][ 'default_navigation_part' ];
                            }

                            if ( isset( $accessList ) )
                                $moduleResult = $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel', array( 'AccessList' => $accessList ) );
                            else
                                $moduleResult = $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );

                            if ( isset( $defaultNavigationPart ) )
                            {
                                $moduleResult['navigation_part'] = $defaultNavigationPart;
                                unset( $defaultNavigationPart );
                            }
                        }
                        else
                        {
                            if ( !isset( $userParameters ) )
                            {
                                $userParameters = false;
                            }

                            // Check if we should switch access mode (http/https) for this module view.
                            eZSSLZone::checkModuleView( $module->attribute( 'name' ), $function_name );

                            $moduleResult = $module->run( $function_name, $params, false, $userParameters );

                            if ( $module->exitStatus() == eZModule::STATUS_FAILED and
                                 $moduleResult == null )
                                $moduleResult = $module->handleError( eZError::KERNEL_MODULE_VIEW_NOT_FOUND, 'kernel', array( 'module' => $module_name,
                                                                                                                               'view' => $function_name ) );
                        }
                    }
                }
                else if ( $moduleCheck['result'] )
                {
                    eZDebug::writeError( "Undefined module: $module_name", "index" );
                    $module = new eZModule( "", "", $module_name );
                    $GLOBALS['eZRequestedModule'] = $module;
                    $moduleResult = $module->handleError( eZError::KERNEL_MODULE_NOT_FOUND, 'kernel', array( 'module' => $module_name ) );
                }
                else
                {
                    if ( $moduleCheck['view_checked'] )
                        eZDebug::writeError( "View '" . $moduleCheck['view'] . "' in module '" . $moduleCheck['module'] . "' is disabled", "index" );
                    else
                        eZDebug::writeError( "Module '" . $moduleCheck['module'] . "' is disabled", "index" );
                    $module = new eZModule( "", "", $moduleCheck['module'] );
                    $GLOBALS['eZRequestedModule'] = $module;
                    $moduleResult = $module->handleError( eZError::KERNEL_MODULE_DISABLED, 'kernel', array( 'check' => $moduleCheck ) );
                }
                $siteBasics['module-run-required'] = false;
                if ( $module->exitStatus() == eZModule::STATUS_RERUN )
                {
                    if ( isset( $moduleResult['rerun_uri'] ) )
                    {
                        $uri = eZURI::instance( $moduleResult['rerun_uri'] );
                        $siteBasics['module-run-required'] = true;
                    }
                    else
                        eZDebug::writeError( 'No rerun URI specified, cannot continue', 'index.php' );
                }

                if ( is_array( $moduleResult ) )
                {
                    if ( isset( $moduleResult["pagelayout"] ) )
                    {
                        $siteBasics['show-page-layout'] = $moduleResult["pagelayout"];
                        $GLOBALS['eZCustomPageLayout'] = $moduleResult["pagelayout"];
                    }
                    if ( isset( $moduleResult["external_css"] ) )
                        $siteBasics['external-css'] = $moduleResult["external_css"];
                }
            }


            /**
             * Ouput an is_logged_in cookie when users are logged in for use by http cache soulutions.
             *
             * @deprecated As of 4.5, since 4.4 added lazy session support (init on use)
             */
            if ( $ini->variable( "SiteAccessSettings", "CheckValidity" ) !== 'true' )
            {
                $currentUser = eZUser::currentUser();

                $wwwDir = eZSys::wwwDir();
                // On host based site accesses this can be empty, causing the cookie to be set for the current dir,
                // but we want it to be set for the whole eZ publish site
                $cookiePath = $wwwDir != '' ? $wwwDir : '/';

                if ( $currentUser->isLoggedIn() )
                {
                    // Only set the cookie if it doesnt exist. This way we are not constantly sending the set request in the headers.
                    if ( !isset( $_COOKIE['is_logged_in'] ) || $_COOKIE['is_logged_in'] != 'true' )
                    {
                        setcookie( 'is_logged_in', 'true', 0, $cookiePath );
                    }
                }
                else if ( isset( $_COOKIE['is_logged_in'] ) )
                {
                    setcookie( 'is_logged_in', false, 0, $cookiePath );
                }
            }

            if ( $module->exitStatus() == eZModule::STATUS_REDIRECT )
            {
                $GLOBALS['eZRedirection'] = true;
                $ini = eZINI::instance();
                $automatic_redir = true;

                if ( $GLOBALS['eZDebugAllowed'] && ( $redirUri = $ini->variable( 'DebugSettings', 'DebugRedirection' ) ) != 'disabled' )
                {
                    if ( $redirUri == "enabled" )
                    {
                        $automatic_redir = false;
                    }
                    else
                    {
                        $redirUris = $ini->variableArray( "DebugSettings", "DebugRedirection" );
                        $uri = eZURI::instance( eZSys::requestURI() );
                        $uri->toBeginning();
                        foreach ( $redirUris as $redirUri )
                        {
                            $redirUri = new eZURI( $redirUri );
                            if ( $redirUri->matchBase( $uri ) )
                            {
                                $automatic_redir = false;
                                break;
                            }
                        }
                    }
                }

                $redirectURI = eZSys::indexDir();

                $moduleRedirectUri = $module->redirectURI();
                $redirectStatus = $module->redirectStatus();
                $translatedModuleRedirectUri = $moduleRedirectUri;
                if ( $ini->variable( 'URLTranslator', 'Translation' ) == 'enabled' &&
                     eZURLAliasML::urlTranslationEnabledByUri( new eZURI( $moduleRedirectUri ) ) )
                {
                    if ( eZURLAliasML::translate( $translatedModuleRedirectUri, true ) )
                    {
                        $moduleRedirectUri = $translatedModuleRedirectUri;
                        if ( strlen( $moduleRedirectUri ) > 0 and
                             $moduleRedirectUri[0] != '/' )
                            $moduleRedirectUri = '/' . $moduleRedirectUri;
                    }
                }

                if ( preg_match( '#^(\w+:)|^//#', $moduleRedirectUri ) )
                {
                    $redirectURI = $moduleRedirectUri;
                }
                else
                {
                    $leftSlash = false;
                    $rightSlash = false;
                    if ( strlen( $redirectURI ) > 0 and
                         $redirectURI[strlen( $redirectURI ) - 1] == '/' )
                        $leftSlash = true;
                    if ( strlen( $moduleRedirectUri ) > 0 and
                         $moduleRedirectUri[0] == '/' )
                        $rightSlash = true;

                    if ( !$leftSlash and !$rightSlash ) // Both are without a slash, so add one
                        $moduleRedirectUri = '/' . $moduleRedirectUri;
                    else if ( $leftSlash and $rightSlash ) // Both are with a slash, so we remove one
                        $moduleRedirectUri = substr( $moduleRedirectUri, 1 );
                    $redirectURI .= $moduleRedirectUri;
                }

                eZStaticCache::executeActions();

                eZDB::checkTransactionCounter();

                if ( $automatic_redir )
                {
                    eZHTTPTool::redirect( $redirectURI, array(), $redirectStatus );
                }
                else
                {
                    // Make sure any errors or warnings are reported
                    if ( $ini->variable( 'DebugSettings', 'DisplayDebugWarnings' ) == 'enabled' )
                    {
                        if ( isset( $GLOBALS['eZDebugError'] ) and
                             $GLOBALS['eZDebugError'] )
                        {
                            eZAppendWarningItem( array( 'error' => array( 'type' => 'error',
                                                                          'number' => 1,
                                                                          'count' => $GLOBALS['eZDebugErrorCount'] ),
                                                        'identifier' => 'ezdebug-first-error',
                                                        'text' => ezpI18n::tr( 'index.php', 'Some errors occurred, see debug for more information.' ) ) );
                        }

                        if ( isset( $GLOBALS['eZDebugWarning'] ) and
                             $GLOBALS['eZDebugWarning'] )
                        {
                            eZAppendWarningItem( array( 'error' => array( 'type' => 'warning',
                                                                          'number' => 1,
                                                                          'count' => $GLOBALS['eZDebugWarningCount'] ),
                                                        'identifier' => 'ezdebug-first-warning',
                                                        'text' => ezpI18n::tr( 'index.php', 'Some general warnings occured, see debug for more information.' ) ) );
                        }
                    }

                    $tpl = eZTemplate::factory();
                    if ( empty( $warningList ) )
                        $warningList = false;

                    $tpl->setVariable( 'site', $site );
                    $tpl->setVariable( 'warning_list', $warningList );
                    $tpl->setVariable( 'redirect_uri', eZURI::encodeURL( $redirectURI ) );
                    $templateResult = $tpl->fetch( 'design:redirect.tpl' );

                    eZDebug::addTimingPoint( "Script end" );

                    eZDisplayResult( $templateResult );
                }

                eZExecution::cleanExit();
            }

            // Store the last URI for access history for login redirection
            // Only if user has session and only if there was no error or no redirects happen
            if ( eZSession::hasStarted() &&
                $module->exitStatus() == eZModule::STATUS_OK )
            {
                $currentURI = $completeRequestedURI;
                if ( strlen( $currentURI ) > 0 and $currentURI[0] != '/' )
                    $currentURI = '/' . $currentURI;

                $lastAccessedURI = "";
                $lastAccessedViewURI = "";

                $http = eZHTTPTool::instance();

                // Fetched stored session variables
                if ( $http->hasSessionVariable( "LastAccessesURI" ) )
                {
                    $lastAccessedViewURI = $http->sessionVariable( "LastAccessesURI" );
                }
                if ( $http->hasSessionVariable( "LastAccessedModifyingURI" ) )
                {
                    $lastAccessedURI = $http->sessionVariable( "LastAccessedModifyingURI" );
                }

                // Update last accessed view page
                if ( $currentURI != $lastAccessedViewURI and
                     !in_array( $module->uiContextName(), array( 'edit', 'administration', 'browse', 'authentication' ) ) )
                {
                    $http->setSessionVariable( "LastAccessesURI", $currentURI );
                }

                // Update last accessed non-view page
                if ( $currentURI != $lastAccessedURI )
                {
                    $http->setSessionVariable( "LastAccessedModifyingURI", $currentURI );
                }
            }


            eZDebug::addTimingPoint( "Module end '" . $module->attribute( 'name' ) . "'" );
            if ( !is_array( $moduleResult ) )
            {
                eZDebug::writeError( 'Module did not return proper result: ' . $module->attribute( 'name' ), 'index.php' );
                $moduleResult = array();
                $moduleResult['content'] = false;
            }

            if ( !isset( $moduleResult['ui_context'] ) )
            {
                $moduleResult['ui_context'] = $module->uiContextName();
            }
            $moduleResult['ui_component'] = $module->uiComponentName();

            $templateResult = null;

            eZDebug::setUseExternalCSS( $siteBasics['external-css'] );
            if ( $siteBasics['show-page-layout'] )
            {
                $tpl = eZTemplate::factory();
                if ( $tpl->hasVariable( 'node' ) )
                    $tpl->unsetVariable( 'node' );

                if ( !isset( $moduleResult['path'] ) )
                    $moduleResult['path'] = false;
                $moduleResult['uri'] = eZSys::requestURI();

                $tpl->setVariable( "module_result", $moduleResult );

                $meta = $ini->variable( 'SiteSettings', 'MetaDataArray' );

                if ( !isset( $meta['description'] ) )
                {
                    $metaDescription = "";
                    if ( isset( $moduleResult['path'] ) and
                         is_array( $moduleResult['path'] ) )
                    {
                        foreach ( $moduleResult['path'] as $pathPart )
                        {
                            if ( isset( $pathPart['text'] ) )
                                $metaDescription .= $pathPart['text'] . " ";
                        }
                    }
                    $meta['description'] = $metaDescription;
                }

                $site['uri'] = $oldURI;
                $site['redirect'] = false;
                $site['meta'] = $meta;
                $site['version'] = eZPublishSDK::version();
                $site['page_title'] = $module->title();

                $tpl->setVariable( "site", $site );

                if ( $ini->variable( 'DebugSettings', 'DisplayDebugWarnings' ) == 'enabled' )
                {
                    // Make sure any errors or warnings are reported
                    if ( isset( $GLOBALS['eZDebugError'] ) and
                         $GLOBALS['eZDebugError'] )
                    {
                        \eZAppendWarningItem( array( 'error' => array( 'type' => 'error',
                                                                      'number' => 1 ,
                                                                      'count' => $GLOBALS['eZDebugErrorCount'] ),
                                                    'identifier' => 'ezdebug-first-error',
                                                    'text' => ezpI18n::tr( 'index.php', 'Some errors occurred, see debug for more information.' ) ) );
                    }

                    if ( isset( $GLOBALS['eZDebugWarning'] ) and
                         $GLOBALS['eZDebugWarning'] )
                    {
                        \eZAppendWarningItem( array( 'error' => array( 'type' => 'warning',
                                                                      'number' => 1,
                                                                      'count' => $GLOBALS['eZDebugWarningCount'] ),
                                                    'identifier' => 'ezdebug-first-warning',
                                                    'text' => ezpI18n::tr( 'index.php', 'Some general warnings occured, see debug for more information.' ) ) );
                    }
                }

                if ( $siteBasics['user-object-required'] )
                {
                    $currentUser = eZUser::currentUser();

                    $tpl->setVariable( "current_user", $currentUser );
                    $tpl->setVariable( "anonymous_user_id", $ini->variable( 'UserSettings', 'AnonymousUserID' ) );
                }
                else
                {
                    $tpl->setVariable( "current_user", false );
                    $tpl->setVariable( "anonymous_user_id", false );
                }

                $tpl->setVariable( "access_type", $access );

                if ( empty( $warningList ) )
                    $warningList = false;

                $tpl->setVariable( 'warning_list', $warningList );

                $resource = "design:";
                if ( is_string( $siteBasics['show-page-layout'] ) )
                {
                    if ( strpos( $siteBasics['show-page-layout'], ":" ) !== false )
                    {
                        $resource = "";
                    }
                }
                else
                {
                    $siteBasics['show-page-layout'] = "pagelayout.tpl";
                }

                // Set the navigation part
                // Check for navigation part settings
                $navigationPartString = 'ezcontentnavigationpart';
                if ( isset( $moduleResult['navigation_part'] ) )
                {
                    $navigationPartString = $moduleResult['navigation_part'];

                    // Fetch the navigation part
                }
                $navigationPart = eZNavigationPart::fetchPartByIdentifier( $navigationPartString );

                $tpl->setVariable( 'navigation_part', $navigationPart );
                $tpl->setVariable( 'uri_string', $uri->uriString() );
                if ( isset( $moduleResult['requested_uri_string'] ) )
                {
                    $tpl->setVariable( 'requested_uri_string', $moduleResult['requested_uri_string'] );
                }
                else
                {
                    $tpl->setVariable( 'requested_uri_string', $actualRequestedURI );
                }

                // Set UI context and component
                $tpl->setVariable( 'ui_context', $moduleResult['ui_context'] );
                $tpl->setVariable( 'ui_component', $moduleResult['ui_component'] );

                $templateResult = $tpl->fetch( $resource . $siteBasics['show-page-layout'] );
            }
            else
            {
                $templateResult = $moduleResult['content'];
            }

            eZDebug::addTimingPoint( "Script end" );

            $out = ob_get_clean();
            echo trim( $out );

            eZDB::checkTransactionCounter();

            eZDisplayResult( $templateResult );

            eZExecution::cleanup();
            eZExecution::setCleanExit();
        }
        /**
         * @param string $template
         * @param array $params
         */
        public function legacyInclude( $template, array $params = array() )
        {

        }
    }
}//namespace HiMVC\Core\Legacy


/**
 * Some global functions that seems to be needed by legacy kernel.
 */
namespace {
    require_once( __DIR__ . '/eZSession.php' );
    /**
     * @deprecated Since 5.0
     * @return null
     */
    function eZDisplayDebug()
    {
        $ini = eZINI::instance();

        if ( $ini->variable( 'DebugSettings', 'DebugOutput' ) != 'enabled' )
            return null;

        $scriptStopTime = microtime( true );

        $type = $ini->variable( "DebugSettings", "Debug" );
        //eZDebug::setHandleType( eZDebug::HANDLE_NONE );
        if ( $type == "inline" or $type == "popup" )
        {
            $as_html = true;

            if ( $ini->variable( "DebugSettings", "DebugToolbar" ) == 'enabled' &&
                 $ini->variable( "SiteAccessSettings", "CheckValidity" ) !== 'true' &&
                 $as_html == true &&
                 !$GLOBALS['eZRedirection'] )

            {
                $tpl = eZTemplate::factory();
                $result = "<tr><td>" . $tpl->fetch( 'design:setup/debug_toolbar.tpl' ) . "</td></tr>";
                eZDebug::appendTopReport( "Debug toolbar", $result );
            }

            eZDebug::appendBottomReport( 'Template Usage Statistics', eZTemplatesStatisticsReporter::generateStatistics( $as_html ) );

            eZDebug::setScriptStop( $scriptStopTime );
            return eZDebug::printReport( $type == "popup", $as_html, true, false, true,
                true, $ini->variable( "DebugSettings", "DisplayIncludedFiles" ) == 'enabled' );
        }
        return null;
    }


    /**
     * @deprecated Since 5.0
     * @param string|null $templateResult
     */
    function eZDisplayResult( $templateResult )
    {
        ob_start();
        if ( $templateResult !== null )
        {
            $classname = eZINI::instance()->variable( "OutputSettings", "OutputFilterName" );//deprecated
            if( !empty( $classname ) && class_exists( $classname ) )
            {
                $templateResult = call_user_func( array ( $classname, 'filter' ), $templateResult );
            }
            $templateResult = ezpEvent::getInstance()->filter( 'response/preoutput', $templateResult );
            $debugMarker = '<!--DEBUG_REPORT-->';
            $pos = strpos( $templateResult, $debugMarker );
            if ( $pos !== false )
            {
                $debugMarkerLength = strlen( $debugMarker );
                echo substr( $templateResult, 0, $pos );
                eZDisplayDebug();
                echo substr( $templateResult, $pos + $debugMarkerLength );
            }
            else
            {
                echo $templateResult, eZDisplayDebug();
            }
        }
        else
        {
            eZDisplayDebug();
        }
        $fullPage = ob_get_clean();
        echo ezpEvent::getInstance()->filter( 'response/output', $fullPage );
    }

    /**
     * Reads settings from site.ini and passes them to eZDebug
     *
     * @deprecated Since 5.0
     */
    function eZUpdateDebugSettings()
    {
        $ini = eZINI::instance();

        $settings = array();
        list( $settings['debug-enabled'], $settings['debug-by-ip'], $settings['log-only'], $settings['debug-by-user'], $settings['debug-ip-list'], $logList, $settings['debug-user-list'] ) =
            $ini->variableMulti( 'DebugSettings',
                                 array( 'DebugOutput', 'DebugByIP', 'DebugLogOnly', 'DebugByUser', 'DebugIPList', 'AlwaysLog', 'DebugUserIDList' ),
                                 array( 'enabled', 'enabled', 'disabled', 'enabled' ) );
        $logMap = array( 'notice' => eZDebug::LEVEL_NOTICE,
                         'warning' => eZDebug::LEVEL_WARNING,
                         'error' => eZDebug::LEVEL_ERROR,
                         'debug' => eZDebug::LEVEL_DEBUG,
                         'strict' => eZDebug::LEVEL_STRICT );
        $settings['always-log'] = array();
        foreach ( $logMap as $name => $level )
        {
            $settings['always-log'][$level] = $logList !== null && in_array( $name, $logList );
        }
        eZDebug::updateSettings( $settings );
    }

    /**
     *Appends a new warning item to the warning list.
     * a $parameters must contain a \c error and \c text key.
     *
     * @deprecated Since 5.0
     * @param array $parameters
     */
    function eZAppendWarningItem( $parameters = array() )
    {
        global $warningList;
        $parameters = array_merge( array( 'error' => false,
                                          'text' => false,
                                          'identifier' => false ),
                                   $parameters );
        $error = $parameters['error'];
        $text = $parameters['text'];
        $identifier = $parameters['identifier'];
        $warningList[] = array( 'error' => $error,
                                'text' => $text,
                                'identifier' => $identifier );
    }

    /**
     * @deprecated Since 5.0
     * @param \eZURI $uri
     * @param null|array $check
     * @param null|\eZModule $module ByRef, will be set to a eZModule instace based on $module_name
     * @param string $module_name
     * @param string $function_name
     * @param array $params
     * @return bool
     */
    function fetchModule( eZURI $uri, $check, &$module, &$module_name, &$function_name, &$params )
    {
        $module_name = $uri->element();
        if ( $check !== null and isset( $check["module"] ) )
            $module_name = $check["module"];

        // Try to fetch the module object
        $module = eZModule::exists( $module_name );
        if ( !( $module instanceof eZModule ) )
        {
            return false;
        }

        $uri->increase();
        $function_name = "";
        if ( !$module->singleFunction() )
        {
            $function_name = $uri->element();
            $uri->increase();
        }
        // Override it if required
        if ( $check !== null and isset( $check["function"] ) )
            $function_name = $check["function"];

        $params = $uri->elements( false );
        return true;
    }
}
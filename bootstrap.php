<?php
/**
 * File containing the bootstrapping
 *
 * Returns instance of (Service) Container setup with configuration service, and setups autoloader.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */


use HiMVC\Core\Common\ClassLoader;
use HiMVC\Core\Common\Configuration;
use HiMVC\Core\Common\DependencyInjectionContainer as Container;
use eZ\Publish\Core\MVC\Legacy\Kernel as LegacyKernel;
use eZ\Publish\Core\MVC\Legacy\Kernel\CLIHandler as LegacyKernelCLI;


if ( !isset( $rootDir ) )
    $rootDir = __DIR__;

// Get global config.php settings
if ( !( $settings = include ( __DIR__ . '/config.php' ) ) )
{
    throw new \RuntimeException( 'Could not find config.php, please copy config.php-DEVELOPMENT to config.php & customize to your needs!' );
}

// 2. Setup autoloader(s)
require __DIR__ . '/HiMVC/Core/Common/ClassLoader.php';
$classLoader = new ClassLoader(
    $settings['ClassLoader']['Repositories'],
    $settings['ClassLoader']['Settings']
);
spl_autoload_register( array( $classLoader, 'load' ) );

// 3. Setup configuration
$configuration = new Configuration(
    'service',
    $settings['Configuration']['Parsers'],
    $settings['Configuration']['Paths'],
    $settings['Configuration']['Settings']
);
$configuration
    ->enableKeepParsedData( true )// Avoid re parsing files several times during bootstrap
    ->load();


// Bootstrap eZ Publish legacy kernel if configured
if ( !empty( $settings['service']['parameters']['legacy_dir'] ) )
{
    if ( !defined( 'EZCBASE_ENABLED' ) )
    {
        define( 'EZCBASE_ENABLED', false );
        require_once $settings['service']['parameters']['legacy_dir'] . '/autoload.php';
    }

    // Define $legacyKernelHandler to whatever you need before loading this bootstrap file.
    // CLI handler is used by defaut, but you must use \ezpKernelWeb if not in CLI context (i.e. REST server)
    // $legacyKernelHandler can be a closure returning the appropriate kernel handler (to avoid autoloading issues)
    if ( isset( $legacyKernelHandler ) )
    {
        $legacyKernelHandler = $legacyKernelHandler instanceof \Closure ? $legacyKernelHandler() : $legacyKernelHandler;
    }
    else if ( PHP_SAPI === 'cli' )
    {
        $legacyKernelHandler = new LegacyKernelCLI;
    }
    else
    {
        $legacyKernelHandler = new \ezpKernelWeb;
    }
    $legacyKernel = new LegacyKernel( $legacyKernelHandler, $settings['service']['parameters']['legacy_dir'], getcwd() );

    set_exception_handler( null );
    // Avoid "Fatal error" text from legacy kernel if not called
    $legacyKernel->runCallback(
        function ()
        {
            eZExecution::setCleanExit();
        }
    );

    // Exposing in env variables in order be able to use them in test cases.
    $_ENV['legacyKernel'] = $legacyKernel;
    $_ENV['legacyPath'] = $settings['service']['parameters']['legacy_dir'];
}


// 4. Setup Container
$container = new Container(
    $configuration->getAll(),
    array(
        '$indexFile' => (isset( $indexFile ) ? $indexFile : 'index.php'),
        '$rootDir' => $rootDir,
        '$classLoader' => $classLoader,
        '$configuration' => $configuration,
        '$cacheDirPermission' => $settings['Configuration']['Settings']['CacheDirPermission'],
        '$cacheFilePermission' => $settings['Configuration']['Settings']['CacheFilePermission'],
        '$useCache' => $settings['Configuration']['Settings']['UseCache'],
        '$developmentMode' => $settings['Configuration']['Settings']['DevelopmentMode'],
        'parameters' => $settings['service']['parameters'],
        '@legacyKernel' => $legacyKernel,
    )
);

// 5. Get Request and update configuration for access
$accessPaths = array();
$accessRelativePaths = array();
$request = $container->getRequest();
/**
 * @var \HiMVC\Core\MVC\AccessMatcher $accessMatcher
 */
$accessMatcher = $container->get( 'accessMatcher' );
foreach ( $accessMatcher->match( $request ) as $accessMatch )
{
    $accessRelativePaths[] = $accessRelativePath = "settings/access/{$accessMatch->type}/{$accessMatch->name}/";
    $accessPaths[] = $rootDir . '/' . $accessRelativePath;
    $request->access[] = $accessMatch;
}
$configuration->setDirs( $accessPaths, 'access' );
$container->setSettings( $configuration->reload()->getAll() );


// 6. Setup sessions now that access is setup
$request->session = $container->get( 'session' );


// 7. Setup modules
$modulePaths = array();
$moduleAccessPaths = array();
foreach ( $container->getModules() as $module )
{
    $request->modules[] = $module;
    $modulePaths[] = "{$rootDir}/{$module->path}/settings/";
    foreach ( $accessRelativePaths as $accessRelativePath )
    {
        $moduleAccessPaths[] = "{$rootDir}/{$module->path}/{$accessRelativePath}";
    }
}
$configuration->setDirs( $modulePaths, 'modules' );
$configuration->setDirs( $moduleAccessPaths, 'modulesAccess' );
$container->setSettings(
    $configuration
        ->reload()
        ->enableKeepParsedData( false )// Set setting back to default
        ->getAll()
);

// 8. Return ready configured container
return $container;

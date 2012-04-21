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


use HiMVC\Core\Base\ClassLoader,
    HiMVC\Core\Base\Configuration,
    HiMVC\Core\Base\DependencyInjectionContainer as Container;

if ( !isset( $rootDir ) )
    $rootDir = __DIR__;

// Read config.php
if ( !( $settings = include( $rootDir . '/config.php' ) ) )
{
    die( 'Could not find config.php, please copy config.php-DEVELOPMENT to config.php and customize to your needs!' );
}

// Setup autoloader(s)
require __DIR__ . '/HiMVC/Core/Base/ClassLoader.php';
$classLoader = new ClassLoader(
    $settings['ClassLoader']['Repositories'],
    $settings['ClassLoader']['Settings']
);
spl_autoload_register( array( $classLoader, 'load' ) );

// Setup configuration
$configuration = new Configuration(
    'service',
    $settings['Configuration']['Parsers'],
    $settings['Configuration']['Paths'],
    $settings['Configuration']['Settings']
);
$configuration
    ->enableKeepParsedData( true )// Avoid re parsing files several times during bootstrap
    ->load();

// Setup Container
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
    )
);

// Get Request and update configuration for access
$accessPaths = array();
$accessRelativePaths = array();
$request = $container->getRequest();
foreach ( $request->access as $accessMatch )
{
    $accessRelativePaths[] = $accessRelativePath = "settings/access/{$accessMatch->type}/{$accessMatch->name}/";
    $accessPaths[] = $rootDir . '/' . $accessRelativePath;
}
$configuration->setDirs( $accessPaths, 'access' );
$container->setSettings( $configuration->reload()->getAll() );


// Setup modules
$modulePaths = array();
$moduleAccessPaths = array();
foreach ( $container->getModules() as $module )
{
    $request->appendModule( $module );
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

// Return ready configured container
return $container;

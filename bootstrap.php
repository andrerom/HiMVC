<?php
/**
 * File containing the bootstrapping
 *
 * Returns instance of (Service) Container setup with configuration service, and setups autoloader.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */


use eZ\Publish\Core\Base\ClassLoader,
    HiMVC\Core\Base\Configuration,
    HiMVC\Core\Base\DependencyInjectionContainer as Container;

// Read config.php
if ( !( $settings = include( __DIR__ . '/config.php' ) ) )
{
    die( 'Could not find config.php, please copy config.php-DEVELOPMENT to config.php and customize to your needs!' );
}

// Setup autoloader(s)
require __DIR__ . '/ezpnext/eZ/Publish/Core/Base/ClassLoader.php';
$classLoader = new ClassLoader(
    $settings['ClassLoader']['Repositories'],
    $settings['ClassLoader']['Mode'],
    $settings['ClassLoader']['lazyClassLoaders']
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
    $accessPaths[] = __DIR__ . '/' . $accessRelativePath;
}
$configuration->setDirs( $accessPaths, 'access' );
$container->setSettings( $configuration->reload()->getAll() );


// Setup modules
$modulePaths = array();
$moduleAccessPaths = array();
foreach ( $container->getModules() as $module )
{
    if ( $module->path[0] !== '/' )
        $module->path = __DIR__ . '/' . $module->path;

    $request->appendModule( $module );

    $modulePaths[] = "{$module->path}/settings/";
    foreach ( $accessRelativePaths as $accessRelativePath )
    {
        $moduleAccessPaths[] = "{$module->path}/{$accessRelativePath}";
    }
}
$configuration->setDirs( $modulePaths, 'modules' );
$configuration->setDirs( $moduleAccessPaths, 'modulesAccess' );
$container->setSettings(
    $configuration
        ->enableKeepParsedData( false )// Set setting back to sane default, as a bonus parsed data is cleared on reload
        ->reload()
        ->getAll()
);

// Return ready configured container
return $container;

<?php
/**
 * File containing the Configuration class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 *
 * @uses ezcPhpGenerator To generate INI cache
 */

namespace HiMVC\Core\Common;

use eZ\Publish\Core\Base\Configuration\Parser;
use eZ\Publish\Core\Base\Exceptions\BadConfiguration;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue;
use ezcPhpGenerator;

/**
 * Configuration instance class
 *
 * A configuration class with override setting support that uses parsers to deal with
 * files so you can support ini/yaml/xml/json given it is defined when setting up the class.
 *
 * By default values are cached to a raw php files and files are not read again unless
 * development mode is on and some file has been removed or modified since cache was created.
 *
 * @uses \ezcPhpGenerator When generating cache files.
 */
class Configuration
{
    /**
     * Constant path to directory for configuration cache
     *
     * @var string
     */
    const DEFAULT_CONFIG_CACHE_DIR = 'var/cache/configuration/';

    /**
     * Constant string used as a temporary unset variable during ini parsing
     *
     * @var string
     */
    const TEMP_INI_UNSET_VAR = '__UNSET__';

    /**
     * Constant set in configuration file to know when configuration was last parsed
     *
     * Usefull for getting hint about when configuration has changed withouth having to create
     * a unique hash based on configuration values. In other words for syncing caches that depend
     * on configuration.
     *
     * @var string
     */
    const TEMP_INI_PARSETIME_VAR = '__PARSETIME__';

    /**
     * Constant integer to check against configuration cache format revision
     *
     * @var int
     */
    const CONFIG_CACHE_REV = 3;

    /**
     * The instance path array, scoped in the order they should be parsed
     *
     * @var array
     */
    private $paths = array();

    /**
     * The instance configuration path array md5 hash, for use in cache names.
     * Empty if it needs to be regenerated
     *
     * @var string
     */
    private $pathsHash = '';

    /**
     * The instance module name, set by {@link __construct()}
     *
     * @var string
     */
    protected $name;

    /**
     * The in memory representation of the current raw configuration data.
     *
     * @var null|array
     */
    protected $raw = null;

    /**
     * Hash of parsers where key defines the file suffix and value is class name
     *
     * @var array
     */
    protected $parsers;

    /**
     * Hash of parsed data from parsers
     *
     * @var array Array value of false, means file is not there
     */
    protected $parsedData;

    /**
     * Configuration object settings (see config.php['Configurtation'])
     *
     * Default values can be seen in __construct()
     *
     * @var array
     */
    protected $settings;

    /**
     * Create instance of Configuration
     *
     * @param string $name The name of the module (and in case of ini files, same as ini filename w/o suffix)
     * @param array $parsers Hash of parsers where key defines the file suffix and value is class name
     * @param array $paths Paths to look for settings in.
     * @param array $settings Settings for Configuration and parsers
     */
    public function __construct( $name = 'service',
                                 array $parsers,
                                 array $paths = array(
                                      'base' => array(
                                          'eZ/Publish/Core/settings/',
                                          'HiMVC/Core/Common/settings/',
                                          'HiMVC/Core/MVC/settings/',
                                      ),
                                      'modules' => array(),
                                      'access' => array(),
                                      'global' => array( 'settings/override/' ),
                                 ),
                                 array $settings = array() )
    {
        $this->name = $name;
        $this->paths = $paths;
        $this->parsers = $parsers;
        $this->settings = $settings + array(
            'CacheFilePermission' => 0644,
            'CacheDirPermission' => 0755,
            'UseCache' => false,
            'DevelopmentMode' => false,
            'KeepParsedData' => false,
            'CacheDir' => self::DEFAULT_CONFIG_CACHE_DIR,
        );
    }

    /**
     * Get raw instance override path list data.
     *
     * @throws InvalidArgumentValue If scope has wrong value
     * @param string $scope See {@link $paths} for scope values (first level keys)
     *
     * @return array
     */
    public function getDirs( $scope = null )
    {
        if ( $scope === null )
            return $this->paths;
        if ( !isset( $this->paths[$scope] ) )
            throw new InvalidArgumentValue( 'scope', $scope, get_class( $this ) );

        return $this->paths[$scope];
    }

    /**
     * Set raw override path list data.
     *
     * Note: Full reset of Configuration instances are done when this function is called.
     *
     * @throws InvalidArgumentValue If scope has wrong value
     * @param array $paths
     * @param string $scope See {@link $globalPaths} for scope values (first level keys)
     * @return bool Return true if paths actually changed, and thus instances where reset.
     */
    public function setDirs( array $paths, $scope = null )
    {
        if ( $scope === null )
        {
            if ( $this->paths === $paths )
                return false;
            $this->paths = $paths;
        }
        else if ( !isset( $this->paths[$scope] ) )
        {
            throw new InvalidArgumentValue( 'scope', $scope, get_class( $this ) );
        }
        else if ( $this->paths[$scope] === $paths )
        {
            return false;
        }
        else
        {
            $this->paths[$scope] = $paths;
        }

        $this->pathsHash = '';
        return true;
    }

    /**
     * Enable/disable setting 'KeepParsedData'
     *
     * @param bool $value
     * @return \HiMVC\Core\Common\Configuration
     */
    public function enableKeepParsedData( $value )
    {
        if ( $value === false )
            $this->parsedData = array();

        $this->settings['KeepParsedData'] = $value;
        return $this;
    }

    /**
     * Get cache hash based on override dirs
     *
     * @return string md5 hash
     */
    protected function pathsHash()
    {
        if ( $this->pathsHash === '' )
        {
            $this->pathsHash = md5( serialize( $this->paths ) );
        }
        return $this->pathsHash;
    }

    /**
     * Reload cache data conditionally if path hash has changed on current instance
     *
     * @return \HiMVC\Core\Common\Configuration
     */
    public function reload()
    {
        if ( !isset( $this->raw['hash'] ) || $this->raw['hash'] !== $this->pathsHash() )
            $this->load();
        return $this;
    }

    /**
     * Loads the configuration from cache or from source (if $useCache is false or there is no cache)
     *
     * @param boolean|null $hasCache Lets you specify if there is a cache file, will check if null and $useCache is true
     * @param boolean $useCache Will skip using cached config files (slow), when null depends on [ini]\use-cache setting
     * @return \HiMVC\Core\Common\Configuration
     */
    public function load( $hasCache = null, $useCache = null )
    {
        $cacheName = $this->createCacheName( $this->pathsHash() );
        if ( $useCache === null )
        {
            $useCache = $this->settings['UseCache'];
        }

        if ( $hasCache === null && $useCache )
        {
            $hasCache = is_file( "{$this->settings['CacheDir']}configuration/{$cacheName}.php" );
        }

        if ( $hasCache && $useCache )
        {
            $this->raw = $this->readCache( $cacheName );
            $hasCache = $this->raw !== null;
        }

        if ( !$hasCache )
        {
            $sourceFiles = array();
            $configurationData = $this->parse( $this->getDirs(), $sourceFiles );
            $this->raw = $this->generateRawData( $this->pathsHash(), $configurationData, $sourceFiles, $this->getDirs() );

            if ( $useCache )
            {
                $this->storeCache( $cacheName, $this->raw );
            }
        }
        return $this;
    }

    /**
     * Create cache name.
     *
     * @param string $configurationPathsHash
     *
     * @return string
     */
    protected function createCacheName( $configurationPathsHash )
    {
        return $this->name . '-' . $configurationPathsHash;
    }

    /**
     * Loads cache file, use {@link is_file()} to make sure it exists first!
     *
     * @param string $cacheName As generated by {@link createCacheName()}
     *
     * @return array|null
     */
    protected function readCache( $cacheName )
    {
        $cacheData = include( "{$this->settings['CacheDir']}configuration/{$cacheName}.php" );

        // Check that cache has
        if ( !isset( $cacheData['data'] ) || $cacheData['rev'] !== self::CONFIG_CACHE_REV )
        {
            return null;
        }

        // Check modified time if dev mode
        if ( $this->settings['DevelopmentMode'] )
        {
            $currentTime = time();
            foreach ( $cacheData['files'] as $inputFile )
            {
                $fileTime = is_file( $inputFile ) ? filemtime( $inputFile ) : false;
                // Refresh cache & input files if file is gone
                if ( $fileTime === false )
                {
                    return null;
                }
                if ( $fileTime > $currentTime )
                {
                    trigger_error( __METHOD__ . ': Input file "' . $inputFile . '" has a timestamp higher then current time, ignoring to avoid infinite recursion!', E_USER_WARNING );
                }
                // Refresh cache if file has been changed
                else if ( $fileTime > $cacheData['created'] )
                {
                    return null;
                }
            }
        }
        return $cacheData;
    }

    /**
     * Generate raw data for use in cache
     *
     * @param string $configurationPathsHash
     * @param array $configurationData
     * @param array $sourceFiles Optional, stored in cache to be able to check modified time in future devMode
     * @param array $sourcePaths Optional, stored in cache to be able to debug it more easily
     *
     * @return array
     */
    protected function generateRawData( $configurationPathsHash, array $configurationData, array $sourceFiles = array(), array $sourcePaths = array() )
    {
        return array(
            'hash' => $configurationPathsHash,
            'paths' => $sourcePaths,
            'files' => $sourceFiles,
            'data' => $configurationData,
            'created' => time(),
            'rev' => self::CONFIG_CACHE_REV,
        );
    }

    /**
     * Parse configuration files
     *
     * Uses configured parsers to do the file parsing pr file, and then merges the result from them and:
     * - Handles array clearing
     * - Handles section extends ( "Section:Base" extends "Base" )
     *
     * @param array $configurationPaths
     * @param array $sourceFiles ByRef value or source files that has been/is going to be parsed
     *                           files you pass in will not be checked if they exists.
     * @throws \eZ\Publish\Core\Base\Exceptions\BadConfiguration If no parser have been defined
     *
     * @return array Data structure for parsed ini files
     */
    protected function parse( array $configurationPaths, array &$sourceFiles )
    {
        if ( empty( $this->parsers ) )
        {
            throw new BadConfiguration( 'base\[Configuration]\Parsers', 'Could not parse configuration files' );
        }

        foreach ( $configurationPaths as $scopeArray )
        {
            foreach ( $scopeArray as $settingsDir )
            {
                foreach ( $this->parsers as $suffix => $parser )
                {
                    $fileName = $settingsDir . $this->name . $suffix;
                    if ( isset( $sourceFiles[$fileName] ) )
                    {
                        continue;
                    }
                    else if ( isset( $this->parsedData[$fileName] ) )
                    {
                        if ( $this->parsedData[$fileName] !== false )
                            $sourceFiles[$fileName] = $suffix;
                    }
                    else if ( is_file( $fileName ) )
                    {
                        $sourceFiles[$fileName] = $suffix;
                    }
                    else
                    {
                        $this->parsedData[$fileName] = false;
                    }
                }
            }
        }

        // No source files, no configuration
        if ( empty( $sourceFiles ) )
        {
            return array();
        }

        $configurationData = array();
        $configurationFileData = array();
        foreach ( $sourceFiles as $fileName => $suffix )
        {
            if ( isset( $this->parsedData[$fileName] ) )
            {
                $configurationFileData[$fileName] = $this->parsedData[$fileName];
                continue;
            }

            if ( !$this->parsers[$suffix] instanceof Parser )
            {
                $this->parsers[$suffix] = new $this->parsers[$suffix]( $this->settings );
            }

            $configurationFileData[$fileName] = $this->parsers[$suffix]->parse( $fileName, file_get_contents( $fileName ) );
            $this->parsedData[$fileName] = $configurationFileData[$fileName];
        }

        // Post processing actions @see recursivePostParseActions()
        $extendedConfigurationFileData = array();
        foreach ( $configurationFileData as $fileName => $data )
        {
            foreach ( $data as $section => $sectionArray )
            {
                // Leave settings that extend others for second pass, key by depth
                if ( ( $count = substr_count( $section, ':' ) ) !== 0 )
                {
                    $extendedConfigurationFileData[$count][$fileName][$section] = $sectionArray;
                    continue;
                }

                if ( !isset( $configurationData[$section] ) )
                    $configurationData[$section] = array();

                $this->recursivePostParseActions( $sectionArray, $configurationData[$section] );
            }
        }

        // Second pass post processing dealing with settings that extends others
        ksort( $extendedConfigurationFileData, SORT_NUMERIC );
        foreach ( $extendedConfigurationFileData as $configurationFileData )
        {
            foreach ( $configurationFileData as $data )
            {
                foreach ( $data as $section => $sectionArray )
                {
                    if ( !isset( $configurationData[$section] ) )
                    {
                        $parent = substr( $section, stripos( $section, ':' ) + 1 );
                        if ( isset( $configurationData[$parent] ) )
                            $configurationData[$section] = $configurationData[$parent];
                        else
                            $configurationData[$section] = array();
                    }

                    $this->recursivePostParseActions( $sectionArray, $configurationData[$section] );
                }
            }
        }

        if ( !$this->settings['KeepParsedData'] )
            $this->parsedData = array();

        return $configurationData;
    }

    /**
     * Recursively look for constant that needs to be dealt with
     *
     * - Post processing to unset array self::TEMP_INI_UNSET_VAR values as set by parser to indicate array clearing
     * and to merge configuration data from all configuration files
     * - Change values of self::TEMP_INI_PARSETIME_VAR to unix time
     *
     * @param array $iniArray
     * @param array|null $configurationPiece
     */
    protected function recursivePostParseActions( array $iniArray, &$configurationPiece )
    {
        foreach ( $iniArray as $setting => $settingValue )
        {
            if ( $settingValue === self::TEMP_INI_PARSETIME_VAR )
            {
                $configurationPiece[$setting] = time();
            }
            else if ( isset( $settingValue[0] ) && $settingValue[0] === self::TEMP_INI_UNSET_VAR )
            {
                array_shift( $settingValue );
                $configurationPiece[$setting] = $settingValue;
            }
            else if ( is_array( $settingValue ) )
            {
                $this->recursivePostParseActions( $settingValue, $configurationPiece[$setting] );
            }
            else
            {
                $configurationPiece[$setting] = $settingValue;
            }
        }

    }

    /**
     * Store cache file, overwrites any existing file
     *
     * @param string $cacheName As generated by {@link createCacheName()}
     * @param array $rawData As generated by {@link generateRawData()}
     */
    protected function storeCache( $cacheName, array $rawData )
    {
        try
        {
            // Create ini dir if it does not exist
            if ( !is_dir( "{$this->settings['CacheDir']}configuration/" ) )
            {
                mkdir( "{$this->settings['CacheDir']}configuration/", $this->settings['CacheDirPermission'], true );
            }

            // Create cache hash
            $cachedFile = "{$this->settings['CacheDir']}configuration/{$cacheName}.php";

            // Store cache
            $generator = new ezcPhpGenerator( $cachedFile );
            $generator->appendComment( "This file is auto generated based on configuration files for '{$this->name}' module. Do not edit!" );
            $generator->appendComment( "Time created (server time): " . date( DATE_RFC822, $rawData['created'] ) );
            $generator->appendEmptyLines();

            $generator->appendValueAssignment( 'cacheData', $rawData );
            $generator->appendCustomCode( 'return $cacheData;' );

            $generator->finish();

            // make sure file has correct file permissions
            chmod( $cachedFile, $this->settings['CacheFilePermission'] );
        }
        catch ( Exception $e )
        {
            // constructor     : ezcBaseFileNotFoundException or ezcBaseFilePermissionException
            // all other calls : ezcPhpGeneratorException
            trigger_error( __METHOD__ . ': '. $e->getMessage(), E_USER_WARNING );
        }
    }

    /**
     * Gets a configuration value, or $fallBackValue if undefined
     * Triggers warning if key is not set and $fallBackValue is null
     *
     * @param string $section The configuration section to get value for
     * @param string $key The configuration key to get value for
     * @param mixed $fallBackValue value to return if setting is undefined.
     *
     * @return mixed|null (null if key is undefined and no $fallBackValue is provided)
     */
    public function get( $section, $key, $fallBackValue = null )
    {
        if ( isset( $this->raw['data'][$section][$key] ) )
        {
            return $this->raw['data'][$section][$key];
        }
        if ( $fallBackValue === null )
        {
            trigger_error( __METHOD__ . " could not find {$this->name}.ini\[{$section}]$key setting", E_USER_WARNING );
        }
        return $fallBackValue;
    }

    /**
     * Gets a configuration values for a section or $fallBackValue if undefined
     * Triggers warning if section is not set and $fallBackValue is null
     *
     * @param string $section The configuration section to get value for
     * @param mixed $fallBackValue value to return if section is undefined.
     *
     * @return array|null (null if key is undefined and no $fallBackValue is provided)
     */
    public function getSection( $section, $fallBackValue = null )
    {
        if ( isset( $this->raw['data'][$section] ) )
        {
            return $this->raw['data'][$section];
        }
        if ( $fallBackValue === null )
        {
            trigger_error( __METHOD__ . " could not find {$this->name}.ini\[{$section}]setting", E_USER_WARNING );
        }
        return $fallBackValue;
    }

    /**
     * Gets all section and configuration value
     *
     * @return array
     */
    public function getAll()
    {
        return $this->raw['data'];
    }

    /**
     * Gets a configuration value, or null if not set.
     *
     * @param string $section The configuration section to get value for
     * @param string $key The configuration key to get value for
     * @param mixed $value value to return if setting is not defined.
     *
     * @return boolean Return true if section existed and was overwritten
     */
    public function set( $section, $key, $value = null )
    {
        if ( isset( $this->raw['data'][$section] ) )
        {
            $this->raw['data'][$section][$key] = $value;
            return true;
        }

        $this->raw['data'][$section] = array( $key => $value );
        return false;
    }

    /**
     * Checks if a configuration section and optionally key is set.
     *
     * @param string $section
     * @param string $key Optional, only checks if section exists if null
     *
     * @return boolean Return true if setting exist
     */
    public function has( $section, $key = null )
    {
        if ( $key === null )
            return isset( $this->raw['data'][$section] );

        return isset( $this->raw['data'][$section][$key] );
    }

    /**
     * Checks if a configuration section & key is set and has a value.
     * (ie. a check using !empty())
     *
     * @param string $section
     * @param string $key
     *
     * @return boolean Return true if setting exist and has value
     */
    public function hasValue( $section, $key )
    {
        return !empty( $this->raw['data'][$section][$key] );
    }
}

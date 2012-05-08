<?php
/**
 * Contains: PSR-0 ish Loader Class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace HiMVC\Core\Common;

/**
 * Provides PSR-0 ish Loader
 *
 * Use:
 * require 'HiMVC/Core/Common/ClassLoader.php'
 * spl_autoload_register( array( new HiMVC\Core\Common\ClassLoader(
 *     array(
 *         'Vendor\\Module' => 'Vendor/Module'
 *     )[,
 *     HiMVC\Core\Common\ClassLoader::PSR_0_PEAR_COMPAT] // PSR-0 PEAR compat mode
 * ), 'load' ) );
 */
class ClassLoader
{
    /**
     * Mode for disabling PEAR autoloader compatibility (and PSR-0 compat)
     *
     * @var int
     */
    const PSR_0_STRICT_MODE = 1;

    /**
     * Mode to not check if file exists before loading class name that matches prefix
     *
     * @var int
     */
    const PSR_0_NO_FILECHECK = 2;

    /**
     * @var array Contains namespace/class prefix as key and sub path as value
     */
    protected $paths;

    /**
     * @var array Hash of settings for this Class loader
     *      'Mode' int One or more of of the MODE constants, these are opt-in. Default; 0
     *      'LazyLoaders' array Hash with class name prefix as key and callback as function a setup loader
     *          Example:
     *          array(
     *              'ezc' => function( $className ){
     *                  require 'ezc/Base/base.php';
     *                  spl_autoload_register( array( 'ezcBase', 'autoload' ) );
     *                  return true;
     *              }
     *          )
     *          Return true signals that autoloader was successfully registered and can be removed from hash.
     *          Default: empty array
     *      'LegacyClassMap' array|null Hash of class name to file map for legacy use, tries to load from eZ Publish
     *          if null. Defaul: null
     *      'LegacyAllowKernelOverride' bool Defines if LegacyClassMap=null should load class map for kernel overrides or not.
     */
    protected $settings;

    /**
     * Construct a loader instance
     *
     * @param array $paths Containing class/namespace prefix as key and sub path as value
     * @param array $settings Settings for loader, {@see $settings}
     */
    public function __construct( array $paths, array $settings = array() )
    {
        $this->paths = $paths;
        $this->settings = $settings + array(
            'Mode' => 0,
            'LazyLoaders' => array(),
            'LegacyClassMap' => null,
            'LegacyAllowKernelOverride' => false,
            'LegacyRelativeRootPath' => '',
        );
    }

    /**
     * Load classes/interfaces following PSR-0 naming
     *
     * @param string $className
     * @param bool $returnFileName For testing, returns file name instead of loading it
     * @return null|boolean|string Null if no match is found, bool if match and found/not found,
     *                             string if $returnFileName is true.
     */
    public function load( $className, $returnFileName = false )
    {
        if ( $className[0] === '\\' )
            $className = substr( $className, 1 );

        foreach ( $this->paths as $prefix => $subPath )
        {
            if ( strpos( $className, $prefix ) !== 0 )
                continue;

            if ( !( $this->settings['Mode'] & self::PSR_0_STRICT_MODE ) ) // Normal PSR-0 PEAR compat
            {
                $lastNsPos = strripos( $className, '\\' );
                $prefixLen = strlen( $prefix ) + 1;
                $fileName = $subPath . DIRECTORY_SEPARATOR;

                if ( $lastNsPos > $prefixLen )
                {
                    // Replacing '\' to '/' in namespace part
                    $fileName .= str_replace( '\\', DIRECTORY_SEPARATOR, substr( $className, $prefixLen, $lastNsPos - $prefixLen ) )
                               . DIRECTORY_SEPARATOR;
                }

                // Replacing '_' to '/' in className part and append '.php'
                $fileName .= str_replace( '_', DIRECTORY_SEPARATOR, substr( $className, $lastNsPos + 1 ) ) . '.php';
            }
            else // Strict PSR mode
            {
                $fileName = $subPath . DIRECTORY_SEPARATOR .
                            str_replace( '\\', DIRECTORY_SEPARATOR, substr( $className , strlen( $prefix ) +1 ) ) .
                            '.php';
            }

            if ( !( $this->settings['Mode'] & self::PSR_0_NO_FILECHECK ) &&
                 ( $fileName = stream_resolve_include_path( $fileName ) ) === false )
                return false;


            if ( $returnFileName )
                return $fileName;

            require $fileName;
            return true;
        }

        // No match where found, see if we have any lazy loaded closures that should register other autoloaders
        foreach ( $this->settings['LazyLoaders'] as $prefix => $callable )
        {
            if ( strpos( $className, $prefix ) !== 0 )
                continue;

            if ( $callable( $className ) )
                unset( $this->settings['LazyLoaders'][$prefix] );

            return true;
        }

        if ( $this->settings['LegacyClassMap'] === null )
            $this->settings['LegacyClassMap'] = $this->getEzp4ClassesList();

        if ( isset( $this->settings['LegacyClassMap'][$className] ) )
        {
            if ( $returnFileName )
                return $this->settings['LegacyClassMap'][$className];

            require $this->settings['LegacyClassMap'][$className];
            return true;
        }

        return null;
    }

    /**
     * Merges all eZ Publish 4.x autoload files and return result
     *
     * @return array
     */
    public function getEzp4ClassesList()
    {
        if ( file_exists( "autoload/ezp_kernel.php" ) )
            $ezpKernelClasses = require "autoload/ezp_kernel.php";
        else
            $ezpKernelClasses = array();

        if ( file_exists( "var/autoload/ezp_extension.php" ) )
            $ezpExtensionClasses = require "var/autoload/ezp_extension.php";
        else
            $ezpExtensionClasses = array();

        if ( file_exists( "var/autoload/ezp_tests.php" ) )
            $ezpTestClasses = require "var/autoload/ezp_tests.php";
        else
            $ezpTestClasses = array();

        if ( $this->settings["LegacyAllowKernelOverride"] && file_exists( "var/autoload/ezp_override.php" ) )
            $ezpKernelOverrideClasses = require "var/autoload/ezp_override.php";
        else
            $ezpKernelOverrideClasses = array();

        return $ezpKernelOverrideClasses + $ezpTestClasses + $ezpExtensionClasses + $ezpKernelClasses;
    }
}

<?php
/**
 * File contains DesignDispatcher class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use eZ\Publish\Core\Base\Exceptions\NotFoundException,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    HiMVC\Core\MVC\Request;

/**
 * DesignDispatcher, template loader
 *
 * @todo Add cache based pr $possibleDesignLocations singature
 */
class DesignDispatcher
{
    /**
     * @var array List of paths (relative or absolute) to possible design locations in the system
     */
    private $possibleDesignLocations;

    /**
     * Construct DesignDispatcher and pre generate possible design locations.
     *
     * Possible design locations is a combination of active modules and active designs.
     *
     * @param \HiMVC\Core\Base\Module[] $modules
     * @param array $designs
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If $designs or $request->modules is empty
     */
    public function __construct( array $modules, array $designs )
    {
        if ( empty( $designs ) )
            throw new InvalidArgumentException( '$designs', 'Empty, can not find design locations' );

        if ( empty( $modules ) )
            throw new InvalidArgumentException( '$modules', 'Empty, can not find design locations' );

        foreach ( $modules as $module )
        {
            foreach ( $designs as $design )
            {
                $this->possibleDesignLocations[] = "{$module->path}/design/{$design}";
            }
        }

        // Reverse the list as we the last item has first priority
        $this->possibleDesignLocations = array_reverse( $this->possibleDesignLocations );

    }

    /**
     * @param $name
     * @return string
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If $name was not found in $possibleDesignLocations
     */
    public function getPath( $name )
    {
        foreach ( $this->possibleDesignLocations as $designPath )
        {
            if ( is_file( "{$designPath}/{$name}" ) )
            {
                return "{$designPath}/{$name}";
            }
        }
        throw new NotFoundException( "Could not find template in any design:" . var_export( $this->possibleDesignLocations, true ), $name );
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param  string $name The name of the template to load
     * @return string The template source code
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException When $name is not found
     */
    public function getSource( $name )
    {
        $path = $this->getPath( $name );
        return file_get_contents( $path );
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param  string $name The name of the template to load
     * @return string The cache key
     *
     * @throws |eZ\Publish\API\Repository\Exceptions\NotFoundException When $name is not found
     */
    public function getCacheKey( $name )
    {
        return hash( 'md4', $this->getPath( $name ) );
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time The last modification time of the cached template
     * @return bool true if the template is fresh, false otherwise
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException When $name is not found
     */
    public function isFresh( $name, $time )
    {
        $path = $this->getPath( $name );
        return filemtime( $path ) <= $time;
    }
}
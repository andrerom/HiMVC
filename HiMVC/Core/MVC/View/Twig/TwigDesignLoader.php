<?php
/**
 * File contains TwigDesignLoader class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\View\Twig;

use HiMVC\Core\MVC\View\DesignLoader,
    Twig_LoaderInterface,
    Twig_Error_Loader,
    eZ\Publish\API\Repository\Exceptions\NotFoundException;

/**
 * TwigDesignLoader, template loader for Twig
 *
 * Uses DesignDispatcher and just converts exceptions into twig exceptions.
 */
class TwigDesignLoader extends DesignLoader implements Twig_LoaderInterface
{
    /**
     * Gets the source code of a template, given its name.
     *
     * @param  string $name The name of the template to load
     * @return string The template source code
     *
     * @throws \Twig_Error_Loader When $name is not found
     */
    public function getSource( $name )
    {
        try
        {
            return parent::getSource( $name );
        }
        catch ( NotFoundException $e )
        {
            throw new Twig_Error_Loader( "Could not find template", -1, $name, $e );
        }
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param  string $name The name of the template to load
     * @return string The cache key
     *
     * @throws |Twig_Error_Loader When $name is not found
     */
    public function getCacheKey( $name )
    {
        try
        {
            return parent::getCacheKey( $name );
        }
        catch ( NotFoundException $e )
        {
            throw new Twig_Error_Loader( "Could not find template", -1, $name, $e );
        }
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time The last modification time of the cached template
     * @return bool true if the template is fresh, false otherwise
     *
     * @throws \Twig_Error_Loader When $name is not found
     */
    public function isFresh( $name, $time )
    {
        try
        {
            return parent::isFresh( $name, $time );
        }
        catch ( NotFoundException $e )
        {
            throw new Twig_Error_Loader( "Could not find template", -1, $name, $e );
        }
    }
}
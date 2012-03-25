<?php
/**
 * Container Interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API;

/**
 * Container interface
 *
 * Interface for dependency injection container implemention.
 * @todo Create interfaces for all objects defined as public api here
 */
interface Container
{
    /**
     * Get Repository object
     *
     * @return \eZ\Publish\API\Repository\Repository
     */
    public function getRepository();

    /**
     * Get Request object
     *
     * @return \HiMVC\Core\MVC\Request
     */
    public function getRequest();

    /**
     * Get Router object
     *
     * @return \HiMVC\Core\MVC\Router
     */
    public function getRouter();

    /**
     * Get ViewDispatcher object
     *
     * @return \HiMVC\Core\MVC\ViewDispatcher
     */
    public function getViewDispatcher();

    /**
     * Get Dispatcher object
     *
     * @return \HiMVC\Core\MVC\Dispatcher
     */
    public function getDispatcher();
}


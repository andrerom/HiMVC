<?php
/**
 * File contains Dispatcher class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use HiMVC\Core\MVC\Values\Request as APIRequest;
use HiMVC\Core\MVC\Router;

/**
 * Dispatcher
 *
 * Dispatches request using ruter and view dispatcher.
 */
class Dispatcher
{
    /**
     * Router to use
     *
     * @var Router
     */
    protected $router;

    /**
     * Construct from router and viewDispatcher
     *
     * @param \HiMVC\Core\MVC\Router $router
     */
    public function __construct( Router $router )
    {
        $this->router = $router;
    }

    /**
     * Dispatch the request
     *
     * Dispatches the request using the information from the router and paasing
     * the result to the view.
     *
     * @param \HiMVC\Core\MVC\Values\Request $request
     * @return Response An object that can be casted to string, hence used in templates as well
     */
    public function dispatch( APIRequest $request )
    {
        // @todo: Add filters and exceptions support (redirect and misc http errors)
        return $this->router->route( $request );
    }
}
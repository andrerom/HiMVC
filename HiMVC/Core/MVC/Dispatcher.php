<?php
/**
 * File contains Dispatcher class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use HiMVC\Core\MVC\Request;
use HiMVC\Core\MVC\Router;
use HiMVC\Core\MVC\ViewDispatcher;
use HiMVC\API\MVC\Values\Result;

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
     * View handler to use
     *
     * @var ViewDispatcher
     */
    protected $viewDispatcher;

    /**
     * Construct from router and viewDispatcher
     *
     * @param \HiMVC\Core\MVC\Router $router
     * @param \HiMVC\Core\MVC\ViewDispatcher $viewDispatcher
     */
    public function __construct( Router $router, ViewDispatcher $viewDispatcher )
    {
        $this->router = $router;
        $this->viewDispatcher   = $viewDispatcher;
    }

    /**
     * Dispatch the request
     *
     * Dispatches the request using the information from the router and paasing
     * the result to the view.
     *
     * @param Request $request
     * @return Response An object that can be casted to string, hence used in templates as well
     */
    public function dispatch( Request $request )
    {
        // @todo: Add filters and exceptions
        $result = $this->router->route( $request );

        if ( $result instanceof Result )
            return $this->viewDispatcher->view( $request, $result );

        // @todo: Throw if not a Response object
        return $result;
    }
}
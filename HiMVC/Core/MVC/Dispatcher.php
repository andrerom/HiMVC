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

use HiMVC\API\MVC\Values\Request as APIRequest;
use HiMVC\Core\MVC\Router;
use HiMVC\Core\MVC\View\ViewDispatcher;
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
     * @param \HiMVC\Core\MVC\View\ViewDispatcher $viewDispatcher
     */
    public function __construct( Router $router, ViewDispatcher $viewDispatcher )
    {
        $this->router = $router;
        $this->viewDispatcher = $viewDispatcher;
    }

    /**
     * Dispatch the request
     *
     * Dispatches the request using the information from the router and paasing
     * the result to the view.
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param bool $isRootRequest If true, this signals that this is the root request (not embed)
     *                            and hence router->route returns Result object instead of
     *                            exception or Response object, then layout is applied.
     * @return Response An object that can be casted to string, hence used in templates as well
     */
    public function dispatch( APIRequest $request, $isRootRequest = false )
    {
        // @todo: Add filters and exceptions support (redirect and misc http errors)
        $result = $this->router->route( $request );

        // @todo: Throw if not a Response object, or do 500 internal server error redirect
        if ( !$result instanceof Result )
        {
            return $result;
        }

        if ( $isRootRequest )
        {
            // @todo Either rename or change this to fit json / xml requests
            return $this->viewDispatcher->layout( $request, $result );
        }

        return $this->viewDispatcher->view( $request, $result );
    }
}
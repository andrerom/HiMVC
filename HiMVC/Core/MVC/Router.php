<?php
/**
 * File contains Router class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use HiMVC\API\MVC\Values\Request as APIRequest;
use HiMVC\API\MVC\Values\Result as APIResult;
use eZ\Publish\Core\Base\Exceptions\Httpable;

/**
 * Router handles routing a request to a controller
 */
class Router
{
    /**
     * @var \HiMVC\API\MVC\Values\Route[]
     */
    protected $routes;

    /**
     * @param \HiMVC\API\MVC\Values\Route[] $routes
     */
    public function __construct( array $routes )
    {
        $this->routes = $routes;
    }

    /**
     * @param \HiMVC\API\MVC\Values\Request $request
     * @return \HiMVC\API\MVC\Values\Result
     * @throws eZ\Publish\Core\Base\Exceptions\Httpable
     * @todo Addapt some kind of httpable exceptions which maps to http errors at least, similar to /x/
     */
    public function route( APIRequest $request )
    {
        $uri = $request->uri;
        foreach ( $this->routes as $route )
        {
            if ( $route->uri === $uri )
            {
                // Do nothing
            }
            else if ( $route->uri !== '' && strpos( $uri, $route->uri ) !== 0 )
                continue;

            if ( isset( $route->methodMap[$request->method] ) )
                $method = $route->methodMap[$request->method];
            else if ( isset( $route->methodMap['ALL'] ) )
                $method = $route->methodMap['ALL'];
            else
                continue;// No match: next route

            if ( ( $uriParams = $route->match( $request ) ) === null )
                continue;

            $controller = $route->controller;
            $result = call_user_func_array( array( $controller(), $method ), $uriParams );
            if ( $result instanceof APIResult )
            {
                $result->setRoute( $route );
            }
            return $result;
        }

        throw new \Exception( "Could not find a route for uri: '{$uri}', and method: '{$request->method}'" );//404
    }
}

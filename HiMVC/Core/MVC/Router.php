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
use eZ\Publish\Core\Base\Exceptions\Httpable;

/**
 * Router handles routing a request to a controller
 */
class Router
{
    /**
     * @var array
     */
    protected $routes;

    /**
     * @param array $routes
     * @todo Document $routes
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
        $uriArray = $request->uriArray;
        if ( !empty( $uriArray ) && isset( $this->routes[ $uriArray[0] ] ) )
            $routes = $this->routes[ $uriArray[0] ];
        else
            $routes = $this->routes[ '_root_' ];

        foreach ( $routes as $routeKey => $route )
        {
            if ( isset( $route['uri'] ) && $uri !== $route['uri'] )
                continue;// No match: next route

            if ( isset( $route['method'] ) && $request->method !== $route['method'] )
                continue;// No match: next route

            if ( isset( $route['methods'] ) &&  !isset( $route['methods'][$request->method] ) )
                continue;// No match: next route

            $uriParams = array();
            if ( isset( $route['regex'] ) )
            {
                $regex = str_replace( array( '{', '}' ), array( '(', ')' ), $route['regex'] );
                if ( !preg_match( "@^{$regex}$@", $uri, $matches ) )
                    continue;

                $i = 0;// Remove all indexes that has numeric keys, the once we care about have string keys
                while ( isset( $matches[$i] ) )
                {
                    unset( $matches[$i] );
                    ++$i;
                }
                $uriParams = $matches;
            }

            if ( isset( $route['function'] ) )
            {
                return call_user_func_array( $route['function'], $uriParams );
            }
            else if ( !isset( $route['controller'] ) )
            {
                throw new \Exception( "Routes[{$uriArray[0]}][{$routeKey}] is missing both a controller and a function parameter!" );//500
            }
            else if ( !isset( $route['methods'][$request->method] ) )
            {
                throw new \Exception( "Routes[{$uriArray[0]}][{$routeKey}] is missing a methods map as needed in conjunction with controller!" );//500
            }

            $controller = $route['controller']();
            $method = $route['methods'][$request->method];
            return call_user_func_array( array( $controller, $method ), $uriParams );
        }

        throw new \Exception( "Could not find a route for uri: '{$uri}', and method: '{$request->method}'" );//404
    }
}

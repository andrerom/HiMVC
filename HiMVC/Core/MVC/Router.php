<?php
/**
 * File contains Router class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use HiMVC\Core\MVC\Request,
    eZ\Publish\Core\Base\Exceptions\Httpable;

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
     * @param Request $request
     * @return \HiMVC\Core\MVC\Response
     * @throws eZ\Publish\Core\Base\Exceptions\Httpable
     * @todo Addapt some kind of httpable exceptions which maps to http errors at least, similar to /x/
     */
    public function execute( Request $request )
    {
        $redirectCount = 0;

        startRouting:
        $uri = $request->uri;
        $uriArray = $request->uriArray;
        if ( $uri === '' )
            $routes = $this->routes[ '_root_' ];
        else if ( isset( $this->routes[ $uriArray[0] ] ) )
            $routes = $this->routes[ $uriArray[0] ];
        else
            throw new \Exception( 'Could not find routes for $uriArray[0]: ' . $uriArray[0] );

        foreach ( $routes as $routeKey => $route )
        {
            if ( isset( $route['action'] ) && $request->action !== $route['action'] )
                continue;

            if ( isset( $route['actions'] ) &&  !in_array( $request->action, $route['actions'] ) )
                continue;

            if ( isset( $route['uri'] ) && strpos( $uri, $route['uri'] ) !== 0 )
                continue;

            $uriParams = array();
            if ( isset( $route['params'] ) )
            {
                $pos = substr_count( $route['uri'], '/' );
                foreach ( $route['params'] as $uriParam => $uriParamRegex )
                {
                    if ( !isset( $uriArray[ $pos ] ) )
                    {
                        if ( isset( $route['optional'][$uriParam] ) && $route['optional'][$uriParam]  )
                        {
                            break;// Break as you can not have non optional params after a optional one
                        }
                        continue 2;
                    }

                    if ( preg_match( "/^({$uriParamRegex})$/", $uriArray[ $pos ] ) !== 1 )
                        continue 2;

                    $uriParams[$uriParam] = $uriArray[ $pos ];
                    $pos++;
                }
            }

            if ( isset( $route['redirect'] ) )
            {
                $request->uri = $route['redirect'];
                $redirectCount++;
                if ( $redirectCount > 10 )
                    throw new \Exception( "Exceeded routing redirect limit of 10!" );
                goto startRouting;
            }

            if ( isset( $route['function'] ) )
            {
                return call_user_func_array( $route['function'], $uriParams );
            }
            else if ( !isset(  $route['controller'] ) )
            {
                throw new \Exception( "Routes[{$uriArray[0]}][{$routeKey}] is missing both a controller and a function parameter!" );
            }

            $controller = $route['controller']();
            $method = isset( $route['method'] ) ? $route['method'] : 'do' . $request->action;
            return call_user_func_array( array( $controller, $method ), $uriParams );
        }

        throw new \Exception( "Could not find a route for uri: '{$uri}', and action: '{$request->action}'" );
    }
}

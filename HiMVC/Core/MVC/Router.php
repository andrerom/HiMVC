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
     * @var array[] Key is conrtoller class, value list of routes
     */
    protected $reverseRoutes = array();

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
            if ( $route->uri !== $uri && $route->uri !== '' && strpos( $uri, $route->uri ) !== 0 )
                continue;// No root uri match

            if ( isset( $route->methodMap[$request->method] ) )
                $method = $route->methodMap[$request->method];
            else if ( isset( $route->methodMap['ALL'] ) )
                $method = $route->methodMap['ALL'];
            else
                continue;// No method match

            if ( ( $uriParams = $route->match( $uri ) ) === null )
                continue;// No request match

            $controller = $route->controller;
            return call_user_func_array( array( $controller(), $method ), $uriParams );
        }

        throw new \Exception( "Could not find a route for uri: '{$uri}', and method: '{$request->method}'" );//404
    }

    /**
     * @param $controller
     * @param $action
     * @param array $params
     * @return array First value is the matched method, and second is the matched uri
     */
    public function reverse( $controller, $action, array $params )
    {
        if ( empty( $this->reverseRoutes[$controller] ) )
        {
            throw new \Exception( "No routes exists for coneroller: {$controller}" );
        }

        foreach ($this->reverseRoutes[$controller] as $route )
        {
            if (  $key = array_search( $action, $route->methodMap, true ) )
                return array( $key, $route->reverse( $params ) );
        }
        throw new \Exception( "Did not find a matching route for: {$controller}::{$action}" );
    }

    /**
     * Method injection of container settings
     *
     * Needed to be able to generate info for doing reverse routing
     *
     * @see $reverseRoutes
     * @param array $settings
     */
    public function generateReverseInfo( array $settings )
    {
        foreach ( $this->routes as $section => $route )
        {
            $controller = $settings["{$section}:route"]['arguments']['controller'];
            if ( ( $functionPos = stripos( $controller, '::' ) ) !== false )
            {
                $controller = substr( $controller, 0, $functionPos );
            }

            if ( $controller[0] === '@' || $controller[0] === '%' )
            {
                $controller = substr( $controller, 1 );
                $controller = $settings[$controller]['class'];
            }

            $this->reverseRoutes[$controller][$section] = $route;
        }
    }
}

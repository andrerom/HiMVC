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
     * @var array[] Key is controller class name, value list of routes with route identifier as key
     */
    protected $routesByControllerName = array();

    /**
     * @var array[] Key is controller class name, value list of routes with route identifier as key
     */
    protected $routesByControllerIdentifier = array();


    /**
     * @param \HiMVC\API\MVC\Values\Route[] $routes
     */
    public function __construct( array $routes )
    {
        $this->routes = $routes;
    }

    /**
     * @param \HiMVC\API\MVC\Values\Request $request
     * @throws \Exception
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function route( APIRequest $request )
    {
        $uri = $request->uri;
        foreach ( $this->routes as $route )
        {
            if ( $route->uri !== $uri && $route->uri !== '' && strpos( $uri, $route->uri ) !== 0 )
                continue;// No root uri match

            if ( isset( $route->methodMap[$request->method] ) )
                $action = $route->methodMap[$request->method];
            else if ( isset( $route->methodMap['ALL'] ) )
                $action = $route->methodMap['ALL'];
            else
                continue;// No method match

            if ( ( $uriParams = $route->match( $uri ) ) === null )
                continue;// No request match


            return call_user_func( $route->controller, $request, $action, $uriParams );
        }

        throw new \Exception( "Could not find a route for uri: '{$uri}', and method: '{$request->method}'" );//404
    }

    /**
     * @param $className
     * @param $action
     * @throws \Exception
     * @return \HiMVC\API\MVC\Values\Route
     */
    public function getRouteByControllerName( $className, $action )
    {
        if ( empty( $this->routesByControllerName[$className] ) )
        {
            throw new \Exception( "No routes exists for controller: {$className}" );
        }

        foreach ($this->routesByControllerName[$className] as $route )
        {
            if (  $key = array_search( $action, $route->methodMap, true ) )
                return $route;
        }
        throw new \Exception( "Did not find a matching route for: {$className}::{$action}" );
    }

    /**
     * @param $identifier
     * @param $action
     * @throws \Exception
     * @return \HiMVC\API\MVC\Values\Route
     */
    public function getRouteByControllerIdentifier( $identifier, $action )
    {
        if ( empty( $this->routesByControllerIdentifier[$identifier] ) )
        {
            throw new \Exception( "No routes exists for controller identifier: {$identifier}" );
        }

        foreach ($this->routesByControllerIdentifier[$identifier] as $route )
        {
            if (  $key = array_search( $action, $route->methodMap, true ) )
                return $route;
        }
        throw new \Exception( "Did not find a matching route for: {$identifier}::{$action}" );
    }

    /**
     * Method injection of container settings
     *
     * Needed to be able to generate info for doing reverse routing both by controller class name
     * and controller identifier (in case of ini settings: the section defining the controller service).
     *
     * @see $routesByControllerClass
     * @see $routesByControllerIdentifier
     * @param array $settings
     */
    public function generateReverseInfo( array $settings )
    {
        foreach ( $this->routes as $routeIdentifier => $route )
        {
            $controllerClass = $settings["{$routeIdentifier}:route"]['arguments']['controller'];
            if ( ( $functionPos = stripos( $controllerClass, '::' ) ) !== false )
            {
                $controllerClass = substr( $controllerClass, 0, $functionPos );
            }

            if ( $controllerClass[0] === '@' || $controllerClass[0] === '%' )
            {
                $controllerIdentifier = substr( $controllerClass, 1 );
                $controllerClass = $settings[$controllerIdentifier]['class'];
                $this->routesByControllerIdentifier[$controllerIdentifier][$routeIdentifier] = $route;
            }

            $this->routesByControllerName[$controllerClass][$routeIdentifier] = $route;
        }
    }
}

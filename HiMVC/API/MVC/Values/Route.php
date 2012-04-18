<?php
/**
 * API\MVC\Values\Route class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use eZ\Publish\API\Repository\Values\ValueObject;
use HiMVC\API\MVC\Values\Request;
use Closure;

/**
 * Route object
 *
 * Represent a route, in this case a plain route.
 *
 * @property-read string $uri
 * @property-read array $methodMap
 * @property-read Closure $controller
 */
class Route extends ValueObject
{
    /**
     * The unique root resourche identifier for the result
     *
     * Like "content" or "content/location", must not start or end with a slash, used for links in view.
     *
     * @var string
     */
    protected $uri;

    /**
     * The method name that maps to this route
     *
     * Format like this:
     *     array( 'GET' => 'doRead'[ 'POST' => 'doCreate' ] )
     *
     * A match all (or match rest) clouse can be archived with key 'ALL', like:
     *     array( 'ALL' => 'doSomething' )
     *
     * @var array
     */
    protected $methodMap;

    /**
     * The callback function for getting the controller
     *
     * @var Closure
     */
    protected $controller;

    /**
     * Constructor for Route
     *
     * @param string $uri
     * @param array $methodMap
     * @param Closure $controller A callback to get the controller
     */
    public function __construct( $uri, array $methodMap, Closure $controller )
    {
        $this->uri = $uri;
        $this->methodMap = $methodMap;
        $this->controller = $controller;
    }

    /**
     * Match request and return uri params, null if no match.
     *
     * @param string $uri
     * @return array|null
     */
    public function match( $uri )
    {
        if ( $this->uri !== $uri )
            return null;
        return array();
    }

    /**
     * Return uri that matches this Route
     *
     * @param array $uriParams
     * @return string A relative uri indenpenat of site specific parts like host and www dir.
     */
    public function reverse( array $uriParams )
    {
        $uri = $this->uri;
        foreach ( $uriParams as $paramValue )
        {
            if ( $paramValue === null || $paramValue === false )
                break;
            $uri .= "/{$paramValue}";
        }
        return $uri;
    }
}

<?php
/**
 * API\MVC\Values\Route class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Values;

use eZ\Publish\API\Repository\Values\ValueObject;
use Closure;

/**
 * Route object
 *
 * Represent a route, in this case a plain route.
 * Properties defined here is used directly by Router and should be considered as api.
 *
 * Note: Use of ValueObject is purly to make sure property name typoes triggers exceptions.
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
    public $rootUri;

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
    public $methodMap;

    /**
     * A callback to execute controller
     *
     * @var Closure
     */
    public $controller;

    /**
     * Constructor for Route
     *
     * @param string $rootUri
     * @param array $methodMap
     * @param callable $controller A callback to execute controller
     */
    public function __construct( $rootUri, array $methodMap, $controller )
    {
        $this->rootUri = $rootUri;
        $this->methodMap = $methodMap;

        if ( $controller instanceof Closure )
            $this->controller = $controller;
        else if ( is_callable( $controller ) )
            $this->controller = $controller;
        else
            throw new \Exception( "Argument \$controller must be callable" );


    }

    /**
     * Match request and return uri params, null if no match.
     *
     * @param string $uri
     * @return array|null
     */
    public function match( $uri )
    {
        if ( $this->rootUri !== $uri )
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
        $uri = $this->rootUri;
        foreach ( $uriParams as $paramValue )
        {
            if ( $paramValue === null || $paramValue === false )
                break;
            $uri .= "/{$paramValue}";
        }
        return $uri;
    }
}

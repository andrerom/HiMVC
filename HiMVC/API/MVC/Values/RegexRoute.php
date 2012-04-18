<?php
/**
 * API\MVC\Values\RegexRoute class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use HiMVC\API\MVC\Values\Route;
use HiMVC\API\MVC\Values\Request;
use Closure;

/**
 * Route object
 *
 * Represent a route, in this case using regex matching
 *
 * @property-read string $pattern The pattern used for uri matching and to extract uri params, $uri (root uri) is
 *                                prepended to this string before match.
 */
class RegexRoute extends Route
{
    /**
     * The pattern used for uri matching and to extract uri params
     *
     * @var string
     */
    protected $pattern;

    /**
     * Constructor for Route
     *
     * @param string $uri
     * @param array $methodMap
     * @param Closure $controller A callback to get the controller
     * @param string $pattern The pattern used for uri matching and to extract uri params
     */
    public function __construct( $uri, array $methodMap, Closure $controller, $pattern )
    {
        $this->pattern = $pattern;
        parent::__construct( $uri, $methodMap, $controller );
    }

    /**
     * Match request and return uri params, null if no match.
     *
     * @param string $uri
     * @return array|null
     */
    public function match( $uri )
    {
        $regex = str_replace( array( '{', '}' ), array( '(', ')' ), $this->pattern );
        if ( !preg_match( "@^{$this->uri}{$regex}$@", $uri, $matches ) )
            return null;

        $i = 0;// Remove all indexes that has numeric keys, the once we care about have string keys
        while ( isset( $matches[$i] ) )
        {
            unset( $matches[$i] );
            ++$i;
        }
        return $matches;
    }
}

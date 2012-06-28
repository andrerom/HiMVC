<?php
/**
 * API\MVC\Values\RegexRoute class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Legacy;

use HiMVC\API\MVC\Values\Route as APIRoute;
use HiMVC\Core\MVC\Values\Request;
use Closure;

/**
 * Route object
 *
 * Represent a route, in this case using regex matching
 *
 * @property-read string $pattern The pattern used for uri matching and to extract uri params, $uri (root uri) is
 *                                prepended to this string before match.
 */
class Route extends APIRoute
{
    /**
     * Match request and return uri params, null if no match.
     *
     * @param string $uri
     * @return array|null
     */
    public function match( $uri )
    {
        return explode( '/', $uri );
    }
}

<?php
/**
 * API\MVC\Values\RegexRoute class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Values;

use HiMVC\Core\MVC\Values\Route;
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
class RegexRoute extends Route
{
    /**
     * The pattern used for uri matching and to extract uri params
     *
     * This is a regex generated based on $rootUri, $params and $optionalParams in {@see __construct()}.
     *
     * @var string
     */
    protected $pattern;

    /**
     * The uri params needed for this route to match
     *
     * like: array( 'id' => '\d+', 'view' => '\w+' )
     *
     * @var array
     */
    protected $params;

    /**
     * The uri params that are optional, and their default value
     *
     * like: array( 'view' => 'full' )
     *
     * @var array
     */
    protected $optionalParams;

    /**
     * Constructor for Route
     *
     * @param string $rootUri
     * @param array $methodMap
     * @param callable $controller A callback to execute controller
     * @param array $params
     * @param array $optionalParams
     * @throws \Exception
     */
    public function __construct( $rootUri, array $methodMap, $controller, array $params, array $optionalParams = array() )
    {
        $this->params = $params;
        $this->optionalParams = $optionalParams;
        $this->pattern = $rootUri;

        // Append params so they create a regex pattern, like: content/(?<id>\d+)(/(?<view>\w+))?
        $optionalParamsCount = 0;
        foreach ( $params as $paramName => $paramRegex )
        {
            if ( isset( $optionalParams[ $paramName ] ) )
            {
                $this->pattern .= '(';
                ++$optionalParamsCount;
            }
            else if ( $optionalParamsCount !== 0 )
            {
                throw new \Exception( "Can not define a non optional argument after a optional one, pattern: {$this->pattern}" );
            }
            $this->pattern .= '/(?<' . $paramName . '>' . $paramRegex . ')';
        }
        // Finnish off regex by applying remaining optional params closing blocks ')?'
        while ( $optionalParamsCount !== 0 )
        {
            $this->pattern .= ')?';
            --$optionalParamsCount;
        }
        parent::__construct( $rootUri, $methodMap, $controller );
    }

    /**
     * Match request and return uri params, null if no match.
     *
     * @param string $uri
     * @return array|null
     */
    public function match( $uri )
    {
        if ( !preg_match( "@^{$this->pattern}$@", $uri, $matches ) )
            return null;

        $i = 0;// Remove all indexes that has numeric keys, the once we care about have string keys
        while ( isset( $matches[$i] ) )
        {
            unset( $matches[$i] );
            ++$i;
        }
        return $matches;
    }

    /**
     * Return uri that matches this Route
     *
     * @param array $uriParams
     * @return string A relative uri independent of site specific parts like host and www dir.
     */
    public function reverse( array $uriParams )
    {
        $uri = $this->rootUri;
        $tempOptionalUri = '';
        foreach ( $uriParams as $paramName => $paramValue )
        {
            if ( !isset( $this->params[$paramName] ) )
                throw new \Exception( "Param '{$paramName}' is not valid on this route: {$this->pattern}" );

            if ( isset( $this->optionalParams[$paramName] ) )
            {
                $tempOptionalUri .= "/{$paramValue}";
                if ( $this->optionalParams[$paramName] !== $paramValue )
                {
                    // If a optional part differs from default value, then append what we have so far
                    $uri .= $tempOptionalUri;
                    $tempOptionalUri = '';
                }
            }
            else
            {
               $uri .= "/{$paramValue}";
            }
        }
        return $uri;
    }
}

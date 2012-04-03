<?php
/**
 * Access match object, representing site, channel, manguage(, ...)  matching
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Base;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    eZ\Publish\API\Repository\Values\ValueObject,
    HiMVC\API\MVC\Values\Request;

/**
 * AccessMatch class
 */
class AccessMatch extends ValueObject
{
    /**
     * @var string Name of access, represented in settings with settings/access/<type>/<name>/*.ini
     */
    public $name;

    /**
     * @var string host|hosturi|hostport|hostporturi|uri|port|default
     */
    public $method;

    /**
     * @var string site|channel|language
     */
    public $type;

    /**
     * @var string Url part used to match siteaccess, must end with a / if not empty
     */
    public $uri;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $method host|hosturi|hostport|hostporturi|uri|port|default
     * @param string $type Type of access match( channel, language, site .. ), default is 'site'
     * @param string $uri Url part used to match siteaccess, must end with a / if not empty
     */
    public function __construct( $name, $method, $type = 'site', $uri = '' )
    {
        $this->name = $name;
        $this->method = $method;
        $this->type = $type;
        $this->uri = $uri;
    }

    /**
     * Match siteaccess type on request and return a siteaccess object
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException If not match rules applies in $matchRules and
     *                                                                   no default mathc is provided.
     * @param Request $request
     * @param array $matchRules
     * @param string $type Type of access match( channel, language, site .. ), default is 'site'
     *
     * @return AccessMatch
     */
    public static function match( Request $request, array $matchRules, $type = 'site' )
    {
        foreach ( $matchRules as $matchMethod => $matchMapping )
        {
            switch ( $matchMethod )
            {
                case 'host':
                case 'port':
                case 'hostport':
                    if ( $matchMethod === 'host' )
                    {
                        $matchValue = $request->host;
                    }
                    else if ( $matchMethod === 'port' )
                    {
                        $matchValue = $request->port;
                    }
                    else
                    {
                        $matchValue = $request->host . ':' . $request->port;
                    }

                    if ( isset( $matchMapping[ $matchValue ] ) )
                    {
                        return new self( $matchMapping[ $matchValue ], $matchMethod, $type );
                    }
                    break;
                case 'uri':
                case 'hosturi':
                case 'hostporturi':
                    if ( !isset( $request->uriArray[0] ) )
                    {
                        continue 2;
                    }

                    if ( $matchMethod === 'uri' )
                    {
                        $matchValue = $request->uriArray[0];
                    }
                    else if ( $matchMethod === 'hosturi' )
                    {
                        $matchValue = $request->host . ';' . $request->uriArray[0];
                    }
                    else
                    {
                        $matchValue =  $request->host . ':' . $request->port . ';' . $request->uriArray[0];
                    }

                    if ( isset( $matchMapping[ $matchValue ] ) )
                    {
                        return new self( $matchMapping[ $matchValue ], $matchMethod, $type, $request->uriArray[0] . '/' );
                    }
                    break;
                case 'default':
                    break;
                default:
                    throw new InvalidArgumentException( '$matchMethod', "'$matchMethod' not supported" );
            }
        }

        if ( !isset( $matchRules['default'] ) )
        {
            throw new InvalidArgumentException(
                '$matchRules',
                'none of the match rules applied and no default match was provided'
            );
        }

        return new self( $matchRules['default'], 'default', $type );
    }
}

?>
<?php
/**
 * API\MVC\Values\AccessMatch class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use eZ\Publish\API\Repository\Values\ValueObject;
use HiMVC\API\MVC\Values\Request;

/**
 * AccessMatch object
 *
 * Represent a AccessMatch, in this case a plain AccessMatch.
 * Properties defined here is used directly by AccessMatcher and should be considered as api.
 *
 * Note: Use of ValueObject is purly to make sure property name typoes triggers exceptions.
 */
class AccessMatch extends ValueObject
{
    /**
     * Name of access
     *
     * Represented in settings with either:
     * - settings/access/<type>/<name>/*.ini
     * - <module-path>/settings/access/<type>/<name>/*.ini
     *
     * @var string
     */
    public $name;

    /**
     * The access match type
     *
     * One of:
     * - site
     * - channel
     * - language
     * - enviroment
     * - (..)
     *
     * @var string
     */
    public $type;

    /**
     * Root uri part to match, must end with a / if not empty, but must not start with /
     *
     * @var string
     */
    public $uri = '';

    /**
     * Host names to match, empty if not supposed to match anything
     *
     * @var array
     */
    public $hosts = array();

    /**
     * Port to match, empty if not supposed to match anything
     *
     * @var int
     */
    public $port = 0;

    /**
     * Constructor for Route
     *
     * @param string $name
     * @param string $type
     * @param array $matches
     */
    public function __construct( $name, $type, array $matches = array() )
    {
        $this->name = $name;
        $this->type = $type;

        if ( isset( $matches['uri'] ) )
            $this->uri = $matches['uri'];

        if ( isset( $matches['hosts'] ) )
            $this->hosts = (array)$matches['hosts'];

        if ( isset( $matches['port'] ) )
            $this->port = (int)$matches['port'];
    }

    /**
     * Match Request based on rules
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @return bool
     */
    public function match( Request $request )
    {
        if ( !empty( $this->uri ) && "{$request->uri}/" !== $this->uri && stripos( $request->uri, $this->uri ) !== 0  )
            return false;

        if ( !empty( $this->hosts ) && !in_array( $request->host, $this->hosts, true ) )
            return false;

        if ( !empty( $this->port ) && $request->port !== $this->port  )
            return false;

        return true;
    }

    /**
     * Return uri for this Access Match
     *
     * @return string A relative uri independent of site specific parts like host and www dir.
     */
    public function reverse()
    {
        return $this->uri;
    }
}

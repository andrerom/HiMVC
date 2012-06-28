<?php
/**
 * Access matcher
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    HiMVC\Core\MVC\Values\Request as APIRequest,
    HiMVC\Core\MVC\Values\AccessMatch;

/**
 * AccessMatcher class
 *
 * (Works in similar manner as Router)
 */
class AccessMatcher
{
    /**
     * List of AccessMatch keyed by type
     *
     * @var \HiMVC\Core\MVC\Values\AccessMatch[][]
     */
    public $matches;

    /**
     * Constructor
     *
     * @param \HiMVC\Core\MVC\Values\AccessMatch[][] $matches {@see $matches}
     */
    public function __construct( array $matches = array() )
    {
        $this->matches = $matches;
    }

    /**
     * Match access type on request and return a access match object
     *
     *
     * @param \HiMVC\Core\MVC\Values\Request $request
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException If not match rules applies in $matchRules and
     *                                                                   no default match is provided.
     * @return \HiMVC\Core\MVC\Values\AccessMatch[]
     */
    public function match( APIRequest $request )
    {
        $accessMatches = array();
        foreach ( $this->matches as $typeKey => $matches )
        {
            if ( $typeKey === 'default' || $matches instanceof AccessMatch )
            {
                throw new InvalidArgumentException(
                    "\$this->matches[{$typeKey}]",
                    'wrong structure of matches, they should be ordered by type: array( "site" => array(...) )'
                );
            }

            foreach ( $matches as $matchKey => $match )
            {
                if ( !$match instanceof AccessMatch )
                {
                    throw new InvalidArgumentException(
                        "\$this->matches[{$typeKey}][{$matchKey}]",
                        'value is not of type AccessMatch'
                    );
                }

                if ( $matchKey === 'default' )
                    continue;

                if ( $match->match( $request ) )
                {
                    $accessMatches[$typeKey] = $match;
                    continue 2;
                }
            }

            if ( !isset( $matches['default'] ) )
            {
                throw new InvalidArgumentException(
                    "\$this->matches[{$typeKey}]",
                    'none of the match rules applied and no default match was provided'
                );
            }
            $accessMatches[$typeKey] = $matches['default'];
        }
        return $accessMatches;
    }
}

?>
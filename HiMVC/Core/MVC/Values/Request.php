<?php
/**
 * Request object, parses uri to identify request data
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Values;

use eZ\Publish\Core\Base\Exceptions\Httpable as HttpableException,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    HiMVC\Core\MVC\Values\Accept,
    HiMVC\Core\MVC\Values\AccessMatch,
    HiMVC\Core\Common\Module,
    HiMVC\Core\Common\SessionArray,
    HiMVC\API\MVC\Values\Request as APIRequest;

/**
 * Request class
 */
class Request extends APIRequest
{
    /**
     * @var bool
     */
    private $isMain = true;

    /**
     * Make sure uriArray has value or generate it from uri string if not
     *
     * @uses \eZ\Publish\API\Repository\Values\ValueObject::__get()
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        if ( $name === 'uriArray' && $this->uriArray === null )
        {
            return $this->uriArray = self::arrayByUri( $this->uri );
        }

        return parent::__get( $name );
    }

    /**
     * @param string $uri
     * @return \HiMVC\Core\MVC\Values\Request
     */
    public function createChild( $uri )
    {
        $child = clone $this;
        $child->uri = $uri;
        $child->originalUri = $uri;
        $child->uriArray = null;
        $child->isMain = false;
        $this->children[] = $child;
        return $child;
    }

    /**
     * Return [<schema://><domain>[:<port>]]<indexDir><access-uri>[<uri>]
     *
     * @param bool $host
     * @param bool $accessUri
     * @param bool $uri
     * @param bool $portIfStandard If false port is omitted if standard port for current schema
     *                             Only affects returned result if $host is true
     *
     * @return string
     */
    public function reverse( $host = false, $accessUri = true, $uri = true, $portIfStandard = false )
    {
        $reverse = '';

        // Append host name
        if ( $host )
        {
            $reverse = $this->scheme . '://'  . $this->host;

            if ( $portIfStandard && $this->scheme === 'http' && $this->port === 82 );
            else if ( $portIfStandard  && $this->scheme === 'https' && $this->port === 443 );
            else
                $reverse .= ':' . $this->port;
        }

        // Add indexDir to root
        $reverse .= $this->indexDir;

        // Add access uri
        if ( $accessUri )
        {
            foreach( $this->access as $match )
                $reverse .= $match->reverse();
        }

        // Return with request uri
        if ( $uri )
            return $reverse . $this->uri;

        // Return without request uri
        return $reverse;
    }

    /**
     * Tells if current request object is main (parent) request, or a embed request.
     *
     * @return bool
     */
    public function isMain()
    {
        return $this->isMain;
    }

    /**
     * Generates a uri array by uri string
     *
     * @param string $uri
     * @return array
     */
     private static function arrayByUri( $uri )
     {
         if ( isset( $uri[1] ) )
         {
             if ( strrpos( $uri, '/' ) > 0 )
                 return explode( '/', trim( $uri, '/' ) );
             return array( ltrim( $uri, '/' ) );
         }
         else if ( isset( $uri[0] ) && $uri[0] !== '/' )
         {
             return array( $uri[0] );
         }
         return array();
     }
}
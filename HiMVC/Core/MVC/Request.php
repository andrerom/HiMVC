<?php
/**
 * Request object, parses uri to identify request data
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use eZ\Publish\Core\Base\Exceptions\Httpable as HttpableException,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    HiMVC\Core\MVC\Accept,
    HiMVC\Core\Base\AccessMatch,
    HiMVC\Core\Base\Module,
    HiMVC\Core\Base\SessionArray,
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
     * Add a acces match to list of matches and remove uri if there is one (must be left most part)
     *
     * @param \HiMVC\Core\Base\AccessMatch $access
     */
    public function appendAccessMatch( AccessMatch $access )
    {
        $this->access[] = $access;
        if ( $access->uri )
        {
            $this->__set( 'uri', ltrim( $this->uri, $access->uri ) );
        }
    }

    /**
     * Add a module to list of modules
     *
     * @param \HiMVC\Core\Base\Module $module
     */
    public function appendModule( Module $module )
    {
        $this->modules[] = $module;
    }

    /**
     * @param \HiMVC\Core\MVC\Accept $accept
     */
    public function setAccept( Accept $accept )
    {
        $this->accept = $accept;
    }

    /**
     * @param \HiMVC\Core\Base\SessionArray $session
     */
    public function setSession( SessionArray $session )
    {
        $this->session = $session;
    }

    /**
     * @param string $uri
     * @return \HiMVC\Core\MVC\Request
     */
    public function createChild( $uri )
    {
        $child = clone $this;
        $child->uri = $uri;
        $child->uriArray = null;
        $child->isMain = false;
        $this->children[] = $child;
        return $child;
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
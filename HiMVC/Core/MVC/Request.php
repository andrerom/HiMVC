<?php
/**
 * Request object, parses uri to identify request data
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use eZ\Publish\Core\Base\Exceptions\Httpable as HttpableException,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    HiMVC\Core\MVC\Accept,
    HiMVC\Core\Base\AccessMatch,
    HiMVC\Core\Base\Module,
    HiMVC\Core\Base\SessionArray,
    eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Request class
 *
 * @property string $uri
 * @property array $uriArray
 * @property-read string $originalUri
 * @property array $params
 * @property-read array $cookies
 * @property-read array $files
 * @property \HiMVC\Core\Base\SessionArray $session
 * @property string $body
 * @property string $wwwDir
 * @property string $indexDir
 * @property string $action
 * @property float $ifModifiedSince
 * @property string $IfNoneMatch
 * @property string $protocol
 * @property string $host
 * @property int $port
 * @property string $contentType The content type of request body, like application/x-www-form-urlencoded', default: ''
 * @property \HiMVC\Core\MVC\Accept $accept
 * @property \HiMVC\Core\Base\AccessMatch[] $access
 * @property \HiMVC\Core\Base\Module[] $modules
 * @property string $authUser
 * @property string $authPwd
 * @property string $userAgent
 * @property string $referrer
 * @property float $microTime
 * @property-read array $raw
 */
class Request extends ValueObject
{
    /*
     * @var string The uri string, must not start or end in a '/'
     */
    protected $uri = '';

    /**
     * @var array Array version of $uri
     */
    protected $uriArray = array();

    /**
     * @var string Original request url, this is read only and set on first write to $uri.
     */
    protected $originalUri;

    /**
     * @var array GET params
     */
    protected $params = array();

    /**
     * @var array COOKIE params
     */
    protected $cookies = array();

    /**
     * @var array Upload files
     */
    protected $files = array();

    /**
     * @var \HiMVC\Core\Base\SessionArray
     */
    protected $session = array();

    /**
     * @var \HiMVC\Core\Base\AccessMatch[]
     */
    protected $access = array();

    /**
     * @var \HiMVC\Core\Base\Module[]
     */
    protected $modules = array();

    /**
     * @var mixed Request body
     */
    protected $body = '';

    /**
     * @var string The dir the install is placed in relative to hostname, must start and end in a '/'
     */
    protected $wwwDir = '/';

    /**
     * @var string Same as $wwwDir, but with the index.php or similar index file IF currently part of url
     */
    protected $indexDir = '/';

    /**
     * @var string One of the CRUD verbs; Create, Retrieve, Update or Delete
     */
    protected $action = 'Retrieve';

    /**
     * @var int If request asks for If-Modified-Since to get a full result, otherwise a not-modifed result
     */
    protected $ifModifiedSince = 0;

    /**
     * @var string If-None-Match=Etag, alternative If-Modified-Since where etag is matched to see if content has been modified
     */
    protected $IfNoneMatch = '';

    /**
     * @var string
     * @todo: rename and make it parse the value to something that can be used generally
     */
    protected $cacheControl = 'max-age=0';

    /**
     * @var string
     */
    protected $protocol = 'HTTP/1.1';

    /**
     * @var string
     */
    protected $host = 'localhost';

    /**
     * @var int
     */
    protected $port = 80;

    /**
     * @var string
     */
    protected $contentType = '';

    /**
     * @var \HiMVC\Core\MVC\Accept
     */
    protected $accept;

    /**
     * @var string
     */
    protected $authUser = '';

    /**
     * @var string
     */
    protected $authPwd = '';

    /**
     * @var string
     */
    protected $userAgent = '';

    /**
     * @var string
     */
    protected $referrer = '';

    /**
     * @var float Time the request was created
     */
    protected $microTime = 0.0;

    /**
     * @var array The raw $_SERVER variable
     */
    protected $raw = array();

    /**
     * @var array List of child requests witin this request
     */
    protected $childRequests = array();

    /**
     * Nested request exceptions
     * @see getException()
     * @see setException()
     *
     * @var \eZ\Publish\Core\Base\Exceptions\Httpable|null
     */
    private $exception = null;

    /**
     * __constructor
     *
     * @param array $params Parameters to request object (GET params in HTTP)
     * @param array $cookies Optional cookies, default empty array
     * @param array $files Optional files, default is empty array
     * @param array $raw Raw $_SERVER variable
     */
    public function __construct( array $params = array(),
                                 array $cookies = array(),
                                 array $files   = array(),
                                 array $raw = array() )
    {
        $this->params = $params;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->raw = $raw;
    }

    /**
     * 'Magic' PHP function to set values on protected properties
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     * @param string $name
     * @param mixed $value
     */
    public function __set( $name, $value )
    {
        if ( $value === null )
        {
            throw new InvalidArgumentException( $name, 'does not take "null" as value on '. __CLASS__ );
        }

        // @todo Change setters that forces a type to use set methods instead
        switch ( $name )
        {
            case 'uri' :
                $this->uri = $value;
                $this->uriArray = false;// Lazy load, {@see __get()}
                if ( $this->originalUri === null )
                    $this->originalUri = $value;// read only, set on first write to ->uri
                break;
            case 'uriArray' :
                $this->uriArray = $value;
                $this->uri = implode( '/', $value );
                break;
            case 'action' :
                if ( $value === 'Create' || $value === 'Retrieve' || $value === 'Update' || $value === 'Delete' )
                    $this->action = $value;
                else
                    throw new InvalidArgumentException(
                        $name,
                        'property can only have one of the following values: Create, Retrieve, Update or Delete'
                    );
                break;
            case 'raw' :
            case 'files' :
            case 'params' :
            case 'access' :
            case 'modules' :
            case 'cookies' :
            case 'session' :
            case 'exception' :
            case 'originalUri' :
                throw new InvalidArgumentException( $name, 'is a readonly property on '. __CLASS__ );
            case 'accept' :
                if ( $value instanceof Accept )
                    $this->accept = $value;
                else
                    throw new InvalidArgumentException( $name, 'can only be of type Accept' );
                break;
            default:
                if ( isset( $this->$name ) )
                    $this->$name = $value;
                else
                    throw new InvalidArgumentException( $name, 'is not a valid property on '. __CLASS__ );
        }
    }

    /**
     * 'Magic' PHP function to get values in {@link Request::$data} hash
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        if ( $name === 'uriArray' && $this->uriArray === false )
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
     * @param \HiMVC\Core\Base\SessionArray $session
     */
    public function setSession( SessionArray $session )
    {
        $this->session = $session;
    }

    /**
     * Return request exception property
     *
     * @return \eZ\Publish\Core\Base\Exceptions\Httpable|null
     */
    final public function getException()
    {
        return $this->exception;
    }

    /**
     * Set request exception property
     *
     * @param \eZ\Publish\Core\Base\Exceptions\Httpable $exception
     * @return \eZ\Publish\Core\Base\Exceptions\Httpable
     */
    final public function setException( HttpableException $exception )
    {
        return $this->exception = $exception;
    }

    /**
     * @param string $uri
     * @return Request
     */
    public function createChild( $uri )
    {
        $child = clone $this;
        $child->uri = $uri;
        $child->uriArray = false;
        $this->childRequests[] = $child;
        return $child;
    }

    /**
     * Protectes clone so it is only accessible via createChild()
     */
    protected function __clone()
    {
    }

    /**
     * Generates a uri array by uri string
     *
     * @param string $uri
     * @return array
     */
     protected static function arrayByUri( $uri )
     {
         if ( isset( $uri[1] ) )
         {
             if ( strrpos( $uri, '/' ) > 0 )
                 return explode( '/', trim( $uri, '/' ) );
             return array( trim( $uri, '/' ) );
         }
         else if ( isset( $uri[0] ) && $uri[0] !== '/' )
         {
             return array( $uri[0] );
         }
         return array();
     }
}
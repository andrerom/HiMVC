<?php
/**
 * Request object, parses uri to identify request data
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

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
 * @property-read string $uri
 * @property-read array $uriArray
 * @property-read string $originalUri
 * @property-read array $params
 * @property-read array $cookies
 * @property-read array $files
 * @property-read array|\HiMVC\Core\Base\SessionArray $session
 * @property-read mixed $body
 * @property-read string $wwwDir
 * @property-read string $indexDir
 * @property-read string $method
 * @property-read float $ifModifiedSince
 * @property-read string $IfNoneMatch
 * @property-read string $scheme
 * @property-read string $host
 * @property-read int $port
 * @property-read string $mimeType The content type of request body, like application/x-www-form-urlencoded', default: ''
 * @property-read \HiMVC\Core\MVC\Accept $accept
 * @property-read \HiMVC\Core\Base\AccessMatch[] $access
 * @property-read \HiMVC\Core\Base\Module[] $modules
 * @property-read string $authUser
 * @property-read string $authPwd
 * @property-read string $userAgent
 * @property-read string $referrer
 * @property-read float $microTime
 * @property-read array $raw
 * @property-read \HiMVC\API\MVC\Values\Request[] $childRequests
 */
abstract class Request extends ValueObject
{
    /*
     * @var string The uri string, must not start or end in a '/'
     */
    protected $uri = '';

    /**
     * @var array Array version of $uri
     */
    protected $uriArray;

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
     * @var array|\HiMVC\Core\Base\SessionArray
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
     * @var string HTTP method: GET, HEAD, POST, PUT, DELETE, ...
     */
    protected $method = 'GET';

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
    protected $scheme = 'http';

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
    protected $mimeType = '';

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
     * Add a acces match to list of matches and remove uri if there is one (must be left most part)
     *
     * @param \HiMVC\Core\Base\AccessMatch $access
     */
    abstract public function appendAccessMatch( AccessMatch $access );

    /**
     * Add a module to list of modules
     *
     * @param \HiMVC\Core\Base\Module $module
     */
    abstract public function appendModule( Module $module );

    /**
     * @param \HiMVC\Core\MVC\Accept $accept
     */
    abstract public function setAccept( Accept $accept );

    /**
     * @param \HiMVC\Core\Base\SessionArray $session
     */
    abstract public function setSession( SessionArray $session );

    /**
     * @param string $uri
     * @return \HiMVC\API\MVC\Values\Request
     */
    abstract public function createChild( $uri );

    /**
     * Protectes clone so it is only accessible via createChild()
     */
    protected function __clone()
    {
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
}
<?php
/**
 * Request object, parses uri to identify request data
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use eZ\Publish\Core\Base\Exceptions\Httpable as HttpableException,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    HiMVC\Core\MVC\Values\Accept,
    HiMVC\Core\MVC\Values\AccessMatch,
    HiMVC\Core\Common\Module,
    HiMVC\Core\Common\SessionArray,
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
 * @property-read array|\HiMVC\Core\Common\SessionArray $session
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
 * @property-read \HiMVC\Core\MVC\Values\Accept $accept
 * @property-read \HiMVC\Core\MVC\Values\AccessMatch[] $access
 * @property-read \HiMVC\Core\Common\Module[] $modules
 * @property-read string $authUser
 * @property-read string $authPwd
 * @property-read string $userAgent
 * @property-read string $referrer
 * @property-read float $microTime
 * @property-read array $raw
 * @property-read \HiMVC\API\MVC\Values\Request[] $children
 */
abstract class Request extends ValueObject
{
    /*
     * @var string The uri string, must not start or end in a '/'
     */
    public $uri = '';

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
    public $params = array();

    /**
     * @var array COOKIE params
     */
    public $cookies = array();

    /**
     * @var array Upload files
     */
    public $files = array();

    /**
     * @var array|\HiMVC\Core\Common\SessionArray
     */
    public $session = array();

    /**
     * @var \HiMVC\Core\MVC\Values\AccessMatch[]
     */
    public $access = array();

    /**
     * @var \HiMVC\Core\Common\Module[]
     */
    public $modules = array();

    /**
     * @var mixed Request body
     */
    public $body = '';

    /**
     * @var string The dir the install is placed in relative to hostname, must start and end in a '/'
     */
    public $wwwDir = '/';

    /**
     * @var string Same as $wwwDir, but with the index.php or similar index file IF currently part of url
     *             Must start and end in a '/'
     */
    public $indexDir = '/';

    /**
     * @var string HTTP method: GET, HEAD, POST, PUT, DELETE, ...
     */
    public $method = 'GET';

    /**
     * @var int If request asks for If-Modified-Since to get a full result, otherwise a not-modifed result
     */
    public $ifModifiedSince = 0;

    /**
     * @var string If-None-Match=Etag, alternative If-Modified-Since where etag is matched to see if content has been modified
     */
    public $IfNoneMatch = '';

    /**
     * @var string
     * @todo: rename and make it parse the value to something that can be used generally
     */
    public $cacheControl = 'max-age=0';

    /**
     * @var string
     */
    public $scheme = 'http';

    /**
     * @var string
     */
    public $host = 'localhost';

    /**
     * @var int
     */
    public $port = 80;

    /**
     * @var string Aka CONTENT_TYPE
     */
    public $mimeType = '';

    /**
     * @var \HiMVC\Core\MVC\Values\Accept
     */
    public $accept;

    /**
     * @var string
     */
    public $authUser = '';

    /**
     * @var string
     */
    public $authPwd = '';

    /**
     * @var string
     */
    public $userAgent = '';

    /**
     * @var string
     */
    public $referrer = '';

    /**
     * @var float Time the request was created
     */
    public $microTime = 0.0;

    /**
     * @var array The raw $_SERVER variable, this is not part of the public request api but exposed for specifc needs
     */
    public $raw = array();

    /**
     * @var \HiMVC\API\MVC\Values\Request[] List of child requests within this request
     */
    protected $children = array();

    /**
     * Nested request exceptions
     * @see getException()
     * @see setException()
     *
     * @var \eZ\Publish\Core\Base\Exceptions\Httpable|null
     */
    private $exception = null;

    /**
     * @param string $uri
     * @return \HiMVC\API\MVC\Values\Request
     */
    abstract public function createChild( $uri );

    /**
     * Return <schema://><domain>[:<port>]/<access-uri>/<uri>
     *
     * @param bool $host
     * @param bool $accessUri
     * @param bool $uri
     * @param bool $portIfStandard If false port is omitted if standard port for current schema
     *
     * @return string
     */
    abstract public function reverse( $host = false, $accessUri = true, $uri = true, $portIfStandard = false );

    /**
     * Tells if current request object is main (parent) request, or a embed request.
     *
     * @return bool
     */
    abstract public function isMain();

    /**
     * Protect clone so it is only accessible via createChild()
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
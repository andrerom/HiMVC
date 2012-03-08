<?php
/**
 * File contains Request parser class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use HiMVC\Core\MVC\Request,
    HiMVC\Core\MVC\Accept,
    HiMVC\Core\MVC\RequestParser,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Request parser cli addapter
 *
 * Possible way to allow requests be perfomed on commanline with host/port/user/pass/url as first argument
 * and optional post data as json string as second argument.
 */
class RequestParserCLI extends RequestParser
{
    /**
     * @var array
     */
    protected $uriComponents = array();

    /**
     * Parse request and create request object
     *
     * @param array $server
     * @param array $post
     * @param array $get
     * @param array $cookies
     * @param array $files
     * @param string $body
     * @param string $indexFile
     * @return \HiMVC\Core\MVC\Request
     */
    public function process(
            array $server,
            array $post = array(),
            array $get = array(),
            array $cookies = array(),
            array $files = array(),
                  $body = '',
                  $indexFile = 'index.php' )
    {
        if ( isset( $server['argv'][1] ) )
        {

            $this->uriComponents = parse_url( $server['argv'][1] );
            /* ["scheme"] => string(4) "http"
               ["host"] => string(15) "www.example.com"
               ["port"] => int(80)
               ["path"] => string(13) "/content/view"
               ["query"] => string(6) "test=0"
               ["fragment"] => string(4) "home" */
        }
        if ( isset( $server['argv'][2] ) )
        {
            $post = json_decode( $server['argv'][2], true );
            if ( $post === null )
            {
                throw new InvalidArgumentException( "\$server['argv'][2]", "Could not parse json string" );
            }
        }
        $req = parent::process( $server, $post, $get, $cookies, $files, $body, $indexFile );
        $req->userAgent = 'CLI';
        return $req;
    }

    /**
     * Look for port in http host name and set that to port param and only host
     * on host param.
     *
     * @param \HiMVC\Core\MVC\Request $req
     */
    protected function processHost( Request $req )
    {
        if ( isset( $this->uriComponents['host'] ) )
            $req->host = $this->uriComponents['host'];

        if ( isset( $this->uriComponents['port'] ) )
            $req->port = $this->uriComponents['port'];
    }

    /**
     * Processes the basic HTTP auth variables is set
     *
     * @param \HiMVC\Core\MVC\Request $req
     */
    protected function processAuthVars( Request $req  )
    {
        if ( isset( $this->uriComponents['user'] ) && isset( $this->uriComponents['pass']) )
        {
            $req->authUser = $this->uriComponents['user'];
            $req->authPwd = $this->uriComponents['pass'];
        }
    }

    /**
     * Get raw request URI
     *
     * @param \HiMVC\Core\MVC\Request $req
     * @return string
     */
    protected function getRequestUri( Request $req )
    {
        if ( isset( $this->uriComponents['path'] ) )
            return $this->uriComponents['path'];
        return '';
    }
}

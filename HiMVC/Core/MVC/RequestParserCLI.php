<?php
/**
 * File contains Request parser class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use HiMVC\Core\MVC\Values\Request,
    HiMVC\Core\MVC\Values\Accept,
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
     * @param array $settings
     * @return \HiMVC\API\MVC\Values\Request
     */
    public function process(
            array $server,
            array $post = array(),
            array $get = array(),
            array $cookies = array(),
            array $files = array(),
                  $body = '',
                  $indexFile = 'index.php',
            array $settings = array() )
    {
        if ( isset( $server['argv'][1] ) )
        {
            $this->uriComponents = parse_url( $server['argv'][1] );
        }

        if ( isset( $server['argv'][2] ) )
        {
            $post = json_decode( $server['argv'][2], true );
            if ( $post === null )
            {
                throw new InvalidArgumentException( "\$server['argv'][2]", "Could not parse json string" );
            }
        }

        $req = parent::process( $server, $post, $get, $cookies, $files, $body, $indexFile, $settings );
        return $req;
    }

    /**
     * Look for port in http host name and set that to port param and only host
     * on host param.
     *
     * @param array $server
     * @param array $data
     * @return void
     */
    protected function processHost( array $server, array &$data )
    {
        if ( isset( $this->uriComponents['host'] ) )
            $data['host'] = $this->uriComponents['host'];

        if ( isset( $this->uriComponents['port'] ) )
            $data['port'] = $this->uriComponents['port'];

        if ( isset( $this->uriComponents['scheme'] ) )
            $data['scheme'] = $this->uriComponents['scheme'];
    }

    /**
     * Processes the basic HTTP auth variables is set
     *
     * @param array $server
     * @param array $data
     */
    protected function processAuthVars( array $server, array &$data )
    {
        if ( isset( $this->uriComponents['user'] ) && isset( $this->uriComponents['pass']) )
        {
            $data['authUser'] = $this->uriComponents['user'];
            $data['authPwd'] = $this->uriComponents['pass'];
        }
    }

    /**
     * Processes the request body for PUT requests
     *
     * @param string $body
     * @param array $post
     * @param array $server
     * @param array $data
     */
    protected function processBody( $body, array $post, array $server, array &$data )
    {
        if ( isset( $server['argv'][2] ) && $post )
        {
            $data['body'] = $post;
            $data['mimeType'] = 'application/json';
        }
    }

    /**
     * Get raw request URI
     *
     * @param array $server
     * @return string
     */
    protected function getRequestUri( array $server )
    {
        if ( isset( $this->uriComponents['path'] ) )
            return $this->uriComponents['path'];

        return '';
    }
}

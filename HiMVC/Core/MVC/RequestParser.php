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
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Request parser for HTTP
 *
 * Use:
 * $parser = new RequestParser();
 * $request = $parser->parse( $_SERVER,
 *                            $_POST,
 *                            $_GET,
 *                            $_COOKIE,
 *                            $_FILES,
 *                            file_get_contents( "php://input" ),
 *                            'index.php'
 * );
 */
class RequestParser
{
    /**
     * @static
     * @param array $server
     * @param array $post
     * @param array $get
     * @param array $cookies
     * @param array $files
     * @param string $body
     * @param string $indexFile
     * @param array $settings
     * @todo Add settings for trusted proxies for X_FORWARDED_* headers use
     * @return Request
     * @throws \Exception
     */
    static final public function createRequest(
            array $server,
            array $post = array(),
            array $get = array(),
            array $cookies = array(),
            array $files = array(),
                 $body = '',
                 $indexFile = 'index.php',
            array $settings = array() )
    {
        // Possible addapters based on cli params or accept type if available in $addapters
        if ( isset( $server['CONTENT_TYPE'] ) && isset( $settings['HTTP']['Addapters'][ $_SERVER['CONTENT_TYPE'] ] ) )
            $requestParser = new $settings['HTTP']['Addapters'][ $server['CONTENT_TYPE'] ];
        else if ( isset( $server['argv'] ) )
            $requestParser = new RequestParserCLI;
        else
            $requestParser = new RequestParser;

        if ( !$requestParser instanceof RequestParser )//@todo Interface
            throw new \Exception( 'Request Parser addapters needs to extend HiMVC\Core\MVC\RequestParser' );

        return $requestParser->process( $server, $post, $get, $cookies, $files, $body, $indexFile, $settings );
    }

    protected $settings;

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
        $this->settings = $settings + array(
            'trustedProxys' => array(),// Use with X_FORWARDED_HOST, X_FORWARDED_PROTO, X_FORWARDED_FOR and X_FORWARDED_PORT
        );
        $data = $this->processStandardHeaders( $server ) + array(
            'raw' => $server,
            'params' => $get,
            'cookies' => $cookies,
            'files' => $files,
            'accept' => $this->processAcceptHeaders( $server ),
            'method' => $this->processMethod( $server, $post ),
        );

        // Needs to be after processStandardHeaders() as it will overwrite port it if there is a port in host name
        $this->processHost( $server, $data );

        $data['ifModifiedSince'] = $this->processIfModifiedSince( $server );

        $this->processAuthVars( $server, $data );
        $this->processRequestUri( $server, $data, $indexFile );

        // Depends on 'method' being processed already
        $this->processBody( $body, $post, $server, $data );

        $data['originalUri'] = $data['uri'];
        return new Request( $data );
    }

    /**
     * @param array $server
     * @return array The resulting data for request object
     */
    protected function processStandardHeaders( array $server )
    {
        $data = array();
        if ( isset( $server['REQUEST_TIME_FLOAT'] ) )
            $data['microTime'] = $server['REQUEST_TIME_FLOAT'];
        else
            $data['microTime'] = microtime( true );

        if ( isset( $server['HTTP_IF_NONE_MATCH'] ) )
            $data['IfNoneMatch'] = $server['HTTP_IF_NONE_MATCH'];

        if ( isset( $server['HTTP_REFERER'] ) )
            $data['referrer'] = $server['HTTP_REFERER'];

        if ( isset( $server['HTTP_USER_AGENT'] ) )
            $data['userAgent'] = $server['HTTP_USER_AGENT'];

        if ( isset( $server['SERVER_PORT'] ) )
            $data['port'] = $server['SERVER_PORT'];

        return $data;
    }

    /**
     * Create Accept object based on _SERVER hash
     *
     * @param array $server
     * @return \HiMVC\Core\MVC\Values\Accept
     */
     public function processAcceptHeaders( array $server )
    {
        $accept = new Accept();
        $map = array(
            'HTTP_ACCEPT', 'types',
            'HTTP_ACCEPT_ENCODING', 'encodings',
            'HTTP_ACCEPT_LANGUAGE', 'languages',
        );

        for ( $i = 0; isset( $map[$i] ); $i += 2 )
        {
            if ( !isset( $server[$map[$i]] ) )
            {
                continue;
            }

            $parts = explode( ',', $server[$map[$i]] );
            $priorities = array();
            for ($y = 0; isset( $parts[$y] ); ++$y)
            {
                // @todo consider caring about priority, but these are usually in order anyway
                $priPos = strpos( $parts[$y], ';q=' );
                if ( $priPos !== false )
                {
                    $priorities[] = substr( $parts[$y], 0, $priPos );
                }
                else
                {
                    $priorities[] = $parts[$y];
                }
            }
            $accept->$map[$i+1] = $priorities;
        }
        return $accept;
    }

    /**
     * Map http method using post params if present
     *
     * @param array $server
     * @param array $post
     * @return string
     */
    protected function processMethod( array $server, array $post = array() )
    {
        $method = isset( $server['REQUEST_METHOD'] ) ? $server['REQUEST_METHOD'] : ( empty( $post ) ? 'GET' : 'POST' );

        // Allow POST to be used for DELETE and PUT
        if ( $method === 'POST' && isset( $post['_method'] ) &&
            ( $post['_method'] === 'DELETE' || $post['_method'] === 'PUT' ) )
        {
            return $post['_method'];
        }
        return $method;
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
        if ( isset( $server['HTTP_HOST'] ) )
            $host = $server['HTTP_HOST'];
        else if ( isset( $server['SERVER_NAME'] ) )
            $host = $server['SERVER_NAME'];
        else
            return;

        if ( strpos( $host, ':' ) !== false )
        {
            $host = explode( ':', $host );
            $data['host'] = $host[0];
            $data['port'] = $host[1];
        }
        else
        {
            $data['host'] = $host;
        }

        if ( isset( $server['HTTPS'] ) && ( $server['HTTPS'] === 'on' || $server['HTTPS'] === 1 ) )
        {
            $data['scheme'] = 'https';
        }
    }

    /**
     * Fix legacy IE specific issues with ifModifiedSince and
     * parse string to unix timestamp
     *
     * @param array $server
     * @return int
     */
    protected function processIfModifiedSince( array $server )
    {
        if ( isset( $server['HTTP_IF_MODIFIED_SINCE'] ) && $server['HTTP_IF_MODIFIED_SINCE'] )
            $ifModifiedSince = $server['HTTP_IF_MODIFIED_SINCE'];
        else
            return 0;

        // @todo Is this really needed? wasn't this to wrok around IE5 issues?
        if ( false !== ( $pos = strpos( $ifModifiedSince, ';' ) ) )
            $ifModifiedSince = substr( $ifModifiedSince, 0, $pos );

        return strtotime( $ifModifiedSince ) ?: 0;
    }

    /**
     * Processes the basic HTTP auth variables is set
     *
     * @param array $server
     * @param array $data
     */
    protected function processAuthVars( array $server, array &$data )
    {
        if ( isset( $server['PHP_AUTH_USER'] ) && isset( $server['PHP_AUTH_PW'] ) )
        {
            $data['authUser'] = $server['PHP_AUTH_USER'];
            $data['authPwd'] = $server['PHP_AUTH_PW'];
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
        // @todo Figgure out a way to do body, especially in regards to post data + content type ('php'?)
        if ( isset( $server['CONTENT_TYPE'] ) )
            $data['mimeType'] = $server['CONTENT_TYPE'];

        if ( $data['method'] !== 'PUT' )
        {
            $data['body'] = $body;
        }
    }

    /**
     * Decode raw request url and to figgure out www and index paths
     *
     * @param array $server
     * @param array $data
     * @param string $indexFile
     */
    protected function processRequestUri( array $server, array &$data, $indexFile )
    {
        $requestUri = $this->getRequestUri( $server );
        $wwwDir = $this->getWWWDir( $server['PHP_SELF'], $server['SCRIPT_FILENAME'], $indexFile );
        if ( $wwwDir !== null && $wwwDir !== false )// '' is valid
        {
            // Auto detect IIS vh mode & Apache .htaccess mode
            if ( ( isset( $server['IIS_WasUrlRewritten'] ) && $server['IIS_WasUrlRewritten'] )
              || ( isset( $server['REDIRECT_URL'] ) && isset( $server['REDIRECT_STATUS'] ) && $server['REDIRECT_STATUS'] == '200' ) )
            {
                $wwwDir = '/'. $wwwDir;
                $indexDir = $wwwDir;

                // Remove sub path from requestUri
                if( $wwwDir === $requestUri )
                    $requestUri = '';
                elseif ( $wwwDir !== '/' && ( $wwwDirPos = strpos( $requestUri, $wwwDir ) ) !== false )
                    $requestUri = substr( $requestUri, $wwwDirPos + strlen($wwwDir) );
            }
            else // Non virtual host mode, use phpSelf to figure out paths
            {
                $wwwDir = '/'. $wwwDir;
                $indexDir = $wwwDir . $indexFile;

                // Remove sub path from requestUri
                if ( $requestUri === $indexDir )
                    $requestUri = '';
                elseif ( ( $indexDirPos = strpos( $requestUri, $indexDir ) ) !== false )
                    $requestUri = substr( $requestUri, $indexDirPos + strlen($indexDir) );
                elseif( $wwwDir === $requestUri )
                    $requestUri = '';
                elseif ( $wwwDir !== '/' && ( $wwwDirPos = strpos( $requestUri, $wwwDir ) ) !== false )
                    $requestUri = substr( $requestUri, $wwwDirPos + strlen($wwwDir) );

                // Append slash on index dir so it can be prepended with requestUri directly to create full url
                $indexDir .= '/';
            }
            $data['wwwDir'] = $wwwDir;
            $data['indexDir'] = $indexDir;
        }// else Use defaults, as in vh mode with empty www dir

        // Remove url, hash and type parameters
        if ( isset( $requestUri[1] ) && $requestUri !== '/'  )
        {
            if ( $requestUri[0] === '?' || $requestUri === '/?' )
                $requestUri = '';
            elseif ( ( $uriGetPos = strpos( $requestUri, '?' ) ) !== false )
                $requestUri = substr( $requestUri, 0, $uriGetPos );

            if (  !isset( $requestUri[0]) || $requestUri[0] === '#' || $requestUri === '/#' )
                $requestUri = '';
            elseif ( ($uriHashPos = strpos( $requestUri, '#' )) !== false )
                $requestUri = substr( $requestUri, 0, $uriHashPos );
        }

        // Normalize slash use and url decode url if needed
        if ( $requestUri === '/' || !$requestUri )
            $data['uri'] = '';
        else
            $data['uri'] = urldecode( trim( $requestUri, '/ ' ) );

    }

    /**
     * Get raw request URI
     *
     * @param array $server
     * @return string
     */
    protected function getRequestUri( array $server )
    {
        return $server['REQUEST_URI'];
    }

    /**
     * Generate wwwDir from phpSelf if valid according to scriptFileName
     * and return null if invalid and false if there is no index in phpSelf
     *
     * @param string $phpSelf
     * @param string $scriptFileName
     * @param string $indexFile
     * @return string|null|boolean String in form 'path/path2' if valid, null if not
     *                           and false if $indexFile is not part of phpSelf
     */
    protected function getWWWDir( $phpSelf, $scriptFileName, $indexFile )
    {
        // Exit w/ false if phpSelf is to short to contain path
        if ( !isset( $phpSelf[1] ) )
            return false;

        // Exit w/ false if phpSelf does not contain index
        $phpSelfIndexPos = strpos( $phpSelf, $indexFile );
         if ( $phpSelfIndexPos === false )
            return false;

        // Exit w/ null if scriptFileName does not contain index
        if ( strpos( $scriptFileName, $indexFile ) === false )
            return null;

        // Optimize '/index.php' pattern
        if ( $phpSelf === $indexFile || $phpSelf === "/{$indexFile}" )
            return '';

        // Get phpSelf part before index
        $phpSelfWWWDir = substr( $phpSelf, 0, $phpSelfIndexPos );

        // Remove first path if home dir
        if ( $phpSelf[1] === '~' )
        {
            $uri = explode( '/', ltrim( $phpSelfWWWDir, '/' ) );
            array_shift( $uri );
            $validateDir = '/' . implode( '/', $uri );
        }
        else
        {
            $validateDir = $phpSelfWWWDir;
        }

        // Validate scriptFileName directly with phpSelf part
        if ( strpos( $scriptFileName, $validateDir ) !== false )
            return ltrim( $phpSelfWWWDir, '/' );

        // Validate scriptFileName with phpSelf part using Windows path
        if ( strpos( $scriptFileName, str_replace( '/', '\\', $validateDir ) ) !== false )
            return ltrim( $phpSelfWWWDir, '/' );

        return null;
    }
}

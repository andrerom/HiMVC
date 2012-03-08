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

        return $requestParser->process( $server, $post, $get, $cookies, $files, $body, $indexFile );
    }
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
        $req = new Request( $get, $cookies, $files, $server );
        $this->processStandardHeaders( $req );
        $this->processAcceptHeaders( $req );
        $this->processMethod( $req, $post );

        // Needs to be after processStandardHeaders() as it will overwrite port it if there is a port in host name
        $this->processHost( $req );

        $this->processIfModifiedSince( $req);

        $this->processAuthVars( $req );
        $this->processRequestUri( $req, $indexFile );

        // Depends on 'method' being processed already
        $this->processBody( $req, $body, $post );

        return $req;
    }

    /**
     * @param \HiMVC\Core\MVC\Request $req
     */
    protected function processStandardHeaders( Request $req )
    {
        if ( isset( $req->raw['REQUEST_TIME_FLOAT'] ) )
            $req->microTime = $req->raw['REQUEST_TIME_FLOAT'];
        else
            $req->microTime = microtime( true );

        if ( isset( $req->raw['HTTP_IF_NONE_MATCH'] ) )
            $req->IfNoneMatch = $req->raw['HTTP_IF_NONE_MATCH'];

        if ( isset( $req->raw['HTTP_REFERER'] ) )
            $req->referrer = $req->raw['HTTP_REFERER'];

        if ( isset( $req->raw['HTTP_USER_AGENT'] ) )
            $req->userAgent = $req->raw['HTTP_USER_AGENT'];

        if ( isset( $req->raw['SERVER_PORT'] ) )
            $req->port = $req->raw['SERVER_PORT'];
    }

    /**
     * Create Accept object based on _SERVER hash
     *
     * @param \HiMVC\Core\MVC\Request $req
     */
     public function processAcceptHeaders( Request $req )
    {
        $accept = new Accept();
        $map = array(
            'HTTP_ACCEPT', 'types',
            'HTTP_ACCEPT_ENCODING', 'encodings',
            'HTTP_ACCEPT_LANGUAGE', 'languages',
        );

        for ( $i = 0; isset( $map[$i] ); $i += 2 )
        {
            if ( !isset( $req->raw[$map[$i]] ) )
            {
                continue;
            }

            $parts = explode( ',', $req->raw[$map[$i]] );
            $priorities = array();
            for ($y = 0; isset( $parts[$y] ); $y++)
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
        $req->accept = $accept;
    }

    /**
     * Map http method verbs to CRUD verbs, including allowing POST to specify DELETE and PUT
     * verbs with _method param like in Rails
     *
     * @param \HiMVC\Core\MVC\Request $req
     * @param array $post
     */
    protected function processMethod( Request $req, array $post = array() )
    {
        $method = isset( $req->raw['REQUEST_METHOD'] ) ?
                  $req->raw['REQUEST_METHOD'] :
                  ( empty($post) ? 'GET' : 'POST' );

        switch ( $method )
        {
            case 'POST':
                $action = 'Create';

                if ( !isset( $post['_method'] ) )
                    break;
                // Allow POST to be used for DELETE and PUT
                if ( $post['_method'] === 'DELETE' )
                {
                    $action = 'Delete';
                }
                else if ( $post['_method'] === 'PUT' )
                {
                    $action = 'Update';
                }
                break;
            case 'PUT':
                $action = 'Update'; // Replace in case of collection
                break;
            case 'DELETE':
                $action = 'Delete';
                break;
            default: // GET / HEAD (and OPTIONS, meaning the latter is not really supported atm)
                $action = 'Retrieve';
        }
        $req->action = $action;
    }

    /**
     * Look for port in http host name and set that to port param and only host
     * on host param.
     *
     * @param \HiMVC\Core\MVC\Request $req
     */
    protected function processHost( Request $req )
    {
        if ( isset( $req->raw['HTTP_HOST'] ) )
            $host = $req->raw['HTTP_HOST'];
        else if ( isset( $req->raw['SERVER_NAME'] ) )
            $host = $req->raw['SERVER_NAME'];
        else
            return;

        if ( strpos( $host, ':' ) !== false )
        {
            $host = explode( ':', $host );
            $req->host = $host[0];
            $req->port = $host[1];
        }
        else
        {
            $req->host = $host;
        }
    }

    /**
     * Fix legacy IE specific issues with ifModifiedSince and
     * parse string to unix timestamp
     *
     * @param \HiMVC\Core\MVC\Request $req
     */
    protected function processIfModifiedSince( Request $req )
    {
        if ( isset( $req->raw['HTTP_IF_MODIFIED_SINCE'] ) && $req->raw['HTTP_IF_MODIFIED_SINCE'] )
            $ifModifiedSince = $req->raw['HTTP_IF_MODIFIED_SINCE'];
        else
            return;

        // @todo Is this really needed? wasn't this to wrok around IE5 issues?
        $pos = strpos( $ifModifiedSince, ';' );// Legacy Internet Explorer specific
        if ( $pos !== false )
            $ifModifiedSince = substr( $ifModifiedSince, 0, $pos );

        $req->ifModifiedSince = strtotime( $ifModifiedSince );
    }

    /**
     * Processes the basic HTTP auth variables is set
     *
     * @param \HiMVC\Core\MVC\Request $req
     */
    protected function processAuthVars( Request $req  )
    {
        if ( isset( $req->raw['PHP_AUTH_USER'] ) && isset( $req->raw['PHP_AUTH_PW'] ) )
        {
            $req->authUser = $req->raw['PHP_AUTH_USER'];
            $req->authPwd = $req->raw['PHP_AUTH_PW'];
        }
    }

    /**
     * Processes the request body for PUT requests
     *
     * @param \HiMVC\Core\MVC\Request $req
     * @param string $body
     * @param array $post
     */
    protected function processBody( Request $req, $body, array $post )
    {
        // @todo Figgure out a way to do body, especially in regards to post data + content type ('php'?)
        if ( isset( $req->raw['CONTENT_TYPE'] ) )
            $req->contentType = $req->raw['CONTENT_TYPE'];

        if ( $req->action === 'Update' )
        {
            $req->body = $body;
        }
    }

    /**
     * Decode raw request url and to figgure out www and index paths
     *
     * @param \HiMVC\Core\MVC\Request $req
     * @param string $indexFile
     */
    protected function processRequestUri( Request $req, $indexFile )
    {
        $phpSelf = $req->raw['PHP_SELF'];
        $requestUri = $this->getRequestUri( $req );

        $wwwDir = $this->getWWWDir( $phpSelf, $req->raw['SCRIPT_FILENAME'], $indexFile );
        if ( $wwwDir !== null && $wwwDir !== false )// '' is valid
        {
            // Auto detect IIS vh mode & Apache .htaccess mode
            if ( ( isset( $req->raw['IIS_WasUrlRewritten'] ) && $req->raw['IIS_WasUrlRewritten'] )
              || ( isset( $req->raw['REDIRECT_URL'] ) && isset( $req->raw['REDIRECT_STATUS'] ) && $req->raw['REDIRECT_STATUS'] == '200' ) )
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
            $req->wwwDir = $wwwDir;
            $req->indexDir = $indexDir;
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
            $req->uri = '';
        else
            $req->uri = urldecode( trim( $requestUri, '/ ' ) );

    }

    /**
     * Get raw request URI
     *
     * @param \HiMVC\Core\MVC\Request $req
     * @return string
     */
    protected function getRequestUri( Request $req )
    {
        return $req->raw['REQUEST_URI'];
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

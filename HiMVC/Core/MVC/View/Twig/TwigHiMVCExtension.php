<?php
/**
 * File contains TwigDispatcherExtension class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\View\Twig;

use HiMVC\Core\MVC\Dispatcher;
use HiMVC\Core\MVC\Router;
use HiMVC\Core\MVC\View\ViewDispatcher;
use HiMVC\Core\MVC\View\DesignLoader;
use HiMVC\API\MVC\Values\Request;
use HiMVC\API\MVC\Values\Result;
use Twig_Extension;
use Twig_Environment;
use Twig_Function_Method;

/**
 * TwigDispatcherExtension
 *
 * Extends twig by adding 'dispatch' function for hmvc use.
 */
class TwigHiMVCExtension extends Twig_Extension
{
    /**
     * @var \HiMVC\Core\MVC\Dispatcher
     */
    protected $dispatcher;

    /**
     * @var \HiMVC\Core\MVC\Router
     */
    protected $router;

    /**
     * @var \HiMVC\Core\MVC\View\ViewDispatcher
     */
    protected $viewDispatcher;

    /**
     * @var \HiMVC\Core\MVC\View\DesignLoader
     */
    protected $designLoader;

    /**
     * @param \HiMVC\Core\MVC\Dispatcher $dispatcher
     * @param \HiMVC\Core\MVC\Router $router
     * @param \HiMVC\Core\MVC\View\ViewDispatcher $viewDispatcher
     * @param \HiMVC\Core\MVC\View\DesignLoader $designLoader
     */
    public function __construct( Dispatcher $dispatcher, Router $router, ViewDispatcher $viewDispatcher, DesignLoader $designLoader )
    {
        $this->dispatcher = $dispatcher;
        $this->router = $router;
        $this->viewDispatcher = $viewDispatcher;
        $this->designLoader = $designLoader;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'dispatch' => new Twig_Function_Method( $this, 'dispatch', array( 'is_safe' => array( 'html' ) ) ),
            'view' => new Twig_Function_Method( $this, 'view', array( 'is_safe' => array( 'html' ) ) ),
            'link' => new Twig_Function_Method( $this, 'link' ),
            'design' => new Twig_Function_Method( $this, 'design' )
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'HiMVC';
    }

    /**
     * Dispatch request
     *
     * @uses \HiMVC\Core\MVC\Dispatcher::dispatch()
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @return Response An object that can be casted to string
     */
    public function dispatch( Request $request )
    {
        return $this->dispatcher->dispatch( $request );
    }

    /**
     * Generate Response for Result+Requst object
     *
     * @uses \HiMVC\Core\MVC\View\ViewDispatcher::view()
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param \HiMVC\API\MVC\Values\Result $result
     * @return Response An object that can be casted to string
     */
    public function view( Request $request, Result $result )
    {
        return $this->viewDispatcher->view( $request, $result );
    }

    /**
     * Generate link to a Result object
     *
     * @uses \HiMVC\Core\MVC\Router::reverse()
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param \HiMVC\API\MVC\Values\Result $result
     * @param array $params
     * @param bool $hostName
     * @return string URI to Result object with or with out hostname
     */
    public function link( Request $request, Result $result, array $params = array(), $hostName = false )
    {
        // Append host name if asked for
        $host = '';
        if ( $hostName )
        {
            $host = $request->scheme . '://'  . $request->host;
        }

        // Put $params that exists in $result->params in resulting $uriParams, and rest as $query params
        $query = '';
        $uriParams = $result->params;
        foreach ( $params as $key => $value )
        {
            if ( isset( $uriParams[$key] ) )
                $uriParams[$key] = $value;
            else
                $query = ( $query === '' ? '?' : '&' ) . $key . '=' . $value;
        }

        // Put them all thogheter and get router uri based on info in result object
        $route = $this->router->reverse( $result->controller, $result->action, $uriParams );
        return $host .
            $request->indexDir .
            $route[1] .
            $query;
    }

    /**
     * Lookup static asset using design loader.
     *
     * @todo CSS / JS assets should be handled differently, this is more aimed at inline design images use.
     * @uses \HiMVC\Core\MVC\View\DesignLoader::getPath()
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param string $file
     * @param bool $hostName
     * @return string
     */
    public function design( Request $request, $file, $hostName = false )
    {
        // Append host name if asked for @todo Host map based on asset file ending
        $host = '';
        if ( $hostName )
        {
            $host = $request->scheme . '://'  . $request->host;
        }
        return $host . $request->wwwDir . $this->designLoader->getPath( $file );
    }
}
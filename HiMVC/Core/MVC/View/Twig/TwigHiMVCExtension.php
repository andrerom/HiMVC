<?php
/**
 * File contains TwigDispatcherExtension class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\View\Twig;

use HiMVC\Core\MVC\Dispatcher;
use HiMVC\Core\MVC\View\ViewDispatcher;
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
     * @var \HiMVC\Core\MVC\View\ViewDispatcher
     */
    protected $viewDispatcher;

    /**
     * @param \HiMVC\Core\MVC\Dispatcher $dispatcher
     * @param \HiMVC\Core\MVC\View\ViewDispatcher $viewDispatcher
     */
    public function __construct( Dispatcher $dispatcher, ViewDispatcher $viewDispatcher )
    {
        $this->dispatcher = $dispatcher;
        $this->viewDispatcher = $viewDispatcher;
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
            'view' => new Twig_Function_Method( $this, 'viewDispatcher', array( 'is_safe' => array( 'html' ) ) ),
            'link' => new Twig_Function_Method( $this, 'link' )
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
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param \HiMVC\API\MVC\Values\Result $result
     * @return Response An object that can be casted to string
     */
    public function viewDispatcher( Request $request, Result $result )
    {
        return $this->viewDispatcher->view( $request, $result );
    }

    /**
     * Generate link to a Result object
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param \HiMVC\API\MVC\Values\Result $result
     * @param bool $hostName
     * @return string URI to Result object with or with out hostname
     */
    public function link( Request $request, Result $result, $hostName = false )
    {
        $host = '';
        if ( $hostName )
        {
            $host = $request->scheme . '://'  . $request->host;
        }
        return $host . $request->indexDir . $result->uri;
    }
}
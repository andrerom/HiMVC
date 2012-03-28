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
use HiMVC\Core\MVC\ViewDispatcher;
use HiMVC\Core\MVC\Request;
use HiMVC\API\MVC\Values\Result;
use Twig_Extension;
use Twig_Environment;
use Twig_Function_Method;

/**
 * TwigDispatcherExtension
 *
 * Extends twig by adding 'dispatch' function for hmvc use.
 */
class TwigDispatcherExtension extends Twig_Extension
{
    /**
     * @var \HiMVC\Core\MVC\Dispatcher
     */
    protected $dispatcher;

    /**
     * @var \HiMVC\Core\MVC\ViewDispatcher
     */
    protected $viewDispatcher;

    /**
     * @param \HiMVC\Core\MVC\Dispatcher $dispatcher
     * @param \HiMVC\Core\MVC\ViewDispatcher $viewDispatcher
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
            'dispatch' => new Twig_Function_Method( $this, 'dispatch' ),
            'view' => new Twig_Function_Method( $this, 'viewDispatcher' )
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'dispatcher';
    }

    /**
     * Dispatch request
     *
     * @param \HiMVC\Core\MVC\Request $request
     * @return Response An object that can be casted to string
     */
    public function dispatch( Request $request )
    {
        return $this->dispatcher->dispatch( $request );
    }

    /**
     * Generate Response for Result+Requst object
     *
     * @param \HiMVC\Core\MVC\Request $request
     * @param \HiMVC\API\MVC\Values\Result $result
     * @return Response An object that can be casted to string
     */
    public function viewDispatcher( Request $request, Result $result )
    {
        return $this->viewDispatcher->view( $request, $result );
    }

}
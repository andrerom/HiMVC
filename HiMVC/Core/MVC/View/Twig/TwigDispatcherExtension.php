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

use HiMVC\Core\MVC\Dispatcher,
    HiMVC\Core\MVC\Request,
    Twig_Extension,
    Twig_Environment,
    Twig_Function_Method;

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
     * @param \HiMVC\Core\MVC\Router $router
     * @param \HiMVC\Core\MVC\ViewDispatcher $viewDispatcher
     */
    public function __construct( Dispatcher $dispatcher )
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'dispatch' => new Twig_Function_Method( $this, 'dispatch' )
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
     * Routes request
     *
     * @param \HiMVC\Core\MVC\Request $request
     * @return Response An object that can be casted to string
     */
    public function dispatch( Request $request )
    {
        return $this->dispatcher->dispatch( $request );
    }

}
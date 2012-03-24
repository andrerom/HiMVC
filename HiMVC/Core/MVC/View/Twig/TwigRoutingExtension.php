<?php
/**
 * File contains TwigRoutingExtension class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\View\Twig;

use HiMVC\Core\MVC\ViewDispatcher,
    HiMVC\Core\MVC\Router,
    HiMVC\Core\MVC\Request,
    Twig_Extension,
    Twig_Environment,
    Twig_Function_Method;

/**
 * TwigRoutingExtension
 *
 * Extends twig by adding 'route' function for hmvc use.
 */
class TwigRoutingExtension extends Twig_Extension
{
    /**
     * @var \HiMVC\Core\MVC\Router
     */
    protected $router;

    /**
     * @param \HiMVC\Core\MVC\Router $router
     * @param \HiMVC\Core\MVC\ViewDispatcher $viewDispatcher
     */
    public function __construct( Router $router, ViewDispatcher $viewDispatcher )
    {
        $this->router = $router;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'route' => new Twig_Function_Method( $this, 'route')
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'routing';
    }

    /**
     * Routes request
     *
     * @param \HiMVC\Core\MVC\Request $request
     * @return \HiMVC\Core\MVC\Result
     */
    public function route( Request $request )
    {
        return $this->router->route( $request );
    }
}
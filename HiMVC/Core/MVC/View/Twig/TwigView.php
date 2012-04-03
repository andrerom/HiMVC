<?php
/**
 * File contains TwigView class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\View\Twig;

use HiMVC\API\MVC\Viewable,
    HiMVC\API\MVC\Values\Request,
    HiMVC\Core\MVC\Router,
    Twig_Environment;

/**
 * Twig view handler
 *
 * @todo Deal with hmvc dependecies like router, request and viewDispatcher objects more nativly then $params
 */
class TwigView implements Viewable
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct( Twig_Environment $twig )
    {
        $this->twig = $twig;
    }

    /**
     * @param string $name The name of the template to execute (including folders if its is in any)
     *                     Eg: content/edit.tpl
     * @param array $params Objects/values that should be available in view.
     * @return string
     */
   public function render( $name, array $params )
   {
       $template = $this->twig->loadTemplate( $name );
       return $template->render( $params );
   }
}
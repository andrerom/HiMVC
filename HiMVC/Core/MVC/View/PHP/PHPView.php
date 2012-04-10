<?php
/**
 * File contains PHPView class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\View\PHP;

use HiMVC\API\MVC\Viewable,
    HiMVC\Core\MVC\View\DesignLoader;

/**
 * PHP view handler
 *
 * @todo Somehow generalize "template functions" so they can be reused between Viewable impl
 */
class PHPView implements Viewable
{
    /**
     * @var \HiMVC\Core\MVC\View\DesignLoader
     */
    protected $loader;

    /**
     * @param \HiMVC\Core\MVC\View\DesignLoader $loader
     */
    public function __construct( DesignLoader $loader )
    {
        $this->loader = $loader;
    }

    /**
     * @param string $name The name of the template to execute (including folders if its is in any)
     *                     Eg: content/edit.php
     * @param array $_phpParams Objects/values that should be available in view.
     * @return string
     */
   public function render( $name, array $_phpParams )
   {
       $_phpFile = $this->loader->getPath( $name );

       unset( $name );
       extract( $_phpParams );
       unset( $_phpParams );

       ob_start();
       require $_phpFile;
       return ob_get_clean();
   }
}
<?php
/**
 * File contains Viewable interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC;

/**
 * Forces the signature of the function used to excute the view
 *
 * All other dependecise needs to be enforced in constructor, such as:
 * - The template engine, if any, fully setup with any dependencies
 * - router & view handler for hmvc use (at least last one must be lazy loaded to avoid circular dependency)
 * - (...)
 *
 * Stuff that should not be injected for the sake of seperation of concerns:
 * - Reposiotry
 * - ServiceContainer
 * - (...)
 */
interface Viewable
{
    /**
     * @abstract
     * @param string $file Absolute path to file that should be executed
     * @param array $params Objects/values that should be available in view
     * @todo Addapt rendering/result/reponse stuff from MvcTools / Symfony 2
     */
   public function render( $file, array $params );
}

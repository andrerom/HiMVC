<?php
/**
 * Hello World Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

/**
 * Example Hello World controller
 */
class HelloController
{
    /**
     * A Hello World action.
     *
     * @return \HiMVC\Core\MVC\Values\Result
     */
    static public function world()
    {
        return __METHOD__ . '()';
    }
}


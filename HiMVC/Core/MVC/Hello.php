<?php
/**
 * Content Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;
use HiMVC\Core\MVC\Request;

/**
 * Example controller, does no chnages to data atm
 */
class Hello
{
    /**
     * @var \HiMVC\Core\MVC\Request
     */
    protected $request;

    /**
     * @param \HiMVC\Core\MVC\Request $request
     */
    public function __construct( Request $request )
    {
        $this->request = $request;
    }

    /**
     * Add new item in collection ( ie GET /hello/ )
     *
     * @return \HiMVC\Core\MVC\Result
     */
    public function doWorld()
    {
        return __METHOD__ . "()";
    }
}


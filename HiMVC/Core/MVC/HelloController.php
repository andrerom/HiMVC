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

use HiMVC\API\MVC\Values\Request as APIRequest;

/**
 * Example Hello World controller
 */
class HelloController
{
    /**
     * @var \HiMVC\API\MVC\Values\Request
     */
    protected $request;

    /**
     * @param \HiMVC\API\MVC\Values\Request $request
     */
    public function __construct( APIRequest $request )
    {
        $this->request = $request;
    }

    /**
     * Add new item in collection ( ie GET /hello/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doWorld()
    {
        return __METHOD__ . '()';
    }
}


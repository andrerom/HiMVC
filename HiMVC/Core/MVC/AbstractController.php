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
abstract class AbstractController
{
    /**
     * Execution point for controller actions
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param $action
     * @param array $params
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function run( APIRequest $request, $action, array $params = array() )
    {
        return call_user_func_array( array( $this, $action ), $params );
    }
}


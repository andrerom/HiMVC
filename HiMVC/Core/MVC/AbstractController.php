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
use HiMVC\API\MVC\Values\Result as APIResult;
use HiMVC\Core\MVC\View\ViewDispatcher;

/**
 * Abstract controller
 */
abstract class AbstractController
{
    /**
     * @var \HiMVC\Core\MVC\View\ViewDispatcher
     */
    private $viewDispatcher;

    /**
     * @param \HiMVC\Core\MVC\View\ViewDispatcher $viewDispatcher
     */
    public function __construct( ViewDispatcher $viewDispatcher )
    {
        $this->viewDispatcher = $viewDispatcher;
    }

    /**
     * Execution point for controller actions
     *
     * Note: As this calls $action directly, make sure private functions are marked as private and not
     * protected so they can not be exposed directly by mistakes in routes.
     *
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param string $action
     * @param array $params
     * @param array $viewParams Params to send to template (used for sending params from parent template to child)
     *
     * @return \HiMVC\API\MVC\Values\Response
     */
    public function run( APIRequest $request, $action, array $params = array(), array $viewParams = array() )
    {
        $result = call_user_func_array( array( $this, $action ), $params );

        if ( !$result instanceof APIResult )
        {
            return $result;
        }

        return $this->viewDispatcher->view( $request, $result, $viewParams );
    }
}


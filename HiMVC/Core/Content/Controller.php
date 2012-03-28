<?php
/**
 * Content Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Content;
use HiMVC\API\MVC\CRUDControllable,
    HiMVC\Core\MVC\Request,
    HiMVC\Core\MVC\ViewDispatcher,
    eZ\Publish\API\Repository\Repository,
    HiMVC\API\MVC\Values\Result;

/**
 * Example controller, does no chnages to data atm
 */
class Controller implements CRUDControllable
{
    /**
     * @var \HiMVC\Core\MVC\Request
     */
    protected $request;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @param \HiMVC\Core\MVC\Request $request
     * @param \eZ\Publish\API\Repository\Repository $reposiotry
     */
    public function __construct( Request $request, Repository $reposiotry )
    {
        $this->request = $request;
        $this->repository = $reposiotry;
    }

    /**
     * Add new item in collection ( ie POST /orders/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doCreate()
    {
        return __METHOD__ . "()";
    }

    /**
     * Get item in collection ( ie GET /orders/{id} )
     *
     * @param mixed $id
     * @param string $view
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doRead( $id, $view = 'full' )
    {
        $model = $this->repository->getContentService()->loadContent( $id );

        return new Result( array(
            'model' => $model,
            'module' => 'content',
            'action' => 'read',
            'view' => $view,
        ) );
    }

    /**
     * Update item in collection ( ie PUT /orders/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doUpdate( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * Delete item in collection ( ie DELETE /orders/{id} )
     * Or 'Cancel order'
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doDelete( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * List items in collection ( ie GET /orders/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doIndex()
    {
        return __METHOD__ . "()";
    }
}


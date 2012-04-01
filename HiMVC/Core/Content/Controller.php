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
    HiMVC\API\MVC\Values\Request,
    HiMVC\Core\MVC\View\ViewDispatcher,
    eZ\Publish\API\Repository\Repository,
    HiMVC\API\MVC\Values\Result;

/**
 * Example controller, does no chnages to data atm
 */
class Controller implements CRUDControllable
{
    /**
     * @var \HiMVC\API\MVC\Values\Request
     */
    protected $request;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @param \HiMVC\API\MVC\Values\Request $request
     * @param \eZ\Publish\API\Repository\Repository $reposiotry
     */
    public function __construct( Request $request, Repository $reposiotry )
    {
        $this->request = $request;
        $this->repository = $reposiotry;
    }

    /**
     * Add new item in collection ( ie POST /content/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doCreate()
    {
        return __METHOD__ . "()";
    }

    /**
     * Get item in collection ( ie GET /content/{id} )
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
            'uri' => "content/{$id}",
        ) );
    }

    /**
     * Update item in collection ( ie PUT /content/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doUpdate( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * Delete item in collection ( ie DELETE /content/{id} )
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
     * List items in collection ( ie GET /content/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doIndex()
    {
        return __METHOD__ . "()";
    }
}


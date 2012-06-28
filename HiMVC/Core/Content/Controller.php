<?php
/**
 * Content Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Content;
use HiMVC\Core\MVC\Values\Request,
    HiMVC\Core\MVC\View\ViewDispatcher,
    eZ\Publish\API\Repository\Repository,
    HiMVC\Core\MVC\Values\ResultItem,
    HiMVC\Core\MVC\Values\ResultList,
    eZ\Publish\API\Repository\Values\Content\Query,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;

use HiMVC\Core\MVC\AbstractController;

/**
 * Example content controller, does no changes to data atm
 */
class Controller extends AbstractController
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @param \HiMVC\Core\MVC\View\ViewDispatcher $viewDispatcher
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct( ViewDispatcher $viewDispatcher, Repository $repository )
    {
        $this->repository = $repository;
        parent::__construct( $viewDispatcher );
    }

    /**
     * Add new item in collection ( ie POST /content/ )
     *
     * @return \HiMVC\Core\MVC\Values\Result
     */
    public function create()
    {
        return __METHOD__ . "()";
    }

    /**
     * Get item in collection ( ie GET /content/{id} )
     *
     * @param mixed $id
     * @param string $view
     * @return \HiMVC\Core\MVC\Values\Result
     */
    public function read( $id, $view = 'full' )
    {
        $model = $this->repository->getContentService()->loadContent( $id );
        return $this->getResult( $model, array( 'id' => $model->id, 'view' => $view  ) );
    }

    /**
     * Update item in collection ( ie PUT /content/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\Core\MVC\Values\Result
     */
    public function update( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * Delete item in collection ( ie DELETE /content/{id} )
     * Or 'Cancel order'
     *
     * @param mixed $id
     * @return \HiMVC\Core\MVC\Values\Result
     */
    public function delete( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * List items in collection ( ie GET /content/ )
     *
     * @todo This should probably not list items by location, but just list of content sorted by creation
     * @return \HiMVC\Core\MVC\Values\Result
     */
    public function index()
    {
        $query = new Query();
        $query->criterion = new ParentLocationId( 1 );
        $searchResult = $this->repository->getContentService()->findContent( $query, array() );

        $items = array();
        foreach ( $searchResult->items as $model )
        {
            $items[] = $this->getResult( $model, array( 'id' => $model->id, 'view' => 'line' ) );
        }

        return new ResultList( array(
            'items' => $items,
            'count' => $searchResult->count,
            'module' => 'content',
            'action' => 'index',
            'controller' => __CLASS__,
        ) );
    }

    /**
     * @param object $model
     * @param array $params
     * @param string $action
     * @return \HiMVC\Core\MVC\Values\ResultItem
     */
    private function getResult( $model, array $params, $action = 'read' )
    {
        return new ResultItem( array(
            'model' => $model,
            'module' => 'content',
            'action' => $action,
            'controller' => __CLASS__,
            'params' => $params
        ) );
    }
}


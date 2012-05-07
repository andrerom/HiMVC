<?php
/**
 * Content Location Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Content;
use HiMVC\API\MVC\Values\Request,
    HiMVC\Core\MVC\View\ViewDispatcher,
    eZ\Publish\API\Repository\Repository,
    HiMVC\API\MVC\Values\ResultItem,
    HiMVC\API\MVC\Values\ResultList,
    eZ\Publish\API\Repository\Values\Content\Query,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;

use HiMVC\Core\MVC\AbstractController;

/**
 * Example cotnent location controller, does no changes to data atm
 */
class LocationController extends AbstractController
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct( Repository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * Add new item in collection ( ie POST /content/locations/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function create()
    {
        return __METHOD__ . "()";
    }

    /**
     * Get item in collection ( ie GET /content/location/{id} )
     *
     * @param mixed $id
     * @param string $view
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function read( $id, $view = 'full' )
    {
        $model = $this->repository->getLocationService()->loadLocation( $id );
        return $this->getResult( $model, array( 'id' => $model->id, 'view' => $view ) );
    }

    /**
     * Update item in collection ( ie PUT /content/location/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function update( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * Delete item in collection ( ie DELETE /content/location/{id} )
     * Or 'Cancel order'
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function delete( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * List items in collection ( ie GET /content/location/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function index()
    {
        return $this->children( 1 );
    }

    /**
     * List items in collection ( ie GET /content/locations/{$parentId} )
     *
     * @param mixed $parentId
     * @param int $offset
     * @param int $limit
     * @return \HiMVC\API\MVC\Values\Result
     *
     * @todo Add global (injected) setting to specify max limits
     */
    public function children( $parentId, $view = 'line' )
    {
        $locationService = $this->repository->getLocationService();
        $location = $locationService->loadLocation( $parentId );
        $children = $locationService->loadLocationChildren( $location );

        $items = array();
        foreach ( $children as $model )
        {
            $items[] = $this->getResult( $model, array( 'id' => $model->id, 'view' => $view ) );
        }

        return new ResultList( array(
            'items' => $items,
            'count' => $location->childCount,
            'module' => 'content/location',
            'action' => 'list',
            'controller' => __CLASS__,
            'params' => array( 'parentId' => $parentId ),
        ) );
    }

    /**
     * @param object $model
     * @param array $params
     * @param string $action
     * @return \HiMVC\API\MVC\Values\ResultItem
     */
    private function getResult( $model, array $params, $action = 'read' )
    {
        return new ResultItem( array(
            'model' => $model,
            'module' => 'content/location',
            'action' => $action,
            'controller' => __CLASS__,
            'params' => $params
        ) );
    }
}


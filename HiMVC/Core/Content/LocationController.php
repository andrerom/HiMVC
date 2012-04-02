<?php
/**
 * Content Location Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
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

/**
 * Example cotnent location controller, does no changes to data atm
 */
class LocationController
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
     * Add new item in collection ( ie POST /content/locations/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doCreate()
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
    public function doRead( $id, $view = 'full' )
    {
        $model = $this->repository->getLocationService()->loadLocation( $id );

        return new ResultItem( array(
            'model' => $model,
            'module' => 'content/location',
            'action' => 'read',
            'view' => $view,
            'uri' => "content/location/{$id}",
        ) );
    }

    /**
     * Update item in collection ( ie PUT /content/location/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doUpdate( $id )
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
    public function doDelete( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * List items in collection ( ie GET /content/location/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doIndex()
    {
        return $this->doList( 1 );
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
    public function doList( $parentId, $offset = 0, $limit = -1 )
    {
        $locationService = $this->repository->getLocationService();
        $location = $locationService->loadLocation( $parentId );
        $children = $locationService->loadLocationChildren( $location, $offset, $limit );

        $resultHash = array(
            'items' => array(),
            'count' => $location->childCount,
            'module' => 'content/location',
            'action' => 'list',
            'uri' => "content/locations/{$parentId}",
        );

        foreach ( $children as $model )
        {
            $resultHash['items'][] = new ResultItem( array(
                'model' => $model,
                'module' => 'content/location',
                'action' => 'read',
                'view' => 'line',
                'uri' => "content/location/{$model->id}",
            ) );
        }

        return new ResultList( $resultHash );
    }
}


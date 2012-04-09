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
use HiMVC\API\MVC\Values\Request,
    HiMVC\Core\MVC\View\ViewDispatcher,
    eZ\Publish\API\Repository\Repository,
    HiMVC\API\MVC\Values\ResultItem,
    HiMVC\API\MVC\Values\ResultList,
    eZ\Publish\API\Repository\Values\Content\Query,
    eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;

/**
 * Example content controller, does no changes to data atm
 */
class Controller
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @param \eZ\Publish\API\Repository\Repository $reposiotry
     */
    public function __construct( Repository $reposiotry )
    {
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

        return new ResultItem( array(
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
     * @todo This should probably not list items by location, but just list of content sorted by creation
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doIndex()
    {
        $query = new Query();
        $query->criterion = new ParentLocationId( 1 );
        $searchResult = $this->repository->getContentService()->findContent( $query, array() );

        $resultHash = array(
            'items' => array(),
            'count' => $searchResult->count,
            'module' => 'content',
            'action' => 'index',
            'uri' => "content/",
        );

        foreach ( $searchResult->items as $model )
        {
            $resultHash['items'][] = new ResultItem( array(
                'model' => $model,
                'module' => 'content',
                'action' => 'read',
                'view' => 'line',
                'uri' => "content/{$model->contentId}",
            ) );
        }

        return new ResultList( $resultHash );
    }
}


<?php
/**
 * Content Controller
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Controller;
use HiMVC\API\MVC\Restable,
    HiMVC\Core\MVC\Request,
    eZ\Publish\API\Repository\Repository;

/**
 * Example controller, does no chnages to data atm
 */
class ContentItem implements Restable
{
    /**
     * @param \HiMVC\Core\MVC\Request $request
     * @param \eZ\Publish\API\Repository\Repository $reposiotry
     */
    public function __construct( Request $request, Repository $reposiotry  )
    {

    }

    /**
     * Add new item in collection ( ie POST /orders/ )
     *
     * @return \HiMVC\Core\MVC\Response
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
     * @return \HiMVC\Core\MVC\Response
     */
    public function doRetrieve( $id, $view = 'full' )
    {
        return __METHOD__ . "( $id, '$view' )";
    }

    /**
     * Update item in collection ( ie PUT /orders/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\Core\MVC\Response
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
     * @return \HiMVC\Core\MVC\Response
     */
    public function doDelete( $id )
    {
        return __METHOD__ . "( $id )";
    }

    /**
     * List items in collection ( ie GET /orders/ )
     *
     * @return \HiMVC\Core\MVC\Response
     */
    public function doIndex()
    {
        return __METHOD__ . "()";
    }
}


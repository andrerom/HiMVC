<?php
/**
 * CRUD Controller Interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC;

/**
 * @see http://www.parleys.com/#id=1397&sl=31&st=5
 *
 * Should only be Get, Put, Post & Delete according to slides
 * meaning there should be an index controller for /orders/
 * and an item controller for /orders/{id}
 */

interface CRUDControllable
{
    /**
     * Add new item in collection ( ie POST /orders/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doCreate();

    /**
     * Get item in collection ( ie GET /orders/{id}/{view} )
     *
     * @todo Use 'read' instead of 'retrieve' for simplicity and to avoid people using the http typo?
     *
     * @param mixed $id
     * @param string $view
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doRetrieve( $id, $view = 'full' );

    /**
     * Update item in collection ( ie PUT /orders/{id} )
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doUpdate( $id );

    /**
     * Delete item in collection ( ie DELETE /orders/{id} )
     * Or 'Cancel order'
     *
     * @param mixed $id
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doDelete( $id );

    /**
     * List items in collection ( ie GET /orders/ )
     *
     * @return \HiMVC\API\MVC\Values\Result
     */
    public function doIndex();
}


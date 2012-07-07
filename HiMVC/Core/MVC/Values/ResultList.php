<?php
/**
 * MVC\ResultList class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Values;

use HiMVC\Core\MVC\Values\Result;

/**
 * Result List object
 *
 * @see \HiMVC\Core\MVC\Values\Result
 *
 * @property-read ResultItem[] $items The model objects for the result
 * @property-read int $count Count of total count in the collection of items
 */
class ResultList extends Result
{
    /**
     * The model objects for the result
     *
     * @var ResultItem[]
     */
    protected $items;

    /**
     * Count of total count in the collection of items
     *
     * Given limit and offset use, the toatl amount of items using the attached url might be much larger
     * then number of $modules on current instance, this property is need to be able to have paging.
     * Limit and offset values will be part of $params.
     *
     * @var int
     */
    protected $count;

    /**
     * Constructor for ResultList
     *
     * Check presence of model, module, action and uri as they are minimum properties that needs to be set.
     *
     * @param array $properties
     */
    public function __construct( array $properties = array() )
    {
        if ( !isset( $properties['items'] ) || !isset( $properties['count'] ) )
            throw new \Exception( 'Properties that must be present: items, count' );

        parent::__construct( $properties );
    }
}

<?php
/**
 * MVC\ResultItem class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use HiMVC\API\MVC\Values\Result;

/**
 * Result Item object
 *
 * @see \HiMVC\API\MVC\Values\Result
 *
 * @property-read object $model
 */
class ResultItem extends Result
{
    /**
     * The model object for the result
     *
     * @var object
     */
    protected $model;

    /**
     * Constructor for ResultItem
     *
     * Check presence of model, module, action and uri as they are minimum properties that needs to be set.
     *
     * @param array $properties
     */
    public function __construct( array $properties = array() )
    {
        if ( !isset( $properties['model'] ) )
            throw new \Exception( 'Properties that must be present: model' );

        parent::__construct( $properties );
    }
}

<?php
/**
 * Service Container ArrayIterator class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Common\DependencyInjection;

use HiMVC\Core\Common\DependencyInjection\ArrayObject;
use ArrayIterator as splArrayIterator;

/**
 * Array Iterator class, used by DependencyInjection\ArrayObject
 */
class ArrayIterator extends splArrayIterator
{
    /**
     * @var \HiMVC\Core\Common\DependencyInjection\ArrayObject
     */
    private $arrayObject;

    /**
     * @param \HiMVC\Core\Common\DependencyInjection\ArrayObject $arrayObject
     */
    public function __construct( ArrayObject $arrayObject )
    {
        $this->arrayObject = $arrayObject;
        parent::__construct( $arrayObject->getArrayCopy() );
    }

    /**
     * @param mixed $index
     * @return mixed|void
     */
    public function offsetGet( $index )
    {
        $value = parent::offsetGet( $index );
        if ( is_string( $value ) === true )
        {
            self::offsetSet( $index, ( $value = $this->arrayObject[$index] ) );
        }

        return $value;
    }

    /**
     * @return mixed|void
     */
    public function current()
    {
        $value = parent::current();
        if ( is_string( $value ) === true )
        {
            $index = self::key();
            self::offsetSet( $index, ( $value = $this->arrayObject[$index] ) );
        }

        return $value;
    }
}

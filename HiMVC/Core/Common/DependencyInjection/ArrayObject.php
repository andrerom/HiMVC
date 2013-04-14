<?php
/**
 * Service Container ArrayObject class
 *
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Common\DependencyInjection;

use HiMVC\Core\Common\DependencyInjectionContainer;
use HiMVC\Core\Common\DependencyInjection\ArrayIterator;
use ArrayObject as splArrayObject;

/**
 * Array Object class, used by DependencyInjectionContainer for lazy loaded collections.
 */
class ArrayObject extends splArrayObject
{
    /**
     * @var \HiMVC\Core\Common\DependencyInjectionContainer
     */
    private $container;

    /**
     * @param \HiMVC\Core\Common\DependencyInjectionContainer $container
     * @param array $serviceIds
     */
    public function __construct( DependencyInjectionContainer $container, array $serviceIds )
    {
        $this->container = $container;
        parent::__construct( $serviceIds, 0, 'HiMVC\Core\Common\DependencyInjection\ArrayIterator' );
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
            self::offsetSet( $index, ( $value = $this->container->get( $value ) ) );
        }

        return $value;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator( $this );
    }
}

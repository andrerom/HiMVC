<?php
/**
 * File containing the Event Class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Base;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Service: event
 * Provides a extension point for different parts of a system to be able to listen to events
 * form other parts without having to modify the code.
 *
 * Listeners can be defined globally with settings or on demand using attach().
 * The actual phpdoc (params and possible return values) for the different callbacks can vary
 * greatly and needs to be documented somewhere else.
 *
 * Use cases:
 *   - Filters, for instance request / response filters that want to modify some value.
 *     These need to always return the value in dependant of being changed or not.
 *   - Notifications, to be able to notify listeners about state change on a certain kind of object
 *
 * @todo Add support for async notification listeners
 */
class Event
{
    /**
     * Contains all registered listeners (callbacks)
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Count of listeners, used to generate listener id
     * Global to make sure it's unique.
     *
     * @var int
     */
    protected static $listenerIdNumber = 0;

    /**
     * Constructor
     *
     * @param array $eventListeners Format is array( 'eventName' => array( <listeners> ) )
     */
    public function __construct( array $eventListeners = array() )
    {
        foreach ( $eventListeners as $event => $listeners )
        {
            foreach ( $listeners as $listener )
            {
                $this->attach( $event, $listener );
            }
        }
    }


    /**
     * Attach an event listener on demand.
     *
     * @param string $name In the form "content/delete/1" or "content/delete"
     * @param array|string $listener A valid PHP callback {@see http://php.net/manual/en/language.pseudo-types.php#language.types.callback}
     * @return int Listener id, can be used to detach a listener later {@see detach()}
     */
    public function attach( $name, $listener )
    {
        if ( !is_callable( $listener ) )
            throw new InvalidArgumentException(
                '$listener',
                "Listener must be callable, got \$listener: {$listener}, for \$name: '$name'"
            );

        $id = self::$listenerIdNumber++;
        $this->listeners[$name][$id] = $listener;
        return $id;
    }

    /**
     * Detach an event listener by id given when it was added.
     *
     * @param string $name
     * @param int $id The unique id given by {@see attach()}
     * @return bool True if the listener has been correctly detached
     */
    public function detach( $name, $id )
    {
        if ( !isset( $this->listeners[$name][$id] ) )
        {
            return false;
        }

        unset( $this->listeners[$name][$id] );
        return true;
    }

    /**
     * Detach all event listeners
     */
    public function detachAll()
    {
        $this->listeners = array();
    }

    /**
     * Notify all listeners on an event
     *
     * @param string $name In the form "content/delete/1", "content/delete", "content/read"
     * @param array $params The arguments for the specific event as simple array structure (not hash)
     * @return bool True if some listener where called
     */
    public function notify( $name, array $params = array() )
    {
        if ( empty( $this->listeners[$name] ) )
        {
            return false;
        }

        foreach ( $this->listeners[$name] as $listener )
        {
            call_user_func_array( $listener, $params );
        }
        return true;
    }

    /**
     * Filter a value on all listeners, everyone must return the first parameter modified or not
     *
     * @param string $name In the form "content/delete/1", "content/delete", "content/read"
     * @param array $params The arguments for the specific event as simple array structure (not hash)
     * @return mixed $params[0] param after being filtered by filters, or unmodified if no filters
     */
    public function filter( $name, array $params = array() )
    {
        if ( empty( $this->listeners[$name] ) )
        {
            return $params[0];
        }

        foreach ( $this->listeners[$name] as $listener )
        {
            $params[0] = call_user_func_array( $listener, $params );
        }
        return $params[0];
    }
}

<?php
/**
 * File containing session interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 * @package lib
 */

use HiMVC\Core\Common\SessionArray;

/**
 * eZ Publish Session interface class legacy compat
 *
 *
 * @package lib
 * @subpackage ezsession
 */
class eZSession
{
    /**
     * @var \HiMVC\Core\Common\SessionArray
     */
    static protected $session;

    /**
     * Constructor (not used, this is an all static class)
     */
    protected function __construct()
    {
    }

    /**
     * @access protected Only for bootstrap use to inject SessionArray into this class
     * @param HiMVC\Core\Common\SessionArray $session
     */
    static public function setSessionArray( SessionArray $session )
    {
        self::$session = $session;
    }

    /**
     * Get session value (wrapper)
     *
     * @since 4.4
     * @param string|null $key Return the whole session array if null otherwise the value of $key
     * @param null|mixed $defaultValue Return this if not null and session has not started
     * @return mixed|null $defaultValue if key does not exist, otherwise session value depending on $key
     */
    static public function get( $key, $defaultValue = null )
    {
        if ( self::$session->offsetExists( $key ) === null )
        {
            if ( $defaultValue !== null )
                return $defaultValue;
        }

        if ( $key === null )
            return self::$session;
        else if ( isset( self::$session[$key] ) )
            return self::$session[$key];
        return $defaultValue;
    }

    /**
     * Set session value (wrapper)
     *
     * @since 4.4
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    static public function set( $key, $value )
    {
        self::$session[$key] = $value;
        return true;
    }

    /**
     * Isset session value (wrapper)
     *
     * @since 4.4
     * @param string $key
     * @return bool|null Null if session has not started and $forceStart is false
     */
    static public function isSetKey( $key )
    {
        return isset( self::$session[ $key ] );
    }

    /**
     * unset session value (wrapper)
     *
     * @since 4.4
     * @param string $key
     */
    static public function unSetKey( $key )
    {
        unset( self::$session[ $key ] );
    }

    /**
     * Deletes all expired session data in the database, this function is not supported
     * by session handlers that don't have a session backend on their own.
     *
     * @since 4.1
     * @return bool
     */
    static public function garbageCollector()
    {
        return self::$session->gc( (int)$_SERVER['REQUEST_TIME']  );
    }

    /**
     * Truncates all session data in the database, this function is not supported
     * by session handlers that don't have a session backend on their own.
     *
     * @since 4.1
     * @return bool
     */
    static public function cleanup()
    {
        return self::$session->gc( $_SERVER['REQUEST_TIME'] + ( 1024 * 1024 )  );
    }

    /**
     * Counts the number of active session and returns it, this function is not supported
     * by session handlers that don't have a session backend on their own.
     *
     * @since 4.1
     * @return string Number of sessions.
     */
    static public function countActive()
    {
        return 0;
    }

    /**
     * Set default cookie parameters based on site.ini settings (fallback to php.ini settings)
     * Note: this will only have affect when session is created / re-created
     *
     * @since 4.4
     * @param null|int $lifetime Cookie timeout of session cookie, will read from ini if not set
     */
    static public function setCookieParams( $lifetime = null )
    {
        if ( !$lifetime )
            return;

        $param = session_get_cookie_params();
        session_set_cookie_params( $lifetime, $param['path'], $param['domain'], $param['secure'], $param['httponly'] );

    }

    /**
     * Starts the session and sets the timeout of the session cookie.
     * Multiple calls will be ignored unless you call {@link eZSession::stop()} first.
     *
     * @since 4.1
     * @param int|null $cookieTimeout Use this to set custom cookie timeout.
     * @return bool Depending on if session was started.
     */
    static public function start( $cookieTimeout = null )
    {
        return self::$session->start();
    }

    /**
     * Inits eZSession and starts it if user has cookie and $startIfUserHasCookie is true.
     *
     * @since 4.4
     * @param bool $startIfUserHasCookie
     * @return bool|null
     */
    static public function lazyStart( $startIfUserHasCookie = true )
    {
        if ( empty( $GLOBALS['eZSiteBasics']['session-required'] ) || self::$session->hasStarted() )
        {
            return false;
        }

        if ( $startIfUserHasCookie && self::$session->hasSessionCookie() )
        {
            self::setCookieParams();
            return self::start();
        }
        return null;
    }

    /**
     * Gets/generates the user hash for use in validating the session based on [Session]
     * SessionValidation* site.ini settings. The default hash is result of md5('empty').
     *
     * @since 4.1
     * @deprecated as of 4.4, only returns default md5('empty') hash now for BC.
     * @return string MD5 hash based on parts of the user ip and agent string.
     */
    static public function getUserSessionHash()
    {
        return 'a2e4822a98337283e39f7b60acf85ec9';
    }

    /**
     * Writes session data and stops the session, if not already stopped.
     *
     * @since 4.1
     * @return bool Depending on if session was stopped.
     */
    static public function stop()
    {
        return self::$session->stop();
    }

    /**
     * Will make sure the user gets a new session ID while keepin the session data.
     * This is useful to call on login, to avoid sessions theft from users.
     * NOTE: make sure you set new user id first using {@link eZSession::setUserID()}
     *
     * @since 4.1
     * @return bool Depending on if session was regenerated.
     */
    static public function regenerate()
    {
        return self::$session->regenerate();
    }

    /**
     * Removes the current session and resets session variables.
     * Note: implicit stops session as well!
     *
     * @since 4.1
     * @return bool Depending on if session was removed.
     */
    static public function remove()
    {
        return self::$session->destroy();
    }

    /**
     * Sets the current userID used by ezpSessionHandlerDB::write() on shutdown.
     *
     * @since 4.1
     * @param int $userID to use in {@link ezpSessionHandlerDB::write()}
     */
    static public function setUserID( $userID )
    {
    }

    /**
     * Returns if user had session cookie at start of request or not.
     *
     * @since 4.1
     * @return bool|null Null if session is not started yet.
     */
    static public function userHasSessionCookie()
    {
        return self::$session->hasSessionCookie();
    }

    /**
     * Returns if user session validated against stored data in db
     * or if it was invalidated during the current request.
     *
     * @since 4.1
     * @deprecated as of 4.4, only returns true for bc
     * @return bool|null Null if user is not validated yet (for instance a new session).
     */
    static public function userSessionIsValid()
    {
        return true;
    }

    /**
     * Return value to indicate if session has started or not
     *
     * @since 4.4
     * @return bool
     */
    static public function hasStarted()
    {
        return self::$session->hasStarted();
    }

    /**
     * Get curren session handler
     *
     * @since 4.4
     * @return ezpSessionHandler
     */
    static public function getHandlerInstance()
    {

        return new ezpLegacySessionHandler;
    }

    /**
     * Adds a callback function, to be triggered by {@link eZSession::triggerCallback()}
     * when a certain session event occurs.
     * Use: eZSession::addCallback('gc_pre', myCustomGarabageFunction );
     *
     * @since 4.1
     * @deprecated since 4.5, use {@link ezpEvent::getInstance()->attach()} with new events
     * @param string $type cleanup, gc, destroy, insert and update, pre and post types.
     * @param handler $callback a function to call.
     */
    static public function addCallback( $type, $callback )
    {
        // @todo When SessionArray has re introduced events somehow (needed by parts of Legacy kernel, like shop)
    }

    /**
     * Triggers callback functions by type, registrated by {@link eZSession::addCallback()}
     * Use: eZSession::triggerCallback('gc_pre', array( $db, $time ) );
     *
     * @since 4.1
     * @deprecated since 4.5, use {@link ezpEvent::getInstance()->notify()} with new events
     * @param string $type cleanup, gc, destroy, insert and update, pre and post types.
     * @param array $params list of parameters to pass to the callback function.
     * @return bool
     */
    static public function triggerCallback( $type, $params )
    {
        // @see addCallback()
    }
}

class ezpLegacySessionHandler
{
    public function __call( $fn, array $arguments = array() )
    {
        return false;
    }
}

?>

<?php
/**
 * Fake $_SESSION array object which encapusaltes Session system
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */
namespace HiMVC\Core\Common;

use HiMVC\API\MVC\Values\Request,
    HiMVC\Core\Common\SessionHandler\SessionHandlerInterface;


/**
 * An array object of session system for use by request object
 *
 * Note: underlying session system is global, thus is handled using singelton pattern internally.
 */
class SessionArray implements \ArrayAccess
{
    /**
     * Flag session has started, see {@link Session_Array::start()}.
     *
     * @var boolean
     */
    protected $hasStarted = false;

    /**
     * Flag request contains session cookie, set in {@link Session_Array::__construct()}.
     *
     * @var boolean
     */
    protected $hasSessionCookie;

    /**
     * Current session handler or null.
     *
     * @var \HiMVC\Core\Common\SessionHandler\SessionHandlerInterface
     */
    protected $handler;


    /**
     * Current session handler or null.
     *
     * @var \HiMVC\Core\Common\Event
     */
    protected $event;

    /**
     * Event listener id, for use when detaching shutdown event
     *
     * @var int
     */
    protected $eventId;

    /**
     * Constructor, setup session system (but only start if session cookie is present, otherwise lazy start)
     *
     * @param \HiMVC\API\MVC\Values\Request $req
     * @param \HiMVC\Core\Common\SessionHandler\SessionHandlerInterface $handler
     * @param array $settings
     */
    function __construct( Request $req,
                          SessionHandlerInterface $handler,
                          array $settings )
    {
        $this->handler = $handler;

        if ( isset( $settings['name'] ) )
        {
            $sessionName = $settings['name'];
            session_name( $sessionName );
        }
        else
        {
            $sessionName = session_name();
        }

        // See if user has session, used to avoid reading from db if no session.
        // Allow session bye post params for use by flash
        if ( isset( $req->body[ $sessionName ] ) )
        {
            session_id( $req->body[ $sessionName ] );
            $this->hasSessionCookie = true;
        }
        else
        {
            $this->hasSessionCookie = isset( $req->cookies[ $sessionName ] );
        }

        if ( isset( $settings['cookie_params'] ) )
        {
            $params = session_get_cookie_params() + $settings['cookie_params'];
            session_set_cookie_params( $params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly'] );
        }

        if ( isset( $settings['gc_maxlifetime'] ) )
        {
            ini_set("session.gc_maxlifetime", $settings['gc_maxlifetime'] );
        }

        $this->handler->register( $this->hasSessionCookie );

        if ( $this->hasSessionCookie )
        {
            $this->start();
        }
    }

    /**
     * Starts the session and sets the timeout of the session cookie.
     * Multiple calls will be ignored unless you call {@link Session_Array::stop()} first.
     *
     * @return bool Depending on if session was started.
     */
    public function start()
    {
        if ( $this->hasStarted )
             return false;

        session_start();
        return $this->hasStarted = true;
    }

    /**
     * Writes session data and stops the session, if not already stopped.
     *
     * @return bool Depending on if session was stopped.
     */
    public function stop()
    {
        if ( !$this->hasStarted )
             return false;

        session_write_close();
        $this->hasStarted = false;
        return true;
    }

    /**
     * Will make sure the user gets a new session ID while keepin the session data.
     * This is useful to call on logins, to avoid sessions theft from users.
     *
     * @return bool The return value from session handler (usually TRUE on success, FALSE on failure).
     */
    public function regenerate()
    {
        if ( !$this->hasStarted )
             return false;

        if ( !function_exists( 'session_regenerate_id' ) )
            return false;

        if ( headers_sent() )
        {
            if ( PHP_SAPI !== 'cli' )
                trigger_error( __METHOD__ . ": Could not regenerate session id, HTTP headers already sent.!", E_USER_WARNING );
            return false;
        }

        return $this->handler->regenerate();
    }

    /**
     * Removes the current session and resets session variables.
     * Note: implicit stops session as well!
     *
     * @return bool Depending on if session was removed.
     */
    public function destroy()
    {
        if ( !$this->hasStarted )
             return false;

        $_SESSION = array();
        session_destroy();
        $this->hasStarted = false;
        return true;
    }

    /**
     * Get session value (wrapper)
     *
     * @param string $offset Return the value of $offset
     * @return mixed Return the value of $offset, null if session has not started (and no cookie)
     */
    public function offsetGet( $offset )
    {
        if ( $this->hasStarted === false )
        {
            if ( $this->hasSessionCookie === false )
                return null;
            $this->start();
        }

        return $_SESSION[ $offset ];
    }

    /**
     * Set session value (wrapper)
     *
     * @param string $offset
     * @param mixed $value
     * @return bool
     */
    public function offsetSet( $offset, $value )
    {
        if ( $this->hasStarted === false )
            $this->start();

        $_SESSION[ $offset ] = $value;
        return true;
    }

    /**
     * Isset session value (wrapper)
     *
     * @param string $offset
     * @return boolean|null Null if session has not started (and no cookie)
     */
    public function offsetExists( $offset )
    {
        if ( $this->hasStarted === false )
        {
            if ( $this->hasSessionCookie === false )
                return null;
            $this->start();
        }


        return isset( $_SESSION[ $offset ] );
    }

    /**
     * unset session value (wrapper)
     *
     * @param string $offset
     * @return boolean|null True if value was removed, false if it did not exist and
     *                      null if session has not started (and no cookie)
     */
    public function offsetUnset( $offset )
    {
        if ( $this->hasStarted === false )
        {
            if ( $this->hasSessionCookie === false )
                return null;
            $this->start();
        }

        if ( !isset( $_SESSION[ $offset ] ) )
            return false;

        unset( $_SESSION[ $offset ] );
        return true;
    }
}

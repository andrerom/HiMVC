<?php
/**
 * File containing a Session handler for PHP
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Base\SessionHandler;

use HiMVC\Core\Base\SessionHandler\SessionHandlerInterface;

/**
 * Session handler PHP
 */
class SessionHandlerPHP implements SessionHandlerInterface
{
    /**
     * Register session handler
     *
     * @param bool $sessionExist  A hint that specifies is session existed (existence of session cookie) at init.
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function register( $sessionExist )
    {
        return true;
    }

    /**
     * Regenerate session id
     *
     * Notice: Moves session data over to new session, use destroy() if that is not prefered (aka on logout).
     *
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function regenerate()
    {
        session_regenerate_id();
        return true;
    }

    /**
     * Session close handler
     *
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function close()
    {
        return true;
    }

    /**
     * Session destroy handler
     *
     * @param string $sessionId
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function destroy( $sessionId )
    {
        return true;
    }

    /**
      * Session gc (garbageCollector) handler
      *
      * @param int $maxLifeTime In seconds
      * @return bool The return value (usually TRUE on success, FALSE on failure).
      */
     public function gc( $maxLifeTime )
     {
         return true;
     }

    /**
     * Session open handler
     *
     * @param string $savePath
     * @param string $sessionId
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function open( $savePath, $sessionId )
    {
        return true;
    }

    /**
     * Session read handler
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If no session where found with that session id
     * @param string $sessionId
     * @return string Returns the read data (Binary), or an empty string.
     */
    public function read( $sessionId )
    {
        return '';
    }

    /**
     * Session write handler
     *
     * @param string $sessionId
     * @param string $sessionData Binary session data
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function write( $sessionId, $sessionData )
    {
        return true;
    }
}

<?php
/**
 * File containing a Session handler interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Base\SessionHandler;

/**
 * Session handler interface
 *
 * Follows the SessionHandlerInterface added in PHP 5.4.0 closly, but adds regenerate(), cleanup(), deleteByUserIDs()
 * and count() from eZ Publish 4.x
 * (re consider if all of these are needed, and if sessions should have such thight cupling with users or not)
 */
interface SessionHandlerInterface
{
    /**
     * Register session handler
     *
     * @param bool $sessionExist  A hint that specifies is session existed (existence of session cookie) at init.
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function register( $sessionExist );

    /**
     * Regenerate session id
     *
     * Notice: Moves session data over to new session, use destroy() if that is not prefered (aka on logout).
     *
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function regenerate();

    /**
     * Session close handler
     *
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function close();

    /**
     * Session destroy handler
     *
     * @param string $sessionId
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function destroy( $sessionId );

    /**
      * Session gc (garbageCollector) handler
      *
      * @param int $maxLifeTime In seconds
      * @return bool The return value (usually TRUE on success, FALSE on failure).
      */
     public function gc( $maxLifeTime );

    /**
     * Session open handler
     *
     * @param string $savePath
     * @param string $sessionId
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function open( $savePath, $sessionId );

    /**
     * Session read handler
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If no session where found with that session id
     * @param string $sessionId
     * @return string Returns the read data (Binary), or an empty string.
     */
    public function read( $sessionId );

    /**
     * Session write handler
     *
     * @param string $sessionId
     * @param string $sessionData Binary session data
     * @return bool The return value (usually TRUE on success, FALSE on failure).
     */
    public function write( $sessionId, $sessionData );
}

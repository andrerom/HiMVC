<?php
/**
 * File containing a Session handler interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Common\SessionHandler;

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
     * @return bool The return value (usually true on success, false on failure).
     */
    public function register( $sessionExist );

    /**
     * Regenerate session id
     *
     * Notice: Moves session data over to new session, use destroy() if that is not prefered (aka on logout).
     *
     * @return bool The return value  (usually true on success, false on failure).
     */
    public function regenerate();

    /**
     * Session close handler
     *
     * @see http://php.net/sessionhandlerinterface.close
     *
     * @return bool The return value (usually true on success, false on failure).
     */
    public function close();

    /**
     * Session destroy handler
     *
     * @see http://php.net/sessionhandlerinterface.destroy
     *
     * @param string $sessionId
     * @return bool The return value (usually true on success, false on failure).
     */
    public function destroy( $sessionId );

    /**
     * Session gc (garbageCollector) handler
     *
     * @see http://php.net/sessionhandlerinterface.gc
     *
     * @param int $maxLifeTime In seconds
     * @return bool The return value (usually true on success, false on failure).
     */
    public function gc( $maxLifeTime );

    /**
     * Session open handler
     *
     * @see http://php.net/sessionhandlerinterface.open
     *
     * @param string $savePath
     * @param string $sessionId
     * @return bool The return value (usually true on success, false on failure).
     */
    public function open( $savePath, $sessionId );

    /**
     * Session read handler
     *
     * @see http://php.net/sessionhandlerinterface.read
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If no session where found with that session id
     * @param string $sessionId
     * @return string Returns the read data (Binary), or an empty string.
     */
    public function read( $sessionId );

    /**
     * Session write handler
     *
     * @see http://php.net/sessionhandlerinterface.write
     *
     * @param string $sessionId
     * @param string $sessionData Binary session data
     * @return bool The return value (usually true on success, false on failure).
     */
    public function write( $sessionId, $sessionData );
}

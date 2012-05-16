<?php
/**
 * index.php
 *
 * This file on purpose does not use any PHP 5(.3) language features to be able to exit with
 * message about wrong php version even on PHP 4 or 5.2.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */


// Make sure we are on php 5.3 and validate request_order if not in cli mode
if ( version_compare( PHP_VERSION, '5.3.2' ) < 0 )
{
    // 5.3.2: Ubuntu 10.4 LTS, 5.3.3: RHEL 6
    echo '<h1>HiMVC does not like your PHP version: ' . PHP_VERSION . '</h1>';
    echo '<p>PHP 5.3.2 or higher is required!</p>';
    exit;
}

/**
 * Get ServiceContainer
 * @var \HiMVC\API\Container $container
 */
$container = require __DIR__ . '/bootstrap.php';

// Ignore user abort now that we are about to execute request
ignore_user_abort( true );

// Register shutdown event (but lazy load getting event object)
register_shutdown_function(
    function() use ( $container )
    {
        $container->getRequest()->session->stop();
    }
);

// "Execute" Request
echo $container->getDispatcher()->dispatch( $container->getRequest() ) . "\n";

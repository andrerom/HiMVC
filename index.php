<?php
/**
 * index.php
 *
 * This file on purpose does not use any PHP 5(.3) language features to be able to exit with
 * message about wrong php version even on PHP 4 or 5.2.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */


// Make sure we are on php 5.3 and validate request_order if not in cli mode
if ( version_compare( PHP_VERSION, '5.3' ) < 0 )
{
    echo '<h1>HiMVC does not like your PHP version: ' . PHP_VERSION . '</h1>';
    echo '<p>PHP 5.3.0 or higher is required!</p>';
    exit;
}

// Temporary, only for getting more accurate timeing during dev
if ( !isset( $_SEVER['REQUEST_TIME_FLOAT'] ) )
    $_SEVER['REQUEST_TIME_FLOAT'] = microtime( true );

/**
 * Get ServiceContainer
 * @var \HiMVC\API\Container $container
 */
$container = require 'bootstrap.php';

// Ignore user abort now that we are about to execute request
ignore_user_abort( true );

// Register shutdown event (but lazy load getting event object)
register_shutdown_function(
    function() use ( $container )
    {
        $container->getRequest()->session->stop();
    }
);

$request = $container->getRequest();
echo $container->getDispatcher()->dispatch( $request, true ) . "\n";


echo "ms: " .  ( (int) ( ( microtime( true) - $request->microTime ) * 10000 ) ) / 10 . "\n";

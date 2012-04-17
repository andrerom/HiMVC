<?php
/**
 * File contains: Test class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace HiMVC\Core\Base\Tests;
use HiMVC\Core\Base\ClassLoader,
    PHPUnit_Framework_TestCase;

/**
 * Test class
 */
class ClassLoaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \eZ\Publish\Core\Base\ClassLoader::load
     */
    public function testFileCheckReturnValuesOnFailure()
    {
        $loader = new ClassLoader(
            array(
                'eZ' => 'eZ',
                'xyz\\Pizza' => 'xyz/Pasta'
            )
        );

        self::assertFalse( $loader->load( "eZ\\Pizza\\Box" ) );
        self::assertFalse( $loader->load( "\\eZ\\Pizza\\Box" ) );
        self::assertFalse( $loader->load( "xyz\\Pizza\\Box" ) );
        self::assertFalse( $loader->load( "\\xyz\\Pizza\\Box" ) );
        self::assertNull( $loader->load( "NotHere\\Pizza\\Box" ) );// void
        self::assertNull( $loader->load( "\\NotHere\\Pizza\\Box" ) );// void
    }

    /**
     * @covers \eZ\Publish\Core\Base\ClassLoader::load
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testFailure()
    {
        $loader = new ClassLoader(
            array(
                'eZ' => 'eZ',
                'xyz' => 'xyz/Pasta'
            ),
            ClassLoader::PSR_0_NO_FILECHECK
        );

        include $loader->load( "eZ\\Will\\Fail", true );
    }

    /**
     * @covers \eZ\Publish\Core\Base\ClassLoader::load
     */
    public function testStrictModeMapping()
    {
        $loader = new ClassLoader(
            array(
                'eZ' => 'eZ',
                'xyz\\Pizza' => 'xyz/Pasta',
                'xyz' => 'xyz/Pasta'
            ),
            ClassLoader::PSR_0_STRICT_MODE | ClassLoader::PSR_0_NO_FILECHECK
        );

        self::assertEquals( 'eZ/Pizza/Box.php', $loader->load( "eZ\\Pizza\\Box", true ) );
        self::assertEquals( 'eZ/Pizza/Box.php', $loader->load( "\\eZ\\Pizza\\Box", true ) );
        self::assertEquals( 'eZ/Pizza/Paper_Box.php', $loader->load( "eZ\\Pizza\\Paper_Box", true ) );

        self::assertEquals( 'xyz/Pasta/Box.php', $loader->load( "xyz\\Pizza\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Box.php', $loader->load( "\\xyz\\Pizza\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Paper_Box.php', $loader->load( "xyz\\Pizza\\Paper_Box", true ) );

        self::assertEquals( 'xyz/Pasta/Bolognese/Box.php', $loader->load( "xyz\\Bolognese\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Bolognese/Box.php', $loader->load( "\\xyz\\Bolognese\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Bolognese/Paper_Box.php', $loader->load( "xyz\\Bolognese\\Paper_Box", true ) );
    }

    /**
     * @covers \eZ\Publish\Core\Base\ClassLoader::load
     */
    public function testPearCompatMapping()
    {
        $loader = new ClassLoader(
            array(
                'eZ' => 'eZ',
                'xyz\\Pizza' => 'xyz/Pasta',
                'xyz' => 'xyz/Pasta'
            ),
            ClassLoader::PSR_0_NO_FILECHECK
        );

        self::assertEquals( 'eZ/Pizza/Box.php', $loader->load( "eZ\\Pizza\\Box", true ) );
        self::assertEquals( 'eZ/Pizza/Box.php', $loader->load( "\\eZ\\Pizza\\Box", true ) );
        self::assertEquals( 'eZ/Pizza/Paper/Box.php', $loader->load( "eZ\\Pizza\\Paper_Box", true ) );

        self::assertEquals( 'xyz/Pasta/Box.php', $loader->load( "xyz\\Pizza\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Box.php', $loader->load( "\\xyz\\Pizza\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Paper/Box.php', $loader->load( "xyz\\Pizza\\Paper_Box", true ) );

        self::assertEquals( 'xyz/Pasta/Bolognese/Box.php', $loader->load( "xyz\\Bolognese\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Bolognese/Box.php', $loader->load( "\\xyz\\Bolognese\\Box", true ) );
        self::assertEquals( 'xyz/Pasta/Bolognese/Paper/Box.php', $loader->load( "xyz\\Bolognese\\Paper_Box", true ) );
    }
}

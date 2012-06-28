<?php
/**
 * File contains: Test class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Common\Tests;
use HiMVC\API\MVC\Values\AccessMatch;
use HiMVC\Core\Common\AccessMatcher;
use HiMVC\Core\MVC\Request;
use PHPUnit_Framework_TestCase;

/**
 * Test class
 */
class AccessMatcherTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @covers \HiMVC\API\MVC\Values\AccessMatch::match
     */
    public function testMatch()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $matcher = new AccessMatcher( array( 'site' => array( $access ) ) );
        $request = new Request();
        self::assertEquals( array( 'site' => $access ), $matcher->match( $request ) );
    }

    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @covers \HiMVC\API\MVC\Values\AccessMatch::match
     */
    public function testMatchDefault()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $defaultAccess = new AccessMatch( 'default', 'site', array( 'hosts' => array( 'something' ) ) );
        $matcher = new AccessMatcher( array( 'site' => array( $access, 'default' => $defaultAccess ) ) );
        $request = new Request( array( 'host' => 'db.com' ) );
        self::assertEquals( array( 'site' => $defaultAccess ), $matcher->match( $request ) );
    }

    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @covers \HiMVC\API\MVC\Values\AccessMatch::match
     * @expectedException eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testMatchMissingDefault()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $matcher = new AccessMatcher( array( 'site' => array( $access ) ) );
        $request = new Request( array( 'host' => 'db.com' ) );
        self::assertEquals( array(), $matcher->match( $request ) );
    }

    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @expectedException eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testMatchWrongMatchStructure()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $matcher = new AccessMatcher( array( 'site' => $access ) );
        $request = new Request();
        self::assertEquals( array(), $matcher->match( $request ) );
    }

    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @expectedException eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testMatchWrongMatchStructure2()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $matcher = new AccessMatcher( array( 'default' => array( $access ) ) );
        $request = new Request();
        self::assertEquals( array(), $matcher->match( $request ) );
    }

    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @expectedException eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testMatchWrongMatchStructure3()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $matcher = new AccessMatcher( array( $access ) );
        $request = new Request();
        self::assertEquals( array(), $matcher->match( $request ) );
    }

    /**
     * Test AccessMatcher::match
     *
     * @covers \HiMVC\Core\Common\AccessMatcher::match
     * @expectedException eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     */
    public function testMatchWrongMatchStructure4()
    {
        $access = new AccessMatch( 'test', 'site', array( 'hosts' => array( 'localhost' ) ) );
        $matcher = new AccessMatcher( array( 'site' => array( 'x' ) ) );
        $request = new Request();
        self::assertEquals( array(), $matcher->match( $request ) );
    }
}

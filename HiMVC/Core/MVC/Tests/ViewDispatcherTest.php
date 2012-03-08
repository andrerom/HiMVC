<?php
/**
 * File contains: Test class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Tests;
use HiMVC\Core\MVC\ViewDispatcher,
    PHPUnit_Framework_TestCase;

/**
 * Test class
 */
class ViewDispatcherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $viewMock1
     */
    protected $viewMock1;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $viewMock2
     */
    protected $viewMock2;

    /**
     * Setup mock
     */
    public function setUp()
    {
        parent::setUp();
        $this->viewMock1 = $this->getMock( 'HiMVC\API\MVC\Viewable' );
        $this->viewMock2 = $this->getMock( 'HiMVC\API\MVC\Viewable' );
    }

    /**
     * Tear down test
     */
    public function tearDown()
    {
        unset( $this->viewMock1 );
        unset( $this->viewMock2 );
        parent::tearDown();
    }

    /**
     * Test ViewDispatcher
     *
     * @covers \HiMVC\Core\MVC\ViewDispatcher::handle
     */
    public function testNoConditions()
    {
        $this->viewMock1->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/full.tpl' ),
                $this->equalTo( array() )
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock1, 'render' ) ), array() );
        $dispatcher->handle( 'content', 'read', 'full', array() );

        $this->viewMock2->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read.php' ),
                $this->equalTo( array() )
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'php' => array( $this->viewMock2, 'render' ) ), array() );
        $dispatcher->handle( 'content', 'read', '', array() );
    }

    /**
     * Test ViewDispatcher
     *
     * @covers \HiMVC\Core\MVC\ViewDispatcher::handle
     * @covers \HiMVC\Core\MVC\ViewDispatcher::getMatchingConditionTarget
     */
    public function testVerySimpleCondition()
    {
        $params = array( 'identifier' => 'gallery' );

        $this->viewMock1->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/full_frontpage.tpl' ),
                $this->equalTo( $params )
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock1, 'render' ) ), array(
            'frontpage' => array(
                'source' => 'content/read/full',
                'target' => 'content/read/full_frontpage.tpl',
            )
        ) );
        $dispatcher->handle( 'content', 'read', 'full', $params );

        $this->viewMock2->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/gallery.tpl' ),
                $this->equalTo($params )
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock2, 'render' ) ), array(
            'gallery' => array(
                'source' => 'content/read',
                'target' => 'content/read/gallery.tpl',
                'identifier' => 'gallery'
            )
        ) );
        $dispatcher->handle( 'content', 'read', '', $params );
    }

    /**
     * Test ViewDispatcher
     *
     * @todo Consider if conditions should be read in reverse order / prepended on match
     *
     * @covers \HiMVC\Core\MVC\ViewDispatcher::handle
     * @covers \HiMVC\Core\MVC\ViewDispatcher::getMatchingConditionTarget
     */
    public function testSimpleCondition()
    {
        $params = array( 'identifier' => 'gallery', 'remoteId' => 42 );

        $this->viewMock1->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/full_frontpage.tpl' ),
                $this->equalTo( $params )
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock1, 'render' ) ), array(
            'frontpage' => array(
                'source' => 'content/read/full',
                'target' => 'content/read/full_frontpage.tpl',
                'identifier' => 'gallery'
            ),
            'alternative_frontpage' => array(
                'source' => 'content/read/full',
                'target' => 'content/read/alternative_frontpage.tpl',
                'identifier' => 'gallery'
            ),
        ) );
        $dispatcher->handle( 'content', 'read', 'full', $params );

        $this->viewMock2->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/alternative_gallery.tpl' ),
                $this->equalTo( $params )
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock2, 'render' ) ), array(
            'gallery' => array(
                'source' => 'content/read',
                'target' => 'content/read/gallery.tpl',
                'identifier' => 'gallery',
            ),
            'alternative_gallery' => array(
                'source' => 'content/read',
                'target' => 'content/read/alternative_gallery.tpl',
                'remoteId' => 42,
            ),
        ) );
        $dispatcher->handle( 'content', 'read', '', $params );
    }
}

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

use HiMVC\Core\MVC\ViewDispatcher;
use HiMVC\API\MVC\Values\Result;
use HiMVC\Core\MVC\Request;
use PHPUnit_Framework_TestCase;

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
     * @var \HiMVC\Core\MVC\Request $request
     */
    protected $request;

    /**
     * Setup mock
     */
    public function setUp()
    {
        parent::setUp();
        $this->viewMock1 = $this->getMock( 'HiMVC\API\MVC\Viewable' );
        $this->viewMock2 = $this->getMock( 'HiMVC\API\MVC\Viewable' );
        $this->request = new Request();
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
     * @covers \HiMVC\Core\MVC\ViewDispatcher::view
     */
    public function testNoConditions()
    {
        $this->viewMock1->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/full.tpl' ),
                $this->anything()
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock1, 'render' ) ), array() );
        $dispatcher->view(
            $this->request,
            new Result( array( 'module' => 'content', 'action' => 'read', 'view' => 'full' ) )
        );

        $this->viewMock2->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read.php' ),
                $this->anything()
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'php' => array( $this->viewMock2, 'render' ) ), array() );
        $dispatcher->view(
            $this->request,
            new Result( array( 'module' => 'content', 'action' => 'read' ) )
        );
    }

    /**
     * Test ViewDispatcher
     *
     * @covers \HiMVC\Core\MVC\ViewDispatcher::view
     * @covers \HiMVC\Core\MVC\ViewDispatcher::getMatchingConditionTarget
     */
    public function testVerySimpleCondition()
    {
        $params = array( 'identifier' => 'gallery' );

        $this->viewMock1->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/full_frontpage.tpl' ),
                $this->anything()
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock1, 'render' ) ), array(
            'frontpage' => array(
                'source' => 'content/read/full',
                'target' => 'content/read/full_frontpage.tpl',
            )
        ) );
        $dispatcher->view(
            $this->request,
            new Result( array( 'module' => 'content', 'action' => 'read', 'view' => 'full', 'params' => $params ) )
        );

        $this->viewMock2->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/gallery.tpl' ),
                $this->anything()
            )->will( $this->returnValue( null ) );
        $dispatcher = new ViewDispatcher( array( 'tpl' => array( $this->viewMock2, 'render' ) ), array(
            'gallery' => array(
                'source' => 'content/read',
                'target' => 'content/read/gallery.tpl',
                'identifier' => 'gallery'
            )
        ) );
        $dispatcher->view(
            $this->request,
            new Result( array( 'module' => 'content', 'action' => 'read', 'params' => $params ) )
        );
    }

    /**
     * Test ViewDispatcher
     *
     * @covers \HiMVC\Core\MVC\ViewDispatcher::view
     * @covers \HiMVC\Core\MVC\ViewDispatcher::getMatchingConditionTarget
     */
    public function testSimpleCondition()
    {
        $params = array( 'identifier' => 'gallery', 'remoteId' => 42 );

        $this->viewMock1->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/full_frontpage.tpl' ),
                $this->anything()
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
        $dispatcher->view(
            $this->request,
            new Result( array( 'module' => 'content', 'action' => 'read', 'view' => 'full', 'params' => $params ) )
        );

        $this->viewMock2->expects( $this->once() )
            ->method( 'render' )
            ->with(
                $this->equalTo( 'content/read/alternative_gallery.tpl' ),
                $this->anything()
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
        $dispatcher->view(
            $this->request,
            new Result( array( 'module' => 'content', 'action' => 'read', 'params' => $params ) )
        );
    }
}

<?php
/**
 * File contains: Test class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Tests;

use HiMVC\Core\MVC\View\ViewDispatcher;
use HiMVC\API\MVC\Values\ResultItem;
use HiMVC\Core\MVC\Values\Request;
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
     * @var \HiMVC\API\MVC\Values\Request $request
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
     * @covers \HiMVC\Core\MVC\View\ViewDispatcher::view
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
            $this->getResultItem( array(),  array( 'view' => 'full' ) )
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
            $this->getResultItem( array() )
        );
    }

    /**
     * Test ViewDispatcher
     *
     * @covers \HiMVC\Core\MVC\View\ViewDispatcher::view
     * @covers \HiMVC\Core\MVC\View\ViewDispatcher::getMatchingConditionTarget
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
            $this->getResultItem( array(), $params + array( 'view' => 'full' ) )
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
                'params' => array( 'identifier' => 'gallery' ),
            )
        ) );
        $dispatcher->view(
            $this->request,
            $this->getResultItem( array(), $params )
        );
    }

    /**
     * Test ViewDispatcher
     *
     * @covers \HiMVC\Core\MVC\View\ViewDispatcher::view
     * @covers \HiMVC\Core\MVC\View\ViewDispatcher::getMatchingConditionTarget
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
                'params' => array( 'identifier' => 'gallery' ),
            ),
            'alternative_frontpage' => array(
                'source' => 'content/read/full',
                'target' => 'content/read/alternative_frontpage.tpl',
                'params' => array( 'identifier' => 'gallery' ),
            ),
        ) );
        $dispatcher->view(
            $this->request,
            $this->getResultItem( array(), $params + array( 'view' => 'full' ) )
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
                'params' => array( 'identifier' => 'gallery' ),
            ),
            'alternative_gallery' => array(
                'source' => 'content/read',
                'target' => 'content/read/alternative_gallery.tpl',
                'params' => array( 'remoteId' => 42 ),
            ),
        ) );
        $dispatcher->view(
            $this->request,
            $this->getResultItem( array(), $params )
        );
    }

    /**
     * @param object $model
     * @param array $params
     * @param string $action
     * @param string $controller
     * @return \HiMVC\API\MVC\Values\ResultItem
     */
    protected function getResultItem( $model, array $params = array(), $action = 'read', $controller = __CLASS__ )
    {
        return new ResultItem( array(
            'model' => $model,
            'module' => 'content',
            'action' => $action,
            'controller' => $controller,
            'params' => $params
        ) );
    }
}

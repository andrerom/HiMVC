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
use HiMVC\Core\MVC\View\DesignLoader;
use HiMVC\Core\MVC\View\PHP\PHPView;
use PHPUnit_Framework_TestCase;

/**
 * Test class
 */
class PHPViewTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $loaderMock
     */
    protected $loaderMock;

    /**
     * Setup mock
     */
    public function setUp()
    {
        parent::setUp();
        $this->loaderMock = $this->getMock( 'HiMVC\Core\MVC\View\DesignLoader', array(), array(), '', false );
    }

    /**
     * Tear down test
     */
    public function tearDown()
    {
        unset( $this->loaderMock );
        parent::tearDown();
    }

    /**
     * Test TwigView
     *
     * @covers \HiMVC\Core\MVC\View\TwigView::render
     */
    public function testRender()
    {
        $name = 'content/read/full.php';
        $params = array( 'id' => 42 );

        $this->loaderMock->expects( $this->once() )
            ->method( 'getPath' )
            ->with( $this->equalTo( $name ) )
            ->will( $this->returnValue( __DIR__ .'/template.php' ));

        $view = new PHPView( $this->loaderMock );
        $actual = $view->render( $name, $params );
        $this->assertEquals( 'Hello world', $actual );
    }
}

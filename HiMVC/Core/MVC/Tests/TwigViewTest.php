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
use HiMVC\Core\MVC\View\TwigView,
    HiMVC\Core\MVC\View\Twig\TwigDesignLoader,
    PHPUnit_Framework_TestCase;

/**
 * Test class
 */
class TwigViewTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $loaderMock
     */
    protected $loaderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $twigMock
     */
    protected $twigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $twigMock
     */
    protected $twigTemplateMock;

    /**
     * Setup mock
     */
    public function setUp()
    {
        if ( !class_exists( 'Twig_Environment' ) )
            $this->markTestSkipped( 'Twig_Environment was not available, skipping' );

        parent::setUp();
        $this->twigMock = $this->getMock( 'Twig_Environment' );
        $this->twigTemplateMock = $this->getMock( 'Twig_TemplateInterface' );
        $this->loaderMock = $this->getMock( 'HiMVC\Core\MVC\View\Twig\TwigDesignLoader', array(), array(), '', false );
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
        $name = 'content/read/full.tpl';
        $params = array( 'id' => 42 );

        $this->twigMock->expects( $this->once() )
            ->method( 'loadTemplate' )
            ->with( $this->equalTo( $name ) )
            ->will( $this->returnValue( $this->twigTemplateMock ) );

        $this->twigTemplateMock->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->equalTo( $params ) )
            ->will( $this->returnValue( 'Hello world' ) );

        $view = new TwigView( $this->twigMock );
        $actual = $view->render( $name, $params );
        $this->assertEquals( 'Hello world', $actual );
    }
}

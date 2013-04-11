<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;

class AnnotationsReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $kernelMoc;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $annotationReader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $testBundle;

    public function setUp()
    {
        if (!interface_exists('Doctrine\Common\Annotations\Reader')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->testBundle = $this->getMock(
            'Symfony\Bundle\FrameworkBundle\FrameworkBundle'
        );

        $this->kernelMoc = $this->getMock(
            'Symfony\Component\HttpKernel\KernelInterface',
            array()
        );

        $this->annotationReader = $this->getMock(
            'Doctrine\Common\Annotations\AnnotationReader'
        );
    }

    public function testGetEmptyData()
    {

        $this->kernelMoc->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array()));

        $reader = new AnnotationsReader($this->kernelMoc, $this->annotationReader);
        $this->assertEquals(0, count($reader->getData(array())));
    }

    public function testGetData()
    {

        $this->kernelMoc->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array($this->testBundle)));

        $routeMock = $this->getMock('Symfony\Component\Routing\Route', array(), array('/user/show/{id}'));

        $routeMock->expects($this->once())
                ->method('getDefault')
                ->with($this->equalTo('_controller'));

        $reader = new AnnotationsReader($this->kernelMoc, $this->annotationReader);

        $this->assertTrue(is_array($reader->getData(array($routeMock))));
    }
}

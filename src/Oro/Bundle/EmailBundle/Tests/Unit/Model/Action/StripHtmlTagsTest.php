<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Action;

use Oro\Bundle\EmailBundle\Model\Action\StripHtmlTags;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;

class StripHtmlTagsTest extends \PHPUnit\Framework\TestCase
{
    /** @var StripHtmlTags */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var HtmlTagHelper */
    protected $helper;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock('Oro\Component\ConfigExpression\ContextAccessor');

        $this->helper = $this->getMockBuilder('Oro\Bundle\UIBundle\Tools\HtmlTagHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new StripHtmlTags($this->contextAccessor, $this->helper);

        $this->action->setDispatcher($this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
    }

    public function testInitializeWithNamedOptions()
    {
        $options = [
            'html' => '$.html',
            'attribute' => '$.attribute'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.html', ReflectionUtil::getPropertyValue($this->action, 'html'));
        $this->assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithArrayOptions()
    {
        $options = [
            '$.attribute',
            '$.html'
        ];

        $this->action->initialize($options);

        $this->assertEquals('$.html', ReflectionUtil::getPropertyValue($this->action, 'html'));
        $this->assertEquals('$.attribute', ReflectionUtil::getPropertyValue($this->action, 'attribute'));
    }

    public function testInitializeWithMissingOption()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $options = [
            '$.attribute'
        ];

        $this->action->initialize($options);
    }

    public function testExecuteAction()
    {
        $options = [
            'html' => '$.html',
            'attribute' => '$.attribute'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with(
                $this->equalTo($fakeContext),
                $this->equalTo('$.html')
            )->will($this->returnValue($html = '<html></html>'));

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with(
                $this->equalTo($fakeContext),
                $this->equalTo('$.attribute'),
                $this->equalTo($stripped = 'stripped')
            );

        $this->helper->expects($this->once())
            ->method('purify')
            ->with($this->equalTo($html))
            ->will($this->returnValue($purified = 'purified'));

        $this->helper->expects($this->once())
            ->method('stripTags')
            ->with($this->equalTo($purified))
            ->will($this->returnValue($stripped));

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }
}

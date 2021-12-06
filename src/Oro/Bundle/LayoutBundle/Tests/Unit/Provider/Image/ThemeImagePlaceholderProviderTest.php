<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LayoutBundle\Provider\Image\ThemeImagePlaceholderProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ThemeImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private LayoutContextHolder|\PHPUnit\Framework\MockObject\MockObject $contextHolder;

    private ThemeManager|\PHPUnit\Framework\MockObject\MockObject $themeManager;

    private CacheManager|\PHPUnit\Framework\MockObject\MockObject $imagineCacheManager;

    private ThemeImagePlaceholderProvider $provider;

    protected function setUp(): void
    {
        $this->contextHolder = $this->createMock(LayoutContextHolder::class);
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->imagineCacheManager = $this->createMock(CacheManager::class);

        $this->provider = new ThemeImagePlaceholderProvider(
            $this->contextHolder,
            $this->themeManager,
            $this->imagineCacheManager,
            'pl2'
        );
    }

    /**
     * @dataProvider getPathDataProvider
     *
     * @param string $format
     * @param string $expectedPath
     */
    public function testGetPath(string $format, string $expectedPath): void
    {
        $themeName = 'test_theme';

        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getOr')
            ->with('theme')
            ->willReturn($themeName);

        $this->contextHolder->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders(['pl1' => '/path/to/pl1.img', 'pl2' => '/path/to/pl2.img']);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $filter = 'image_filter';

        $this->imagineCacheManager->expects(self::once())
            ->method('generateUrl')
            ->with($expectedPath, $filter, [], null, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/path/to/filtered_pl2.img');

        self::assertEquals('/path/to/filtered_pl2.img', $this->provider->getPath($filter, $format));
    }

    public function getPathDataProvider(): array
    {
        return [
            'path unchanged if format is empty' => ['format' => '', 'expectedPath' => '/path/to/pl2.img'],
            'path unchanged if format is the same' => ['format' => 'img', 'expectedPath' => '/path/to/pl2.img'],
            'path with new extension if format is not the same' => [
                'format' => 'webp',
                'expectedPath' => '/path/to/pl2.img.webp',
            ],
        ];
    }

    public function testGetPathWithoutContext(): void
    {
        $this->contextHolder->expects(self::once())
            ->method('getContext')
            ->willReturn(null);

        $this->themeManager->expects(self::never())
            ->method(self::anything());

        $this->imagineCacheManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutThemeName(): void
    {
        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getOr')
            ->with('theme')
            ->willReturn(null);

        $this->contextHolder->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $this->themeManager->expects(self::never())
            ->method(self::anything());

        $this->imagineCacheManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutPlaceholder(): void
    {
        $themeName = 'test_theme';

        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getOr')
            ->with('theme')
            ->willReturn($themeName);

        $this->contextHolder->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $theme = new Theme($themeName);
        $theme->setImagePlaceholders(['pl1' => '/path/to/pl1.img']);

        $this->themeManager->expects(self::once())
            ->method('getTheme')
            ->with($themeName)
            ->willReturn($theme);

        $filter = 'image_filter';

        $this->imagineCacheManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath($filter));
    }
}

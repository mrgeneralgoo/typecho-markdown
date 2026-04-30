<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TypechoPlugin\MarkdownParse\Plugin;
use TypechoPlugin\MarkdownParse\Tests\Support\ResetSingletonsTrait;
use Widget\Options;

final class PluginTest extends TestCase
{
    use ResetSingletonsTrait;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetMarkdownParse();
        $this->resetTypechoWidgets();
    }

    /**
     * Inject a mock Options widget into the Typecho widget pool so that
     * Options::alloc()->plugin('MarkdownParse') returns a controlled config.
     */
    private function mockOptions(array $pluginOptions, string $siteUrl = 'https://example.com'): void
    {
        $pluginConfig = Mockery::mock();
        foreach ($pluginOptions as $key => $value) {
            $pluginConfig->{$key} = $value;
        }

        $options = Mockery::mock(Options::class)->makePartial();
        $options->shouldReceive('plugin')->with('MarkdownParse')->andReturn($pluginConfig);
        $options->siteUrl = $siteUrl;

        // Inject into Typecho widget pool via reflection.
        // Common::nativeClassName('Widget\\Options') = 'Widget_Options'
        $reflection = new \ReflectionClass(\Typecho\Widget::class);
        $poolProp = $reflection->getProperty('widgetPool');
        $poolProp->setAccessible(true);
        $pool = $poolProp->getValue();
        $pool['Widget_Options'] = $options;
        $poolProp->setValue(null, $pool);
    }

    public function testParseEnablesTocWhenConfigEnabled(): void
    {
        $this->mockOptions([
            'is_available_toc' => 1,
            'internal_hosts'   => '',
        ]);

        $html = Plugin::parse("[TOC]\n\n# H1\n\n## H2\n");

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('H1', $html);
        $this->assertStringNotContainsString('[TOC]', $html);
    }

    public function testParseFallsBackToSiteUrlHostWhenInternalHostsEmpty(): void
    {
        $this->mockOptions([
            'is_available_toc' => 0,
            'internal_hosts'   => '',
        ], 'https://example.com');

        $html = Plugin::parse('[outside](https://other.com/page)');

        // External link should get rel attributes
        $this->assertStringContainsString('rel="', $html);
    }
}

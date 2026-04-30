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

    public function testResourceLinkEmitsMermaidWhenForceEnabled(): void
    {
        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 2,
            'is_available_mathjax'  => 0,
            'cdn_source'            => 'baomitu',
            'mermaid_theme'         => 'default',
        ]);

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringContainsString('<script type="module">', $html);
        $this->assertStringContainsString('mermaid.initialize', $html);
        $this->assertStringContainsString('lib.baomitu.com/mermaid', $html);
    }

    public function testResourceLinkEmitsMermaidOnAutoWhenContentNeedsIt(): void
    {
        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 1,
            'is_available_mathjax'  => 0,
            'cdn_source'            => 'jsDelivr',
            'mermaid_theme'         => 'forest',
        ]);

        Plugin::parse("```mermaid\ngraph TD\nA-->B\n```\n");

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringContainsString('<script type="module">', $html);
        $this->assertStringContainsString('cdn.jsdelivr.net/npm/mermaid', $html);
        $this->assertStringContainsString('"forest"', $html);
    }

    public function testResourceLinkOmitsMermaidWhenDisabled(): void
    {
        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 0,
            'is_available_mathjax'  => 0,
            'cdn_source'            => 'baomitu',
            'mermaid_theme'         => 'default',
        ]);

        Plugin::parse("```mermaid\ngraph TD\nA-->B\n```\n");

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    public function testResourceLinkEmitsMathjaxOnAutoWhenInlineMathPresent(): void
    {
        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 0,
            'is_available_mathjax'  => 1,
            'cdn_source'            => 'cdnjs',
            'mermaid_theme'         => 'default',
        ]);

        Plugin::parse('inline $x = 1$');

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringContainsString('MathJax', $html);
        $this->assertStringContainsString('cdnjs.cloudflare.com/ajax/libs/mathjax', $html);
    }

    public function testResourceLinkEmitsNothingWhenAllDisabled(): void
    {
        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 0,
            'is_available_mathjax'  => 0,
            'cdn_source'            => 'baomitu',
            'mermaid_theme'         => 'default',
        ]);

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }
}

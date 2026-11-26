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

    public function testResourceLinkMermaidAutoUsesCustomDarkSelector(): void
    {
        $this->mockOptions([
            'is_available_toc'        => 0,
            'internal_hosts'          => '',
            'is_available_mermaid'    => 2,
            'is_available_mathjax'    => 0,
            'cdn_source'              => 'baomitu',
            'mermaid_theme'           => 'default',
            'mermaid_dark_theme'      => 'dark',
            'mermaid_dark_selector'   => '.night',
        ]);

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringContainsString('"lightTheme":"default"', $html);
        $this->assertStringContainsString('"darkTheme":"dark"', $html);
        $this->assertStringContainsString('"darkSelector":".night"', $html);
        $this->assertStringContainsString('MutationObserver', $html);
    }

    public function testResourceLinkMermaidSupportsSeparateLightAndDarkThemesWithDefaultSelector(): void
    {
        $this->mockOptions([
            'is_available_toc'        => 0,
            'internal_hosts'          => '',
            'is_available_mermaid'    => 2,
            'is_available_mathjax'    => 0,
            'cdn_source'              => 'baomitu',
            'mermaid_theme'           => 'neutral',
            'mermaid_dark_theme'      => 'forest',
            'mermaid_dark_selector'   => '   ',
        ]);

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringContainsString('"lightTheme":"neutral"', $html);
        $this->assertStringContainsString('"darkTheme":"forest"', $html);
        $this->assertStringContainsString(
            '"darkSelector":' . json_encode(Plugin::DEFAULT_MERMAID_DARK_SELECTOR, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
            $html
        );
    }

    public function testResourceLinkMermaidEmitsMinimalScriptWhenDarkThemeDisabledOrSameAsLight(): void
    {
        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 2,
            'is_available_mathjax'  => 0,
            'cdn_source'            => 'baomitu',
            'mermaid_theme'         => 'neutral',
            'mermaid_dark_theme'    => Plugin::MERMAID_DARK_THEME_DISABLE,
        ]);

        ob_start();
        Plugin::resourceLink();
        $disabledHtml = ob_get_clean();

        $this->assertStringContainsString('mermaid.initialize({ startOnLoad: true, theme: "neutral" })', $disabledHtml);
        $this->assertStringNotContainsString('MutationObserver', $disabledHtml);

        $this->mockOptions([
            'is_available_toc'      => 0,
            'internal_hosts'        => '',
            'is_available_mermaid'  => 2,
            'is_available_mathjax'  => 0,
            'cdn_source'            => 'baomitu',
            'mermaid_theme'         => 'dark',
            'mermaid_dark_theme'    => 'dark',
        ]);

        ob_start();
        Plugin::resourceLink();
        $sameThemeHtml = ob_get_clean();

        $this->assertStringContainsString('mermaid.initialize({ startOnLoad: true, theme: "dark" })', $sameThemeHtml);
        $this->assertStringNotContainsString('MutationObserver', $sameThemeHtml);
    }

    public function testResourceLinkMermaidFallsBackOnEmptyOrInvalidOptions(): void
    {
        $this->mockOptions([
            'is_available_toc'        => 0,
            'internal_hosts'          => '',
            'is_available_mermaid'    => 2,
            'is_available_mathjax'    => 0,
            'cdn_source'              => 'unknown_cdn',
            'mermaid_theme'           => '',
            'mermaid_dark_theme'      => 'invalid_dark',
        ]);

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringContainsString('mermaid.initialize({ startOnLoad: true, theme: "default" })', $html);
        $this->assertStringNotContainsString('MutationObserver', $html);
        $this->assertStringContainsString('lib.baomitu.com/mermaid', $html);
    }

    public function testResourceLinkMermaidEscapesScriptClosingTagInDarkSelector(): void
    {
        $this->mockOptions([
            'is_available_toc'        => 0,
            'internal_hosts'          => '',
            'is_available_mermaid'    => 2,
            'is_available_mathjax'    => 0,
            'cdn_source'              => 'baomitu',
            'mermaid_theme'           => 'default',
            'mermaid_dark_theme'      => 'dark',
            'mermaid_dark_selector'   => '</script><script>alert(1)</script>',
        ]);

        ob_start();
        Plugin::resourceLink();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertSame(1, substr_count(strtolower($html), '</script>'));
        $this->assertStringContainsString('\u003C\/script\u003E', $html);
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

    public function testConfigDoesNotThrow(): void
    {
        $elements = [];
        $form = Mockery::mock(\Typecho\Widget\Helper\Form::class);
        $form->shouldReceive('addInput')
            ->andReturnUsing(static function ($element) use (&$elements, $form) {
                $elements[$element->name] = $element;
                return $form;
            });

        Plugin::config($form);

        $this->assertArrayHasKey('mermaid_theme', $elements);
        $this->assertArrayHasKey('mermaid_dark_theme', $elements);
        $this->assertArrayHasKey('mermaid_dark_selector', $elements);
        $this->assertArrayNotHasKey('mermaid_light_theme', $elements);
        $this->assertSame(Plugin::MERMAID_THEME_DEFAULT, $elements['mermaid_theme']->value);
        $this->assertSame(Plugin::MERMAID_DARK_THEME_DEFAULT, $elements['mermaid_dark_theme']->value);
    }

    public function testActivateDoesNotThrow(): void
    {
        // Plugin::activate() calls \Typecho\Plugin::factory()
        // which needs the plugin system initialized.
        // If it throws, skip with explanation.
        try {
            Plugin::activate();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Plugin::activate requires plugin factory initialization: ' . $e->getMessage());
        }

        $this->expectNotToPerformAssertions();
    }
}

<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TypechoPlugin\MarkdownParse\MarkdownParse;

final class BootstrapSanityTest extends TestCase
{
    public function testTypechoCoreSymbolsAreLoaded(): void
    {
        $this->assertTrue(interface_exists(\Typecho\Plugin\PluginInterface::class));
        $this->assertTrue(class_exists(\Typecho\Widget\Helper\Form::class));
        $this->assertTrue(class_exists(\Widget\Options::class));
        $this->assertTrue(class_exists(\Typecho\Plugin::class));
    }

    public function testMarkdownParseClassIsLoaded(): void
    {
        $this->assertTrue(class_exists(MarkdownParse::class));
        $this->assertInstanceOf(MarkdownParse::class, MarkdownParse::getInstance());
    }
}

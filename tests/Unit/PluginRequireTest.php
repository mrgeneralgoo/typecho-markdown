<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TypechoPlugin\MarkdownParse\MarkdownParse;
use TypechoPlugin\MarkdownParse\Plugin;
use TypechoPlugin\MarkdownParse\Tests\Support\ResetSingletonsTrait;

final class PluginRequireTest extends TestCase
{
    use ResetSingletonsTrait;

    public function testPluginClassIsLoadable(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    public function testResetMarkdownParseGivesFreshInstance(): void
    {
        $first = MarkdownParse::getInstance();
        $first->setIsTocEnable(true);

        $this->resetMarkdownParse();

        $second = MarkdownParse::getInstance();
        $this->assertNotSame($first, $second);
        $this->assertFalse($second->getIsTocEnable());
    }

    public function testResetTypechoWidgetsDoesNotThrow(): void
    {
        $this->resetTypechoWidgets();
        $this->expectNotToPerformAssertions();
    }
}

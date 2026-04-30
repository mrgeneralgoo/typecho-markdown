<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypechoPlugin\MarkdownParse\Tests\Support\Fixtures;

final class FixturesTest extends TestCase
{
    public function testLoadsExistingFile(): void
    {
        // 临时写一个 fixture 用于断言
        $tempName = 'fixtures-test-temp.txt';
        $tempPath = __DIR__ . '/../../fixtures/' . $tempName;
        file_put_contents($tempPath, "hello world\n");

        try {
            $this->assertSame("hello world\n", Fixtures::load($tempName));
        } finally {
            unlink($tempPath);
        }
    }

    public function testThrowsWhenFixtureMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fixture not found: not-here.md');
        Fixtures::load('not-here.md');
    }
}

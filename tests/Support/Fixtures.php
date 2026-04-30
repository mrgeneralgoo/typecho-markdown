<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Support;

use RuntimeException;

final class Fixtures
{
    public static function load(string $name): string
    {
        $path = __DIR__ . '/../fixtures/' . $name;
        if (!is_file($path)) {
            throw new RuntimeException("Fixture not found: {$name}");
        }

        return file_get_contents($path);
    }
}

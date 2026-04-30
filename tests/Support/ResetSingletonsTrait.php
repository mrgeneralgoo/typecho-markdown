<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Support;

use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Typecho\Widget;
use TypechoPlugin\MarkdownParse\MarkdownParse;

trait ResetSingletonsTrait
{
    protected function resetMarkdownParse(): void
    {
        $reflection = new ReflectionClass(MarkdownParse::class);
        $instance = $reflection->getProperty('instance');
        $instance->setValue(null, null);
    }

    protected function resetTypechoWidgets(): void
    {
        $reflection = new ReflectionClass(Widget::class);
        $candidates = ['widgetPool', '_widgetPool', 'pool', 'widgets'];

        foreach ($candidates as $name) {
            try {
                $property = $reflection->getProperty($name);
            } catch (ReflectionException) {
                continue;
            }

            if ($property->isStatic()) {
                $property->setValue(null, []);
            } else {
                $property->setValue([]);
            }
            return;
        }

        throw new RuntimeException(
            'Could not locate Typecho widget pool property; checked: ' . implode(', ', $candidates)
        );
    }
}

<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex\Test\Unit;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use Samwilson\CommonMarkLatex\LatexRendererExtension;

class RendererTest extends TestCase
{
    public function testDataFiles(): void
    {
        $environment = new Environment(['html_input' => 'allow']);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new LatexRendererExtension());
        $converter     = new MarkdownConverter($environment);
        $markdownFiles = \glob(\dirname(__DIR__) . '/data/*.md');
        foreach ($markdownFiles as $markdownFile) {
            if (\basename($markdownFile) === 'README.md') {
                continue;
            }

            $markdown = \file_get_contents($markdownFile);
            $texFile  = \dirname($markdownFile) . '/' . \pathinfo($markdownFile, PATHINFO_FILENAME) . '.tex';
            $this->assertSame(\file_get_contents($texFile), $converter->convert($markdown)->getContent());
        }
    }
}

<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex\Test\Integration;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use Samwilson\CommonMarkLatex\LatexRendererExtension;
use Symfony\Component\Process\Process;

class CompileTest extends TestCase
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

            $workingDir = \dirname($markdownFile);
            // Create a temp tex file in the tests directory, so it's got access to images etc.
            $tmpTexFile = $workingDir . '/CompileTest_' . \pathinfo($markdownFile, PATHINFO_FILENAME) . '.tex';
            $markdown   = \file_get_contents($markdownFile);
            $latex      = "\\documentclass{article}\n"
                . "\\usepackage{listings, graphicx, hyperref, footmisc}\n"
                . "\\begin{document}\n"
                . $converter->convert($markdown)->getContent() . "\n"
                . '\\end{document}';
            \file_put_contents($tmpTexFile, $latex);
            $process = new Process(['pdflatex', '-halt-on-error', $tmpTexFile], \dirname($markdownFile));
            $process->mustRun();
            $this->assertStringContainsString('Output written', $process->getOutput());
            $this->assertStringContainsString('1 page', $process->getOutput());
        }
    }
}

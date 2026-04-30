<?php

declare(strict_types=1);

namespace TypechoPlugin\MarkdownParse\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TypechoPlugin\MarkdownParse\MarkdownParse;
use TypechoPlugin\MarkdownParse\Tests\Support\Fixtures;
use TypechoPlugin\MarkdownParse\Tests\Support\ResetSingletonsTrait;

final class MarkdownParseTest extends TestCase
{
    use ResetSingletonsTrait;

    private MarkdownParse $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetMarkdownParse();
        $this->parser = MarkdownParse::getInstance();
    }

    public function testParsesAtxHeading(): void
    {
        $html = $this->parser->parse('# Hello');
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function testParsesUnorderedList(): void
    {
        $html = $this->parser->parse("- a\n- b\n");
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>a</li>', $html);
        $this->assertStringContainsString('<li>b</li>', $html);
    }

    public function testParsesFencedCodeBlock(): void
    {
        $html = $this->parser->parse("```php\necho 1;\n```\n");
        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString('echo 1;', $html);
    }

    public function testParsesGfmTable(): void
    {
        $md = "| a | b |\n| - | - |\n| 1 | 2 |\n";
        $html = $this->parser->parse($md);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>a</th>', $html);
        $this->assertStringContainsString('<td>1</td>', $html);
    }

    public function testParsesTaskList(): void
    {
        $md = "- [ ] todo\n- [x] done\n";
        $html = $this->parser->parse($md);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function testParsesStrikethrough(): void
    {
        $html = $this->parser->parse('~~gone~~');
        $this->assertStringContainsString('<del>gone</del>', $html);
    }

    public function testParsesFootnote(): void
    {
        $md = "Hello[^1]\n\n[^1]: footnote body\n";
        $html = $this->parser->parse($md);
        $this->assertStringContainsString('class="footnote', $html);
    }

    public function testParsesDescriptionList(): void
    {
        $md = "term\n: definition\n";
        $html = $this->parser->parse($md);
        $this->assertStringContainsString('<dl>', $html);
        $this->assertStringContainsString('<dt>term</dt>', $html);
        $this->assertStringContainsString('<dd>definition</dd>', $html);
    }

    public function testMermaidCodeBlockGetsClassMermaid(): void
    {
        $md = Fixtures::load('mermaid-flowchart.md');
        $html = $this->parser->parse($md);

        $this->assertStringContainsString('<code class="mermaid">', $html);
        $this->assertStringNotContainsString('class="language-mermaid"', $html);
        $this->assertTrue($this->parser->getIsNeedMermaid());
    }

    public function testNonMermaidFencedBlockUntouched(): void
    {
        $md = "```python\nprint(1)\n```\n";
        $html = $this->parser->parse($md);

        $this->assertStringContainsString('class="language-python"', $html);
        $this->assertFalse($this->parser->getIsNeedMermaid());
    }
}

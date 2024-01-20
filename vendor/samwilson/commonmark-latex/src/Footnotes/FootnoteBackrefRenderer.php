<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex\Footnotes;

use League\CommonMark\Extension\Footnote\Node\FootnoteBackref;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteBackrefRenderer implements NodeRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        FootnoteBackref::assertInstanceOf($node);

        return '';
    }
}

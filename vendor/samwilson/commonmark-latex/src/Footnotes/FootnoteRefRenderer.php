<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex\Footnotes;

use League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteRefRenderer implements NodeRendererInterface
{
    public static function cleanFootnoteLabel(string $label): string
    {
        return \str_replace('#', '', $label);
    }

    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        FootnoteRef::assertInstanceOf($node);

        $fnLabel = self::cleanFootnoteLabel($node->getReference()->getDestination());

        // Footnote already used, just reference it.
        if (isset(GatherFootnotesListener::$footnotesUsed[$fnLabel])) {
            return '\\footref{' . $fnLabel . '}';
        }

        // New footnote not yet seen.
        GatherFootnotesListener::$footnotesUsed[$fnLabel] = true;
        $footnote                                         = GatherFootnotesListener::$footnotes[$fnLabel];

        return '\\footnote{\label{' . $fnLabel . '}' . $childRenderer->renderNodes($footnote->children()) . '}';
    }
}

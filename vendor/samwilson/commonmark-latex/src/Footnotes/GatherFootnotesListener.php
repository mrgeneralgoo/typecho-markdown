<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex\Footnotes;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Footnote\Node\Footnote;
use League\CommonMark\Node\NodeIterator;

final class GatherFootnotesListener
{
    /** @var Node[] */
    public static array $footnotes;

    /** @var bool[] */
    public static array $footnotesUsed = [];

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $document            = $event->getDocument();
        self::$footnotesUsed = [];

        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (! $node instanceof Footnote) {
                continue;
            }

            $ref = $document->getReferenceMap()->get($node->getReference()->getLabel());
            if ($ref === null) {
                continue;
            }

            self::$footnotes[FootnoteRefRenderer::cleanFootnoteLabel($ref->getDestination())] = $node;
        }
    }
}

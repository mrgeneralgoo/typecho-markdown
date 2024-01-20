<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex;

use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class ListBlockRenderer implements NodeRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        ListBlock::assertInstanceOf($node);

        $listType       = $node->getListData()->type === ListBlock::TYPE_BULLET ? 'itemize' : 'enumerate';
        $innerSeparator = $childRenderer->getInnerSeparator();

        return '\\begin{' . $listType . '}' . "\n"
            . $innerSeparator . $childRenderer->renderNodes($node->children()) . $innerSeparator
            . '\\end{' . $listType . '}';
    }
}

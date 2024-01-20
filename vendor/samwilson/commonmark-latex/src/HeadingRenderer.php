<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class HeadingRenderer implements NodeRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        $sec = 'section';
        if ($node->getLevel() === 2) {
            $sec = 'subsection';
        } elseif ($node->getLevel() === 3) {
            $sec = 'subsubsection';
        } elseif ($node->getLevel() === 4) {
            $sec = 'paragraph';
        }

        return '\\' . $sec . '{' . $childRenderer->renderNodes($node->children()) . '}';
    }
}

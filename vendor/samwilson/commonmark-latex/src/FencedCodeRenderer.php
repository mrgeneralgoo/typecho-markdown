<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class FencedCodeRenderer implements NodeRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        FencedCode::assertInstanceOf($node);

        $lang      = '';
        $infoWords = $node->getInfoWords();
        if (\count($infoWords) > 0) {
            $lang = $infoWords[0];
        }

        return '\\lstset{language={' . $lang . '}}\\begin{lstlisting}' . "\n"
            . $node->getLiteral()
            . '\\end{lstlisting}';
    }
}

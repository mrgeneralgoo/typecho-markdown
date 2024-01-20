<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex;

use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

final class LatexSpecialCharsParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('(\\\\|&|%|\$|\#|_|\{|\}|~|(?<!\[)(\^)(?=\s*\]?))');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $char = $inlineContext->getFullMatch();

        if (\in_array($char, ['&', '%', '$', '#', '_', '\\', '{', '}'], true)) {
            $charEscaped = '\\' . $char;
        } elseif ($char === '~') {
            $charEscaped = '\\textasciitilde';
        } elseif ($char === '^') {
            $charEscaped = '\\textasciicircum';
        } elseif ($char === '\\') {
            $charEscaped = '\\textbackslash';
        }

        $inlineContext->getCursor()->advanceBy(\strlen($char));
        $inlineContext->getContainer()->appendChild(new Text($charEscaped));

        return true;
    }
}

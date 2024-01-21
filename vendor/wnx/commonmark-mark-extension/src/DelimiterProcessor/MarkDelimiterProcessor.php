<?php

declare(strict_types=1);

namespace Wnx\CommonmarkMarkExtension\DelimiterProcessor;

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\DelimiterProcessorInterface;
use League\CommonMark\Node\Inline\AbstractStringContainer;
use Wnx\CommonmarkMarkExtension\Element\Mark;

class MarkDelimiterProcessor implements DelimiterProcessorInterface
{
    /** @psalm-readonly */
    private string $char;

    /**
     * @param string $char The mark character to use (typically '==' or '::')
     */
    public function __construct(string $char)
    {
        $this->char = $char;
    }

    public function getOpeningCharacter(): string
    {
        return $this->char;
    }

    public function getClosingCharacter(): string
    {
        return $this->char;
    }

    public function getMinLength(): int
    {
        return 2;
    }

    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer): int
    {
        if ($opener->getLength() === 2 && $closer->getLength() === 2) {
            return 2;
        }

        return 0;
    }

    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse): void
    {
        $strikethrough = new Mark(\str_repeat($this->char, $delimiterUse));

        $tmp = $opener->next();
        while ($tmp !== null && $tmp !== $closer) {
            $next = $tmp->next();
            $strikethrough->appendChild($tmp);
            $tmp = $next;
        }

        $opener->insertAfter($strikethrough);
    }
}

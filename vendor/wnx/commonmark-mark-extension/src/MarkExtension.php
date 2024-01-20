<?php

declare(strict_types=1);

namespace Wnx\CommonmarkMarkExtension;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;
use Wnx\CommonmarkMarkExtension\DelimiterProcessor\MarkDelimiterProcessor;
use Wnx\CommonmarkMarkExtension\Element\Mark;
use Wnx\CommonmarkMarkExtension\Renderer\MarkRenderer;

/** @psalm-api */
class MarkExtension implements ConfigurableExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $char = $environment->getConfiguration()->get('mark/character');

        $environment
            ->addDelimiterProcessor(new MarkDelimiterProcessor($char))
            ->addRenderer(Mark::class, new MarkRenderer(), 10);
    }

    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('mark', Expect::structure([
            'character' => Expect::string("="),
        ]));
    }
}

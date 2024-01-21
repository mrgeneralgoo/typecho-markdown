<?php

namespace SimonVomEyser\CommonMarkExtension;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;

class LazyImageExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('lazy_image', Expect::structure([
            'strip_src' => Expect::bool(false),
            'data_attribute' => Expect::string(''),
            'html_class' => Expect::string('')
        ]));
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $lazyImageRenderer = new LazyImageRenderer($environment->getConfiguration(), new ImageRenderer());
        $environment->addRenderer(Image::class, $lazyImageRenderer, 10);
    }
}

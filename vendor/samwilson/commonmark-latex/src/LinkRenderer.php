<?php

declare(strict_types=1);

namespace Samwilson\CommonMarkLatex;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\RegexHelper;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

class LinkRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        Link::assertInstanceOf($node);

        $out = $childRenderer->renderNodes($node->children());

        $replacements = [
            '_' => '\\_',
            '#' => '\#',
            '%' => '\%',
        ];
        $url          = \str_replace(\array_keys($replacements), $replacements, $node->getUrl());

        $allowUnsafeLinks = $this->config->get('allow_unsafe_links');
        if (! $allowUnsafeLinks && RegexHelper::isLinkPotentiallyUnsafe($url)) {
            return $out;
        }

        // Autolink extension makes the label and the unescaped URL the same.
        if ($out === $node->getUrl()) {
            return '\\url{' . $url . '}';
        }

        $title = $node->getTitle();
        if ($title) {
            $title .= ': ';
        }

        return $out . '\\footnote{' . $title . '\\url{' . $url . '}}';
    }
}

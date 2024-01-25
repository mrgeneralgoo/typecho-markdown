<?php

namespace TypechoPlugin\MarkdownParse;

require_once __DIR__ . '/vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\MarkdownConverter;
use Wnx\CommonmarkMarkExtension\MarkExtension;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use SimonVomEyser\CommonMarkExtension\LazyImageExtension;

class MarkdownParse
{

    // Flag to determine if Table of Contents (TOC) is enabled
    private bool $isTocEnable = false;

    // Flag to determine if Mermaid support is needed
    private bool $isNeedMermaid = false;

    // Flag to determine if LaTex support is needed
    private bool $isNeedLaTex = false;

    // The internal hosts, supports regular expressions ("/(^|\.)example\.com$/"), multiple values can be separated by commas.
    private string $internalHosts = '';

    // Singleton instance of MarkdownParse
    private static ?MarkdownParse $instance = null;

    // Private constructor to enforce singleton pattern
    private function __construct()
    {
    }

    /**
     * Get the singleton instance of MarkdownParse
     *
     * @return MarkdownParse The singleton instance
     * @throws \RuntimeException If PHP version is less than 8.0
     */
    public static function getInstance(): MarkdownParse
    {
        if (self::$instance === null) {
            $requiredVersion = '8.0';
            if (version_compare(phpversion(), $requiredVersion, '<')) {
                throw new \RuntimeException('MarkdownParse requires PHP ' . $requiredVersion . ' or later.');
            }
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Parse the given text using CommonMark with optional configuration
     *
     * @param string $text The input Markdown text
     * @param array $config Optional configuration for the parsing process
     * @return string The parsed HTML content
     */
    public function parse(string $text, array $config = []): string
    {
        list($text, $config) = $this->preParse($text, $config);

        $environment = new Environment(array_merge($this->getConfig(), $config));

        $this->addCommonMarkExtensions($environment);

        $htmlContent = (new MarkdownConverter($environment))->convert($text)->getContent();

        list($htmlContent, $config) = $this->postParse($htmlContent, $config);

        return $htmlContent;
    }

    /**
     * Placeholder function for actions to be performed before parsing
     *
     * @param string $text The input text
     * @param array $config Optional configuration for the parsing process
     * @return array Result of actions before parsing
     */
    public function preParse(string $text, array $config = []): array
    {
        // Remove Table of Contents config if it is not enabled
        if (!$this->isTocEnable) {
            $config['table_of_contents']['placeholder'] = '';
        }

        // Set internal hosts for external link config
        if (!empty($this->internalHosts)) {
            $config['external_link']['internal_hosts'] = explode(',', $this->internalHosts);
        }

        // Check if LaTeX is needed by searching for $$ or $ in the text
        if (!$this->isNeedLaTex) {
            $this->isNeedLaTex = (bool) preg_match('/\${1,2}[^`]*?\${1,2}/m', $text);
        }

        // Replace double $$ at the beginning and end of the text with <div> tags
        $count             = 0;
        $text              = preg_replace('/(^\${2,})(.*)(\${2,}$)/ms', '<div>$1$2$3</div>', $text, -1, $count);
        $this->isNeedLaTex = $this->isNeedLaTex || $count > 0;

        return [$text, $config];
    }

    /**
     * Placeholder function for actions to be performed after parsing
     *
     * @param string $htmlContent The parsed HTML content
     * @param array $config Optional configuration for the parsing process
     * @return array Result of actions after parsing
     */
    public function postParse(string $htmlContent, array $config = []): array
    {
        // If LaTeX is needed, remove <div> tags added during preParse
        if ($this->isNeedLaTex) {
            $htmlContent = str_replace(['<div>$$', '$$</div>'], '$$', $htmlContent);
        }

        return [$htmlContent, $config];
    }

    /**
     * Get the default configuration settings
     *
     * @return array The default configuration settings
     */
    public function getConfig(): array
    {
        $instance = $this::getInstance();

        $defaultConfig = [
            'table_of_contents' => [
                'position'    => 'placeholder',
                'placeholder' => '[TOC]',
            ],
            'external_link' => [
                'internal_hosts'     => [],
                'open_in_new_window' => true,
            ],
            'default_attributes' => [
                FencedCode::class => [
                    'class' => static function (FencedCode $node) use ($instance) {
                        $infoWords = $node->getInfoWords();
                        if (\count($infoWords) !== 0 && $infoWords[0] === 'mermaid') {
                            $instance->setIsNeedMermaid(true);
                            return 'mermaid';
                        }
                        return null;
                    },
                ]
            ]
        ];

        return $defaultConfig;
    }

    /**
     * Add CommonMark extensions to the given environment
     *
     * @param Environment $environment The CommonMark environment
     */
    private function addCommonMarkExtensions(Environment $environment): void
    {
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new DisallowedRawHtmlExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new DescriptionListExtension());
        $environment->addExtension(new MarkExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $environment->addExtension(new TableOfContentsExtension());
        $environment->addExtension(new LazyImageExtension());
    }

    /**
     * Get the flag indicating if Table of Contents (TOC) is enabled
     *
     * @return bool The flag indicating if TOC is enabled
     */
    public function getIsTocEnable(): bool
    {
        return $this->isTocEnable;
    }

    /**
     * Set the flag indicating if Table of Contents (TOC) should be enabled
     *
     * @param bool $isTocEnable The flag indicating if TOC should be enabled
     */
    public function setIsTocEnable(bool $isTocEnable): void
    {
        $this->isTocEnable = $isTocEnable;
    }

    /**
     * Get the flag indicating if Mermaid support is needed
     *
     * @return bool The flag indicating if Mermaid support is needed
     */
    public function getIsNeedMermaid(): bool
    {
        return $this->isNeedMermaid;
    }

    /**
     * Set the flag indicating if Mermaid support is needed
     *
     * @param bool $isNeedMermaid The flag indicating if Mermaid support is needed
     */
    public function setIsNeedMermaid(bool $isNeedMermaid): void
    {
        $this->isNeedMermaid = $isNeedMermaid;
    }

    /**
     * Get the flag indicating if LaTex support is needed
     *
     * @return bool The flag indicating if LaTex support is needed
     */
    public function getIsNeedLaTex(): bool
    {
        return $this->isNeedLaTex;
    }

    /**
     * Set the flag indicating if LaTex support is needed
     *
     * @param bool $isNeedLaTex The flag indicating if LaTex support is needed
     */
    public function setIsNeedLaTex(bool $isNeedLaTex): void
    {
        $this->isNeedLaTex = $isNeedLaTex;
    }

    /**
     * Get the internal hosts value
     *
     * @return string The internal hosts value
     */
    public function getInternalHosts(): string
    {
        return $this->internalHosts;
    }

    /**
     * Set the internal hosts value
     *
     * @param string $internalHosts The internal hosts value
     */
    public function setInternalHosts(string $internalHosts): void
    {
        $this->internalHosts = $internalHosts;
    }
}

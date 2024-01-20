<?php

namespace TypechoPlugin\MarkdownParse;

require __DIR__ . '/vendor/autoload.php';

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
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
use Samwilson\CommonMarkLatex\LatexRendererExtension;

/**
 * 更快、更全的 Markdown 解析插件
 *
 * @author  mrgeneral
 * @package MarkdownParse
 * @version 2.0.0
 * @link    https://www.chengxiaobai.cn
 */
class Plugin implements PluginInterface
{
    const RADIO_VALUE_DISABLE = 0;
    const RADIO_VALUE_AUTO    = 1;
    const RADIO_VALUE_FORCE   = 2;

    const CDN_SOURCE_DEFAULT = 'jsDelivr';
    const CDN_SOURCE_MERMAID = [
        'jsDelivr'  => 'https://cdn.jsdelivr.net/npm/mermaid@9/dist/mermaid.min.js',
        'cdnjs'     => 'https://cdnjs.cloudflare.com/ajax/libs/mermaid/9.1.1/mermaid.min.js',
        'bytedance' => 'https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-y/mermaid/8.14.0/mermaid.min.js',
        'baomitu'   => 'https://lib.baomitu.com/mermaid/latest/mermaid.min.js',
        'bootcdn'   => 'https://cdn.bootcdn.net/ajax/libs/mermaid/9.1.1/mermaid.min.js'
    ];
    const CDN_SOURCE_MATHJAX = [
        'jsDelivr'  => 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.min.js',
        'cdnjs'     => 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.0/es5/tex-mml-chtml.min.js',
        'bytedance' => 'https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-y/mathjax/3.2.0/es5/tex-mml-chtml.min.js',
        'baomitu'   => 'https://lib.baomitu.com/mathjax/latest/es5/tex-mml-chtml.min.js',
        'bootcdn'   => 'https://cdn.bootcdn.net/ajax/libs/mathjax/3.2.0/es5/tex-mml-chtml.min.js'
    ];

    public static function activate()
    {
        \Typecho\Plugin::factory('\Widget\Base\Contents')->markdown = [__CLASS__, 'parse'];
        \Typecho\Plugin::factory('\Widget\Base\Comments')->markdown = [__CLASS__, 'parse'];
    }

    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    public static function config(Form $form)
    {
        $elementToc = new Form\Element\Radio('is_available_toc', [self::RADIO_VALUE_DISABLE => _t('不解析'), self::RADIO_VALUE_AUTO => _t('解析')], self::RADIO_VALUE_AUTO, _t('是否解析 [TOC] 语法（符合 HTML 规范，无需 JS 支持）'), _t('开会后支持 [TOC] 语法来生成目录'));
        $form->addInput($elementToc);

        $elementMermaid = new Form\Element\Radio('is_available_mermaid', [self::RADIO_VALUE_DISABLE => _t('不开启'), self::RADIO_VALUE_AUTO => _t('开启（按需加载）'), self::RADIO_VALUE_FORCE => _t('开启（每次加载，pjax 主题建议选择此选项）')], self::RADIO_VALUE_AUTO, _t('是否开启 Mermaid 支持（支持自动识别，按需渲染，无需担心引入冗余资源）'), _t('开启后支持解析并渲染 <a href="https://mermaid-js.github.io/mermaid/#/">Mermaid</a>'));
        $form->addInput($elementMermaid);

        $elementMathJax = new Form\Element\Radio('is_available_mathjax', [self::RADIO_VALUE_DISABLE => _t('不开启'), self::RADIO_VALUE_AUTO => _t('开启（按需加载）'), self::RADIO_VALUE_FORCE => _t('开启（每次加载，pjax 主题建议选择此选项）')], self::RADIO_VALUE_AUTO, _t('是否开启 MathJax 支持（支持自动识别，按需渲染，无需担心引入冗余资源）'), _t('开启后支持解析并渲染 <a href="https://www.mathjax.org/">MathJax</a>'));
        $form->addInput($elementMathJax);

        $elementCDNSource = new Form\Element\Radio('cdn_source', array_combine(array_keys(self::CDN_SOURCE_MERMAID), array_map('_t', array_keys(self::CDN_SOURCE_MERMAID))), self::CDN_SOURCE_DEFAULT);
        $form->addInput($elementCDNSource);

        $elementHelper = new Form\Element\Radio('show_help_info', [], self::RADIO_VALUE_DISABLE, _t('<a href="https://www.chengxiaobai.cn/php/markdown-parser-library.html/">点击查看更新信息</a>'), _t('<a href="https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html/">点击查看语法手册</a>'));
        $form->addInput($elementHelper);
    }

    public static function personalConfig(Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        $config = [
            // @todo config, enable or not
            'table_of_contents' => [
                'position' => 'placeholder',
                'placeholder' => '[TOC]',
            ],
            'external_link' => [
                // @todo config, default from typecho config
                'internal_hosts' => ['foo.example.com', 'bar.example.com', '/(^|\.)google\.com$/'],
                'open_in_new_window' => true,
            ],
            'mark' => [
                // @todo config, enable or not
                'character' => ':',
            ],
        ];
        $environment = new Environment($config);

        // base
        $environment->addExtension(new CommonMarkCoreExtension());

        // core
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new DisallowedRawHtmlExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new FootnoteExtension());

        // extension
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());
        $environment->addExtension(new DescriptionListExtension());
        $environment->addExtension(new LatexRendererExtension());
        $environment->addExtension(new MarkExtension());


        $markdownParser = new MarkdownConverter($environment);
        return $markdownParser->convert($text)->getContent();
    }
}

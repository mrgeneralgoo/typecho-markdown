<?php

namespace TypechoPlugin\MarkdownParse;

require_once 'phar://' . __DIR__ . '/vendor.phar/MarkdownParse.php';

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Options;

/**
 * 符合 CommonMark 和 GFM（GitHub-Flavored Markdown）规范的 Markdown 解析插件，强大而丰富的功能助你在不同平台上展现一致的出色
 *
 * @author  mrgeneral
 * @package MarkdownParse
 * @version 2.5.0
 * @link    https://www.chengxiaobai.cn/
 */
class Plugin implements PluginInterface
{
    const RADIO_VALUE_DISABLE = 0;
    const RADIO_VALUE_AUTO    = 1;
    const RADIO_VALUE_FORCE   = 2;

    const CDN_SOURCE_DEFAULT = 'baomitu';
    const CDN_SOURCE_MERMAID = [
        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.esm.min.mjs',
        'cdnjs'    => 'https://cdnjs.cloudflare.com/ajax/libs/mermaid/10.7.0/mermaid.esm.min.mjs',
        'baomitu'  => 'https://lib.baomitu.com/mermaid/10.7.0/mermaid.esm.min.mjs'
    ];
    const CDN_SOURCE_MATHJAX = [
        'jsDelivr' => 'https://cdn.jsdelivr.net/npm/mathjax/es5/tex-mml-chtml.min.js',
        'cdnjs'    => 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-mml-chtml.min.js',
        'baomitu'  => 'https://lib.baomitu.com/mathjax/latest/es5/tex-mml-chtml.min.js'
    ];

    public static function activate()
    {
        \Typecho\Plugin::factory('\Widget\Base\Contents')->markdown = [__CLASS__, 'parse'];
        \Typecho\Plugin::factory('\Widget\Base\Comments')->markdown = [__CLASS__, 'parse'];
        \Typecho\Plugin::factory('Widget_Archive')->footer          = [__CLASS__, 'resourceLink'];
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

        $elementMermaidTheme = new Form\Element\Radio('mermaid_theme', ['default' => _t('默认（default）'), 'neutral' => _t('墨水（neutral）'), 'dark' => _t('暗黑（dark）'), 'forest' => _t('森林绿（forest）')], 'default', _t('Mermaid 主题颜色'), _t('可以去这里 <a href="https://mermaid.live/edit">实时编辑器</a>调整主题配置看下效果'));
        $form->addInput($elementMermaidTheme);

        $elementMathJax = new Form\Element\Radio('is_available_mathjax', [self::RADIO_VALUE_DISABLE => _t('不开启'), self::RADIO_VALUE_AUTO => _t('开启（按需加载）'), self::RADIO_VALUE_FORCE => _t('开启（每次加载，pjax 主题建议选择此选项）')], self::RADIO_VALUE_AUTO, _t('是否开启 MathJax 支持（支持自动识别，按需渲染，无需担心引入冗余资源）'), _t('开启后支持解析并渲染 <a href="https://www.mathjax.org/">MathJax</a>'));
        $form->addInput($elementMathJax);

        $elementCDNSource = new Form\Element\Radio('cdn_source', array_combine(array_keys(self::CDN_SOURCE_MERMAID), array_map('_t', array_keys(self::CDN_SOURCE_MERMAID))), self::CDN_SOURCE_DEFAULT, _t('静态资源 CDN'), _t('jsDelivr 默认使用最新版本'));
        $form->addInput($elementCDNSource);

        $elementInternalHosts = new Form\Element\Text('internal_hosts', null, '', _t('设置内部链接'), _t('默认为本站点地址，支持正则表达式("/(^|\.)example\.com$/")，多个可用英文逗号分隔。<br/>外部链接解析策略：默认在新窗口中打开，并加上 "noopener noreferrer" 属性'));
        $form->addInput($elementInternalHosts);

        $elementHelper = new Form\Element\Radio('show_help_info', [], self::RADIO_VALUE_DISABLE, _t('<a href="https://www.chengxiaobai.cn/php/markdown-parser-library.html/">点击查看更新信息</a>'), _t('<a href="https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html/">点击查看语法手册</a>'));
        $form->addInput($elementHelper);
    }

    public static function personalConfig(Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        $markdownParser = MarkdownParse::getInstance();

        $markdownParser->setIsTocEnable((bool)Options::alloc()->plugin('MarkdownParse')->is_available_toc);
        $markdownParser->setInternalHosts((string)Options::alloc()->plugin('MarkdownParse')->internal_hosts ?: parse_url(Options::alloc()->siteUrl, PHP_URL_HOST));

        return $markdownParser->parse($text);
    }

    public static function resourceLink()
    {
        $markdownParser     = MarkdownParse::getInstance();
        $configMermaid      = (int)Options::alloc()->plugin('MarkdownParse')->is_available_mermaid;
        $configLaTex        = (int)Options::alloc()->plugin('MarkdownParse')->is_available_mathjax;
        $configCDN          = (string)Options::alloc()->plugin('MarkdownParse')->cdn_source;
        $isAvailableMermaid = $configMermaid === self::RADIO_VALUE_FORCE || ($markdownParser->getIsNeedMermaid() && $configMermaid === self::RADIO_VALUE_AUTO);
        $isAvailableMathjax = $configLaTex   === self::RADIO_VALUE_FORCE || ($markdownParser->getIsNeedLaTex() && $configLaTex === self::RADIO_VALUE_AUTO);

        $resourceContent  = '';

        if ($isAvailableMermaid) {
            $resourceContent .= sprintf('<script type="module">import mermaid from "%s";',self::CDN_SOURCE_MERMAID[$configCDN] ?: self::CDN_SOURCE_MERMAID[self::CDN_SOURCE_DEFAULT]);
            $resourceContent .= sprintf('mermaid.initialize({ startOnLoad: true,theme:"%s"});</script>', (string)Options::alloc()->plugin('MarkdownParse')->mermaid_theme ?: 'default');
        }

        if ($isAvailableMathjax) {
            $resourceContent .= '<script type="text/javascript">(function(){MathJax={loader: {load: [\'[tex]/gensymb\']},tex:{inlineMath:[[\'$\',\'$\'],[\'\\\\(\',\'\\\\)\']],packages: {\'[+]\': [\'gensymb\']}}}})();</script>';
            $resourceContent .= '<script defer src="https://polyfill.alicdn.com/v3/polyfill.min.js?features=es6"></script>';
            $resourceContent .= sprintf('<script id="MathJax-script" defer type="text/javascript" src="%s"></script>', self::CDN_SOURCE_MATHJAX[$configCDN] ?: self::CDN_SOURCE_MATHJAX[self::CDN_SOURCE_DEFAULT]);
        }

        echo $resourceContent;
    }
}

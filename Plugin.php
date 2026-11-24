<?php

namespace TypechoPlugin\MarkdownParse;

if (file_exists(__DIR__ . '/vendor.phar')) {
    require_once 'phar://' . __DIR__ . '/vendor.phar/MarkdownParse.php';
}

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Widget\Options;

/**
 * 符合 CommonMark 和 GFM（GitHub-Flavored Markdown）规范的 Markdown 解析插件，强大而丰富的功能助你在不同平台上展现一致的出色
 *
 * @author  mrgeneral
 * @package MarkdownParse
 * @version 2.7.14
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
    const DEFAULT_MERMAID_DARK_SELECTOR = '[data-theme="dark"], [data-color-mode="dark"], html.dark, body.dark';

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

        $elementMermaidTheme = new Form\Element\Radio('mermaid_theme', ['auto' => _t('自动（跟随站点明暗）'), 'default' => _t('默认（default）'), 'neutral' => _t('墨水（neutral）'), 'dark' => _t('暗黑（dark）'), 'forest' => _t('森林绿（forest）')], 'auto', _t('Mermaid 主题颜色'), _t('“自动”在深色模式下使用 dark 主题、浅色模式下使用 default 主题；可以去这里 <a href="https://mermaid.live/edit">实时编辑器</a>调整主题配置看下效果'));
        $form->addInput($elementMermaidTheme);

        $elementMermaidDarkSelector = new Form\Element\Text('mermaid_dark_selector', null, '', _t('深色模式选择器'), _t('仅“自动”主题生效。任一元素匹配该 CSS 选择器即视为深色模式，支持逗号分隔多个选择器；留空使用默认值 <code>' . self::DEFAULT_MERMAID_DARK_SELECTOR . '</code>'));
        $form->addInput($elementMermaidDarkSelector);

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
            $mermaidTheme  = (string)Options::alloc()->plugin('MarkdownParse')->mermaid_theme ?: 'auto';
            $darkSelector  = trim((string)Options::alloc()->plugin('MarkdownParse')->mermaid_dark_selector)
                ?: self::DEFAULT_MERMAID_DARK_SELECTOR;
            $mermaidConfig = json_encode([
                'auto'         => $mermaidTheme === 'auto',
                'lightTheme'   => $mermaidTheme === 'auto' ? 'default' : $mermaidTheme,
                'darkSelector' => $darkSelector,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // “自动”主题下，站点切换明暗模式时还原源码并按新主题重绘，避免深色页面出现深色文字。
            $mermaidInit = <<<'JS'
const mermaidConfig = CONFIG;
const isDarkMode = () => {
  try {
    return document.documentElement.matches(mermaidConfig.darkSelector)
      || Boolean(document.querySelector(mermaidConfig.darkSelector));
  } catch (e) {
    return false;
  }
};
const currentTheme = () => (mermaidConfig.auto && isDarkMode()) ? 'dark' : mermaidConfig.lightTheme;
let renderedTheme = null;
let renderSeq = 0;
async function renderMermaid(force) {
  const theme = currentTheme();
  if (!force && theme === renderedTheme) return;
  renderedTheme = theme;
  const seq = ++renderSeq;
  const nodes = Array.from(document.querySelectorAll('.mermaid'));
  if (!nodes.length) return;
  nodes.forEach((el) => {
    if (!el.dataset.mermaidSource) el.dataset.mermaidSource = el.textContent;
    el.removeAttribute('data-processed');
    el.textContent = el.dataset.mermaidSource;
  });
  mermaid.initialize({ startOnLoad: false, theme });
  for (const el of nodes) {
    if (seq !== renderSeq) return;
    try { await mermaid.run({ nodes: [el], suppressErrors: true }); } catch (e) { }
  }
}
renderMermaid(true);
if (mermaidConfig.auto) {
  const observer = new MutationObserver(() => renderMermaid(false));
  const options = { attributes: true, attributeFilter: ['class', 'data-theme', 'data-color-mode', 'data-mode'] };
  observer.observe(document.documentElement, options);
  if (document.body) observer.observe(document.body, options);
}
JS;

            $resourceContent .= sprintf(
                '<script type="module">import mermaid from "%s";%s</script>',
                self::CDN_SOURCE_MERMAID[$configCDN] ?: self::CDN_SOURCE_MERMAID[self::CDN_SOURCE_DEFAULT],
                str_replace('CONFIG', $mermaidConfig, $mermaidInit)
            );
        }

        if ($isAvailableMathjax) {
            $resourceContent .= '<script type="text/javascript">(function(){MathJax={loader: {load: [\'[tex]/gensymb\']},tex:{inlineMath:[[\'$\',\'$\'],[\'\\\\(\',\'\\\\)\']],packages: {\'[+]\': [\'gensymb\']}}}})();</script>';
            $resourceContent .= '<script defer src="https://polyfill.alicdn.com/v3/polyfill.min.js?features=es6"></script>';
            $resourceContent .= sprintf('<script id="MathJax-script" defer type="text/javascript" src="%s"></script>', self::CDN_SOURCE_MATHJAX[$configCDN] ?: self::CDN_SOURCE_MATHJAX[self::CDN_SOURCE_DEFAULT]);
        }

        echo $resourceContent;
    }
}

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
    const MERMAID_THEME_DEFAULT      = 'default';
    const MERMAID_DARK_THEME_DEFAULT = 'dark';
    const MERMAID_DARK_THEME_DISABLE = 'none';
    const MERMAID_THEMES             = [
        'default' => '默认（default）',
        'neutral' => '墨水（neutral）',
        'dark'    => '暗黑（dark）',
        'forest'  => '森林绿（forest）',
    ];
    const DEFAULT_MERMAID_DARK_SELECTOR = 'html[data-theme="dark"], body[data-theme="dark"], html[data-color-mode="dark"], body[data-color-mode="dark"], html.dark, body.dark';

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

        $mermaidThemes = array_map('_t', self::MERMAID_THEMES);

        $elementMermaidTheme = new Form\Element\Radio(
            'mermaid_theme',
            $mermaidThemes,
            self::MERMAID_THEME_DEFAULT,
            _t('Mermaid 日间（默认）模式主题'),
            _t('站点日常（或浅色模式）下使用的主题；可以去这里 <a href="https://mermaid.live/edit">实时编辑器</a>调整主题配置看下效果')
        );
        $form->addInput($elementMermaidTheme);

        $elementMermaidDarkTheme = new Form\Element\Radio(
            'mermaid_dark_theme',
            [self::MERMAID_DARK_THEME_DISABLE => _t('不开启（与日间模式保持一致）')] + $mermaidThemes,
            self::MERMAID_DARK_THEME_DEFAULT,
            _t('Mermaid 夜间（深色）模式主题'),
            _t('开启后，站点处于深色模式时自动切换为所选主题')
        );
        $form->addInput($elementMermaidDarkTheme);

        $elementMermaidDarkSelector = new Form\Element\Text(
            'mermaid_dark_selector',
            null,
            '',
            _t('深色模式选择器'),
            _t('仅在开启夜间（深色）模式主题切换时生效。任一元素匹配该 CSS 选择器即视为深色模式，支持逗号分隔多个选择器；留空使用默认值 <code>' . htmlspecialchars(self::DEFAULT_MERMAID_DARK_SELECTOR, ENT_QUOTES, 'UTF-8') . '</code>')
        );
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
        $pluginOptions  = Options::alloc()->plugin('MarkdownParse');

        $markdownParser->setIsTocEnable((bool)($pluginOptions->is_available_toc ?? false));
        $markdownParser->setInternalHosts((string)($pluginOptions->internal_hosts ?? '') ?: parse_url(Options::alloc()->siteUrl, PHP_URL_HOST));

        return $markdownParser->parse($text);
    }

    public static function resourceLink()
    {
        $markdownParser = MarkdownParse::getInstance();
        $pluginOptions  = Options::alloc()->plugin('MarkdownParse');
        $cdnSource      = (string)($pluginOptions->cdn_source ?? '');

        $resourceContent = '';

        if (self::shouldLoadResource((int)($pluginOptions->is_available_mermaid ?? self::RADIO_VALUE_DISABLE), $markdownParser->getIsNeedMermaid())) {
            $mermaidUrl       = self::CDN_SOURCE_MERMAID[$cdnSource] ?? self::CDN_SOURCE_MERMAID[self::CDN_SOURCE_DEFAULT];
            $resourceContent .= self::buildMermaidScript($mermaidUrl, self::resolveMermaidOptions($pluginOptions));
        }

        if (self::shouldLoadResource((int)($pluginOptions->is_available_mathjax ?? self::RADIO_VALUE_DISABLE), $markdownParser->getIsNeedLaTex())) {
            $mathJaxUrl       = self::CDN_SOURCE_MATHJAX[$cdnSource] ?? self::CDN_SOURCE_MATHJAX[self::CDN_SOURCE_DEFAULT];
            $resourceContent .= self::buildMathJaxScript($mathJaxUrl);
        }

        echo $resourceContent;
    }

    private static function shouldLoadResource(int $mode, bool $isNeeded): bool
    {
        return $mode === self::RADIO_VALUE_FORCE
            || ($mode === self::RADIO_VALUE_AUTO && $isNeeded);
    }

    private static function resolveMermaidOptions($pluginOptions): array
    {
        $lightOption = (string)($pluginOptions->mermaid_theme ?? '');
        $lightTheme  = isset(self::MERMAID_THEMES[$lightOption])
            ? $lightOption
            : self::MERMAID_THEME_DEFAULT;

        $darkOption = (string)($pluginOptions->mermaid_dark_theme ?? self::MERMAID_DARK_THEME_DISABLE);
        $darkTheme  = isset(self::MERMAID_THEMES[$darkOption])
            ? $darkOption
            : $lightTheme;

        $darkSelector = trim((string)($pluginOptions->mermaid_dark_selector ?? ''))
            ?: self::DEFAULT_MERMAID_DARK_SELECTOR;

        return [
            'lightTheme'   => $lightTheme,
            'darkTheme'    => $darkTheme,
            'darkSelector' => $darkSelector,
        ];
    }

    private static function buildMermaidScript(string $cdnUrl, array $options): string
    {
        if ($options['lightTheme'] === $options['darkTheme']) {
            return sprintf(
                '<script type="module">import mermaid from "%s";mermaid.initialize({ startOnLoad: true, theme: %s });</script>',
                $cdnUrl,
                json_encode($options['lightTheme'], JSON_HEX_TAG | JSON_HEX_AMP)
            );
        }

        $encodedConfig = json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        $runtimeScript = <<<'JS'
const config = __MERMAID_CONFIG__;
const isDarkMode = () => {
  try {
    return Boolean(document.querySelector(config.darkSelector));
  } catch {
    return false;
  }
};
const resolveTheme = () => isDarkMode() ? config.darkTheme : config.lightTheme;
const readSource = (el) => {
  if (el.dataset.mermaidSource === undefined) {
    if (el.hasAttribute('data-processed') || el.querySelector('svg')) return '';
    el.dataset.mermaidSource = el.textContent || '';
  }
  return el.dataset.mermaidSource.trim() ? el.dataset.mermaidSource : '';
};
let activeTheme = null;
let renderSeq = 0;
async function renderDiagrams(force) {
  const theme = resolveTheme();
  if (!force && theme === activeTheme) return;
  activeTheme = theme;
  const seq = ++renderSeq;
  mermaid.initialize({ startOnLoad: false, theme });
  for (const el of document.querySelectorAll('.mermaid')) {
    if (seq !== renderSeq) return;
    const source = readSource(el);
    if (!source || (el.dataset.mermaidTheme === theme && el.hasAttribute('data-processed'))) continue;
    delete el.dataset.mermaidTheme;
    el.removeAttribute('data-processed');
    el.textContent = source;
    try {
      await mermaid.run({ nodes: [el], suppressErrors: true });
      if (seq === renderSeq) el.dataset.mermaidTheme = theme;
    } catch {}
  }
}
renderDiagrams(true);
document.addEventListener('pjax:complete', () => renderDiagrams(true));
const ignoredAttrs = new Set(['data-processed', 'data-mermaid-source', 'data-mermaid-theme']);
if (!config.darkSelector.includes('style')) ignoredAttrs.add('style');
new MutationObserver((mutations) => {
  if (mutations.some((m) => !ignoredAttrs.has(m.attributeName))) renderDiagrams(false);
}).observe(document.documentElement, { attributes: true, subtree: true });
JS;

        return sprintf(
            '<script type="module">import mermaid from "%s";%s</script>',
            $cdnUrl,
            str_replace('__MERMAID_CONFIG__', $encodedConfig, $runtimeScript)
        );
    }

    private static function buildMathJaxScript(string $cdnUrl): string
    {
        return '<script type="text/javascript">(function(){MathJax={loader: {load: [\'[tex]/gensymb\']},tex:{inlineMath:[[\'$\',\'$\'],[\'\\\\(\',\'\\\\)\']],packages: {\'[+]\': [\'gensymb\']}}}})();</script>'
            . '<script defer src="https://polyfill.alicdn.com/v3/polyfill.min.js?features=es6"></script>'
            . sprintf('<script id="MathJax-script" defer type="text/javascript" src="%s"></script>', $cdnUrl);
    }
}

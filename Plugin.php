<?php

/**
 * 更快、更全的 Markdown 解析插件
 *
 * @author  mrgeneral
 * @package MarkdownParse
 * @version 1.4.3
 * @link    https://www.chengxiaobai.cn
 */

require_once 'ParsedownExtension.php';

class MarkdownParse_Plugin implements Typecho_Plugin_Interface
{
    const RADIO_VALUE_DISABLE = 0;
    const RADIO_VALUE_AUTO    = 1;
    const RADIO_VALUE_FORCE   = 2;

    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->markdown = [__CLASS__, 'parse'];
        Typecho_Plugin::factory('Widget_Abstract_Comments')->markdown = [__CLASS__, 'parse'];
        Typecho_Plugin::factory('Widget_Archive')->footer             = [__CLASS__, 'resourceLink'];
    }

    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $elementToc = new Typecho_Widget_Helper_Form_Element_Radio('is_available_toc', [self::RADIO_VALUE_DISABLE => _t('不解析'), self::RADIO_VALUE_AUTO => _t('解析')], self::RADIO_VALUE_AUTO, _t('是否解析 [TOC] 语法（符合 HTML 规范，无需 JS 支持）'), _t('开会后支持 [TOC] 语法来生成目录'));
        $form->addInput($elementToc);

        $elementMermaid = new Typecho_Widget_Helper_Form_Element_Radio('is_available_mermaid', [self::RADIO_VALUE_DISABLE => _t('不开启'), self::RADIO_VALUE_AUTO => _t('开启（按需加载）'), self::RADIO_VALUE_FORCE => _t('开启（每次加载，pjax 主题建议选择此选项）')], self::RADIO_VALUE_AUTO, _t('是否开启 Mermaid 支持（支持自动识别，按需渲染，无需担心引入冗余资源）'), _t('开启后支持解析并渲染 <a href="https://mermaid-js.github.io/mermaid/#/">Mermaid</a>'));
        $form->addInput($elementMermaid);

        $elementMathJax = new Typecho_Widget_Helper_Form_Element_Radio('is_available_mathjax', [self::RADIO_VALUE_DISABLE => _t('不开启'), self::RADIO_VALUE_AUTO => _t('开启（按需加载）'), self::RADIO_VALUE_FORCE => _t('开启（每次加载，pjax 主题建议选择此选项）')], self::RADIO_VALUE_AUTO, _t('是否开启 MathJax 支持（支持自动识别，按需渲染，无需担心引入冗余资源）'), _t('开启后支持解析并渲染 <a href="https://www.mathjax.org/">MathJax</a>'));
        $form->addInput($elementMathJax);

        $elementHelper = new Typecho_Widget_Helper_Form_Element_Radio('show_help_info', [], self::RADIO_VALUE_DISABLE, _t('<a href="https://www.chengxiaobai.cn/php/markdown-parser-library.html/">点击查看更新信息</a>'), _t('<a href="https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html/">点击查看语法手册</a>'));
        $form->addInput($elementHelper);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        $markdownParser              = ParsedownExtension::instance();
        $markdownParser->isTocEnable = (bool)Helper::options()->plugin('MarkdownParse')->is_available_toc;

        return $markdownParser->setBreaksEnabled(true)->text($text);
    }

    public static function resourceLink()
    {
        $configMermaid      = (int)Helper::options()->plugin('MarkdownParse')->is_available_mermaid;
        $configLaTex        = (int)Helper::options()->plugin('MarkdownParse')->is_available_mathjax;
        $markdownParser     = ParsedownExtension::instance();
        $isAvailableMermaid = $configMermaid === self::RADIO_VALUE_FORCE || ($markdownParser->isNeedMermaid && $configMermaid === self::RADIO_VALUE_AUTO);
        $isAvailableMathjax = $configLaTex === self::RADIO_VALUE_FORCE || ($markdownParser->isNeedLaTex && $configLaTex === self::RADIO_VALUE_AUTO);

        $resourceContent = '';

        if ($isAvailableMermaid) {
            $resourceContent .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mermaid/8.14.0/mermaid.min.js"></script>';
            $resourceContent .= '<script type="text/javascript">(function(){mermaid.initialize({startOnLoad:true})})();</script>';
        }

        if ($isAvailableMathjax) {
            $resourceContent .= '<script type="text/javascript">(function(){MathJax={tex:{inlineMath:[[\'$\',\'$\'],[\'\\\\(\',\'\\\\)\']]}}})();</script>';
            $resourceContent .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.0/es5/tex-mml-chtml.min.js"></script>';
        }

        echo $resourceContent;
    }
}

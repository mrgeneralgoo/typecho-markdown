<?php

/**
 * 更快、更全的 Markdown 解析插件
 *
 * @package MarkdownParse
 * @author  mrgeneral
 * @version 1.3.0
 * @link    https://www.chengxiaobai.cn
 */

require_once 'ParsedownExtension.php';

class MarkdownParse_Plugin implements Typecho_Plugin_Interface
{
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
        $elementToc = new Typecho_Widget_Helper_Form_Element_Radio('is_available_toc', [0 => _t('不解析'), 1 => _t('解析')], 1, _t('是否解析 [TOC] 语法'), _t('开会后支持 [TOC] 语法来生成目录'));
        $form->addInput($elementToc);

        $elementMermaid = new Typecho_Widget_Helper_Form_Element_Radio('is_available_mermaid', [0 => _t('不开启'), 1 => _t('开启')], 1, _t('是否开启 Mermaid 支持'), _t('开启后支持解析并渲染 <a href="https://mermaid-js.github.io/mermaid/#/">Mermaid</a>'));
        $form->addInput($elementMermaid);

        $elementMathJax = new Typecho_Widget_Helper_Form_Element_Radio('is_available_mathjax', [0 => _t('不开启'), 1 => _t('开启')], 1, _t('是否开启 MathJax 支持'), _t('开启后支持解析并渲染 <a href="https://www.mathjax.org/">MathJax</a>'));
        $form->addInput($elementMathJax);

        $elementHelper = new Typecho_Widget_Helper_Form_Element_Radio('show_help_info', [], 0, _t('<a href="https://www.chengxiaobai.cn/php/markdown-parser-library.html/">点击查看更新信息</a>'), _t('<a href="https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html/">点击查看语法手册</a>'));
        $form->addInput($elementHelper);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        return ParsedownExtension::instance()
            ->setBreaksEnabled(true)
            ->setTocEnabled((bool)Helper::options()->plugin('MarkdownParse')->is_available_toc)
            ->text($text);
    }

    public static function resourceLink()
    {
        $resourceContent = '';

        if ((bool)Helper::options()->plugin('MarkdownParse')->is_available_mermaid) {
            $resourceContent .= '<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/mermaid@8/dist/mermaid.min.js"></script>';
            $resourceContent .= '<script type="text/javascript">(function(){mermaid.initialize({startOnLoad:true})})();</script>';
        }

        if ((bool)Helper::options()->plugin('MarkdownParse')->is_available_mathjax) {
            $resourceContent .= '<script type="text/javascript">(function(){MathJax={tex:{inlineMath:[[\'$\',\'$\'],[\'\\\\(\',\'\\\\)\']]}}})();</script>';
            $resourceContent .= '<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.min.js"></script>';
        }

        echo $resourceContent;
    }
}

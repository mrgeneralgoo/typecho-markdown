<?php

/**
 * 更快、更强的 Markdown 解析插件
 *
 * @package MarkdownParse
 * @author  mrgeneral
 * @version 1.0.0
 * @link    https://www.chengxiaobai.cn
 */
class MarkdownParse_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->markdown = ['MarkdownParse_Plugin', 'parse'];
        Typecho_Plugin::factory('Widget_Abstract_Comments')->markdown = ['MarkdownParse_Plugin', 'parse'];
    }

    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement config() method.
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        require_once dirname(__FILE__) . '/ParsedownExtra.php';

        $content = ParsedownExtra::instance()->setBreaksEnabled(true)->text($text);

        return preg_match('/^<p> *\[TOC\]\s*<\/p>$/m', $content) ? self::buildToc($content) : $content;
    }

    public static function buildToc($content)
    {
        $document  = new \DOMDocument();
        $htmlStart = '<!DOCTYPE html><html><head><meta charset="UTF-8" /></head><body>';
        $htmlEnd   = '</body></html>';
        $document->loadHTML($htmlStart . $content . $htmlEnd);

        $xpath    = new \DOMXPath($document);
        $elements = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');

        if ($elements->length === 0) {
            return $content;
        }

        $tocContent   = '';
        $lastPosition = 0;

        foreach ($elements as $element) {
            sscanf($element->tagName, 'h%d', $currentPosition);

            if ($currentPosition > $lastPosition) {
                // parents start
                $tocContent .= '<ul>' . PHP_EOL;
            } elseif ($currentPosition < $lastPosition) {
                // Must have brother if style of title is right
                // brother's grandchild end
                // brother's child end
                // brother end
                $tocContent .= '</li></ul></li>' . PHP_EOL;
            } else {
                // brother end
                $tocContent .= '</li>' . PHP_EOL;
            }

            if ($element->hasAttribute('id')) {
                $id = $element->getAttribute('id');
            } else {
                $id = md5($element->textContent);
                $element->setAttribute('id', $id);
            }

            // child start
            $tocContent   .= '<li><a href="#' . $id . '">' . $element->textContent . '</a>' . PHP_EOL;
            $lastPosition = $currentPosition;
        }
        // child end and parents end
        $tocContent .= '</li></ul>';

        return preg_replace('/^<p> *\[TOC\]\s*<\/p>$/m', $tocContent, $document->saveHTML());
    }
}

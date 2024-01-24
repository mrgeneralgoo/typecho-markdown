Markdown Plugin for Typecho
=========================

MarkdownParse 是一款基于 [league/commonmark](https://commonmark.thephpleague.com) 的 Typecho Markdown 解析插件，它的特色在于完美符合 [CommonMark](https://spec.commonmark.org) 和 GFM（[GitHub-Flavored Markdown](https://github.github.com/gfm/)）规范，不仅可以为你提供强大而丰富的功能，同时也能确保你的内容在不同平台上都能展现一致的出色效果。

本插件除了支持 CommonMark 和 GFM 规范内提到的功能（目录、表格、任务列表、脚标等等），MarkdownParse 还具有以下额外特性：

1. **Mermaid 语法支持：** 可以利用 Mermaid 语法轻松创建各种图表
2. **MathJax 数学公式渲染：** 支持使用 MathJax 渲染数学公式
3. **智能资源加载：** 根据实际渲染需求，能够智能识别是否加载渲染所需资源，无需担心引入冗余资源
4. **图片延迟加载：** 支持浏览器原生的图片延迟加载技术，[MDN-Lazy loading](https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading)
5. **文本高亮：** 通过 `<mark>` HTML 标签实现文本高亮效果，[MDN-Mark](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/mark)

## 环境要求

* Typecho 1.2.0 or higher
* PHP 8.0 or higher

## 安装

1. [下载这个插件](https://github.com/mrgeneralgoo/typecho-markdown/releases)
2. 修改文件夹的名字为 "MarkdownParse"
3. 添加到你的项目中并启用它

## 报告问题

[你可以直接点击这里提出你的问题](https://github.com/mrgeneralgoo/typecho-markdown/issues/new)

##  语法示例

https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html

------

MarkdownParse is a Typecho Markdown parsing plugin based on [league/commonmark](https://commonmark.thephpleague.com). Its feature lies in its perfect compliance with [CommonMark](https://spec.commonmark.org) and GFM ([GitHub-Flavored Markdown](https://github.github.com/gfm/)) specifications. It not only provides you with powerful and abundant functions, but also ensures consistent outstanding effects of your content on different platforms.

In addition to the functions mentioned in the CommonMark and GFM specifications (table of contents, tables, task lists, footnotes, etc.), MarkdownParse also has the following additional features:

1. **Mermaid syntax support:** Easily create various charts using Mermaid syntax
2. **MathJax formula rendering:** Supports rendering mathematical formulas using MathJax  
3. **Intelligent resource loading:** According to actual rendering needs, it can intelligently identify whether to load required rendering resources without worrying about introducing redundant resources
4. **Image lazy loading:** Supports native image lazy loading technology in browsers, [MDN-Lazy loading](https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading)
5. **Text highlight:** Realize text highlight effect through `<mark>` HTML tag, [MDN-Mark](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/mark)

## Requirements

* Typecho 1.2.0 or higher
* PHP 8.0 or higher

## Installation 

1. [Download this plugin](https://github.com/mrgeneralgoo/typecho-markdown/releases)  
2. Rename the folder to "MarkdownParse"  
3. Add it to your project and activate it

## Reporting Issues  

[You can click here directly to create an issue](https://github.com/mrgeneralgoo/typecho-markdown/issues/new)  

## Example

https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html

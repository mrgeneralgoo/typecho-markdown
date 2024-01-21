Markdown Plugin for Typecho
=========================

This is a markdown parse plugin. 
It uses [Parsedown](https://github.com/erusev/parsedown) to replace Typecho's own markdown parse library, and supports `[TOC]` syntax to generate table of contents, supports `$` and `$$` syntax for [MathJax](https://www.mathjax.org), supports `mermaid` syntax for [Mermaid](https://mermaid-js.github.io/mermaid/#/). All resources are loaded on demand, doing more with less.

## Installation

1. [Download the plugin](https://github.com/mrgeneralgoo/typecho-markdown/archive/master.zip)
2. Rename the folder name "MarkdownParse"
3. Enable this plugin

### Reporting issues

You can [create an issue](https://github.com/mrgeneralgoo/typecho-markdown/issues/new)

####  Example

https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html

####  Blog

https://www.chengxiaobai.cn/php/markdown-parser-library.html

------

MarkdownParse 是一款基于 league/commonmark 的 Typecho Markdown 解析插件，它的特色在于完美符合 CommonMark 和 GFM（GitHub-Flavored Markdown）规范，为用户提供强大而丰富的功能组，确保在不同平台上展现一致的出色效果。

除了支持 CommonMark 和 GFM 规范内提到的功能，MarkdownParse 还具有以下额外特性：

Mermaid 语法支持： 可以利用 Mermaid 语法轻松创建各种图表，为文章增色不少。
MathJax 数学公式渲染： 支持使用 MathJax 渲染数学公式，使得数学内容更加清晰和专业。
智能资源加载： 根据实际渲染需求，MarkdownParse 能够智能识别是否加载渲染所需资源，无需用户担心引入冗余资源，保障网页加载效率。
图片延迟加载： 使用浏览器原生的图片延迟加载技术，提升页面加载速度，特别适用于图片较多的文章。
文本高亮： 通过 <mark> HTML 标签实现文本高亮效果，使得关键信息更加突出。
MarkdownParse 是一款全面而灵活的 Markdown 解析插件，为 Typecho 用户提供了更多可能性，让你的文章在任何地方都能够以最佳状态呈现。

这是一个 Markdown 解析插件，用 [Parsedown](https://github.com/erusev/parsedown) 替换 Typecho 自带的 Markdown 解析库，并额外支持 `[TOC]` 语法来生成目录，同时支持 [MathJax](https://www.mathjax.org) 来渲染数学公式，也支持 [Mermaid](https://mermaid-js.github.io/mermaid/#/) 生成各种图表。根据实际渲染需求，智能识别是否加载渲染资源，无需再担心引入冗余资源。

## 安装

1. [下载这个插件](https://github.com/mrgeneralgoo/typecho-markdown/archive/master.zip)
2. 修改文件夹的名字为"MarkdownParse"
3. 添加到你的项目中并启用它

### 报告问题

[你可以直接点击这里提出你的问题](https://github.com/mrgeneralgoo/typecho-markdown/issues/new)

####  语法示例

https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html

#### 我的博客

https://www.chengxiaobai.cn/php/markdown-parser-library.html
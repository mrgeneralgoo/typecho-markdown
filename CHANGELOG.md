## v1.4.0 (2021-05-22)

Support to load resources on demand to improve performance

Supported footnote syntax，refer to [markdown-concise-grammar-manual - 13. footnote](https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html#13.+注脚)

## v1.3.1 (2021-05-22)

Fix ([#22](https://github.com/mrgeneralgoo/typecho-markdown/commit/6f56055b7ee3d3e98e04549cbc6c24cc09861a00))

Compatible with PHP8

Support [browser-level image lazy-loading]((https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading))

## v1.3.0 (2020-08-09)

Supported `mermaid` syntax for [Mermaid](https://mermaid-js.github.io/mermaid/#/)

Support Mermaid and MathJax parsing becomes optional, parsing is enabled by default

## v1.2.3 (2020-05-02)

Fix ([#13](https://github.com/mrgeneralgoo/typecho-markdown/issues/13))

## v1.2.2 (2019-06-09)

Support toc parsing becomes optional, parsing is enabled by default

## v1.2.1 (2019-04-07)

Support  original text output when no  `[TOC]` syntax is detected ([#9](https://github.com/mrgeneralgoo/typecho-markdown/issues/9))

## v1.2.0 (2019-03-13)

Supported `$` and `$$` syntax for [MathJax](https://www.mathjax.org)

Added new functions makes it easier to expand its capabilities

## v1.1.1 (2019-02-18)

Fixed duplicate builds table of contents ([#5](https://github.com/mrgeneralgoo/typecho-markdown/issues/5))

## v1.1.0 (2018-08-25)

Optimized  table of contents parsing algorithm

Remove extra code to improve performance

## v1.0.1 (2017-12-26)

Supported `[TOC]` syntax to generate table of contents

## v1.0.0 (2017-12-02)

The first release

------
## v1.4.0 (2021-05-22)

支持按需引入渲染资源，无需再担心引入冗余资源，提升 MathJax 和 Mermaid 页面体验与性能

支持注脚语法，请参考[考语法手册 - 13. 注脚](https://www.chengxiaobai.cn/record/markdown-concise-grammar-manual.html#13.+注脚)

## v1.3.1 (2021-05-22)

修复目录重复导致的目录渲染丢失问题 ([#22](https://github.com/mrgeneralgoo/typecho-markdown/issues/22))

兼容 PHP8

支持[浏览器原生图片懒加载特性](https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading)

## v1.3.0 (2020-08-09)

支持使用 `mermaid` 语法来解析 [Mermaid](https://mermaid-js.github.io/mermaid/#/)

Mermaid and MathJax 解析功能变成可选项，可以在配置面板控制，默认开启。

## v1.2.3 (2020-05-02)

修复 `$$` 和 Markdown 语法共存在一段有时会导致 markdown 不解析问题 ([#13](https://github.com/mrgeneralgoo/typecho-markdown/issues/13))

## v1.2.2 (2019-06-09)

目录解析功能成为可选项，默认开启。

## v1.2.1 (2019-04-07)

支持原文输出，当没有检测到 `[TOC]` 语法的时候不再对标题级别元素添加`id`属性 ([#9](https://github.com/mrgeneralgoo/typecho-markdown/issues/9))

## v1.2.0 (2019-03-13)

支持 [MathJax](https://www.mathjax.org) 的 `$` 和 `$$` 语法来渲染数学公式

新增函数使得拓展其功能更加简单

## v1.1.1 (2019-02-18)

修复多文章列表情况下目录重复问题 ([#5](https://github.com/mrgeneralgoo/typecho-markdown/issues/5))

## v1.1.0 (2018-08-25)

优化目录解析算法，避免乱码情况

精简代码，提升性能

## v1.0.1 (2017-12-26)

支持`[TOC]` 语法生成目录

## v1.0.0 (2017-12-02)

第一版正式版发布。
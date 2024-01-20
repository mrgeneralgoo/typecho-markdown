Samwilson/CommonMarkLatex
=========================

An extension to [League/CommonMark](https://commonmark.thephpleague.com)
for rendering Markdown to LaTeX.

![Packagist Version](https://img.shields.io/packagist/v/samwilson/commonmark-latex)
![Packagist License](https://img.shields.io/packagist/l/samwilson/commonmark-latex)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/samwilson/commonmark-latex/ci.yml?branch=main)

## Installation

Install with [Composer](https://getcomposer.org/):

```
$ composer require samwilson/commonmark-latex
```

## Usage

```php
<?php
$environment = new \League\CommonMark\Environment\Environment();
// Add the core extension.
$environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension\CommonMarkCoreExtension());
// Add the LaTeX extension.
$environment->addExtension(new \Samwilson\CommonMarkLatex\LatexRendererExtension());
$converter = new \League\CommonMark\MarkdownConverter($environment);
$latex = $converter->convert('*Markdown* content goes here!')->getContent());
```

## License

Copyright © 2022 Sam Wilson https://samwilson.id.au/

This program is free software: you can redistribute it and/or modify it under the terms of
the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.
If not, see https://www.gnu.org/licenses/

## Kudos

* The [League/CommonMark](https://commonmark.thephpleague.com/) package (of course!).
* [Simple_shapes_example.png](https://commons.wikimedia.org/wiki/File:Simple_shapes_example.png)
  Test image by User:Scarce is Public Domain.
* [cebe/markdown-latex](https://packagist.org/packages/cebe/markdown-latex) for inspiration and some of the test files.

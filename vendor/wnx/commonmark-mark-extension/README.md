# Parse and Render ==marked text== with league/commonmark

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wnx/commonmark-mark-extension.svg?style=flat-square)](https://packagist.org/packages/wnx/commonmark-mark-extension)
[![Tests](https://github.com/stefanzweifel/commonmark-mark-extension/actions/workflows/run-tests.yml/badge.svg)](https://github.com/stefanzweifel/commonmark-mark-extension/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/stefanzweifel/commonmark-mark-extension/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/stefanzweifel/commonmark-mark-extension/actions/workflows/php-cs-fixer.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wnx/commonmark-mark-extension.svg?style=flat-square)](https://packagist.org/packages/wnx/commonmark-mark-extension)


A [league/commonmark](https://github.com/thephpleague/commonmark) extension to turn highlighted text into [`<mark>`-HTML](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/mark) elements.

For example, the following markdown text …

```md
The ==quick brown fox== jumps over the lazy dog.
```

… is turned into the following HTML.

```html
<p>The <mark>quick brown fox</mark> jumps over the lazy dog.</p>
```

## Installation

You can install the package via composer:

```bash
composer require wnx/commonmark-mark-extension
```

## Usage

Create a custom CommonMark environment, and register the `MarkExtension`.

```php
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Wnx\CommonmarkMarkExtension\MarkExtension;

// Configure the Environment with all the CommonMark parsers/renderers
$environment = new Environment();
$environment->addExtension(new CommonMarkCoreExtension());

// Add this extension
$environment->addExtension(new MarkExtension());

// Instantiate the converter engine and start converting some Markdown!
$converter = new MarkdownConverter($environment);
echo $converter->convertToHtml('The ==quick== brown fox jumps over the ==lazy dog==');
```

If you're using a different character than `=` to highlight text in Markdown, you can pass a `character` configuration to the extension.

```php
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Wnx\CommonmarkMarkExtension\MarkExtension;

// Define your configuration, if needed
$config = [
    'mark' => [
        'character' => ':',
    ],
];

// Configure the Environment with all the CommonMark parsers/renderers
$environment = new Environment($config);
$environment->addExtension(new CommonMarkCoreExtension());

// Add this extension
$environment->addExtension(new MarkExtension());

// Instantiate the converter engine and start converting some Markdown!
$converter = new MarkdownConverter($environment);
echo $converter->convertToHtml('The ::quick:: brown fox jumps over the ::lazy dog::');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Stefan Zweifel](https://github.com/stefanzweifel)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

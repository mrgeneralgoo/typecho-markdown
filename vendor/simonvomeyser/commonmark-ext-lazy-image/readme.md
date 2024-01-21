# Extension to render lazy images in markdown

![Tests](https://github.com/simonvomeyser/commonmark-ext-lazy-image/workflows/Tests/badge.svg)

This adds support for lazy images to the [league/commonmark](https://github.com/thephpleague/commonmark) package version `^2.0`.

## Install

``` bash
composer require simonvomeyser/commonmark-ext-lazy-image
```

<details>
<summary>⚠️ When you are using Version 1.0 of league\commonmark </summary>
  
<br>
<br>
The current version of this pacakge is only compatible with `League\CommonMark 2.0`, for `1.0` compatibility install the latest `1.0` version of this package like so:

``` bash
composer require simonvomeyser/commonmark-ext-lazy-image "^v1.2.0"
```

You can find the old documentation [here](https://github.com/simonvomeyser/commonmark-ext-lazy-image/tree/40fcb3ec18b1c84e21a0b0b635ad021f8ec933bd).
</details>


## Example

```php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use SimonVomEyser\CommonMarkExtension\LazyImageExtension;

$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension())
            ->addExtension(new LazyImageExtension());

$converter = new MarkdownConverter($environment);
$html = $converter->convert('![alt text](/path/to/image.jpg)');
```

This creates the following HTML

```html
<img src="/path/to/image.jpg" alt="alt text" loading="lazy" />
```

## Options/Configuration

By default, only the `loading="lazy"` attribute is added

While this should hopefully be sufficient [in the future](https://web.dev/native-lazy-loading/), you can use the provided options to integrate with various lazy loading libraries.

Here is an example how to use this package with the [lozad library](https://github.com/ApoorvSaxena/lozad.js):

```php
$environment = new Environment([
    // ... other config
    'lazy_image' => [
        'strip_src' => true, // remove the "src" to add it later via js, optional
        'html_class' => 'lozad', // the class that should be added, optional
        'data_attribute' => 'src', // how the data attribute is named that provides the source to get picked up by js, optional
    ]
]);
$environment->addExtension(new CommonMarkCoreExtension())
    ->addExtension(new LazyImageExtension());

$converter = new MarkdownConverter($environment);

$html = $converter->convert('![alt text](/path/to/image.jpg)');
```


This creates the following HTML

```html
<img src="" alt="alt text" loading="lazy" data-src="/path/to/image.jpg" class="lozad" />
```

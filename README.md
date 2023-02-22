# Laravel Mail CSS Inliner

[![Latest Version](https://img.shields.io/github/release/stayallive/laravel-mail-css-inliner.svg?style=flat-square)](https://github.com/stayallive/laravel-mail-css-inliner/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/stayallive/laravel-mail-css-inliner/ci.yaml?branch=master&style=flat-square)](https://github.com/stayallive/laravel-mail-css-inliner/actions/workflows/ci.yaml)
[![Total Downloads](https://img.shields.io/packagist/dt/stayallive/laravel-mail-css-inliner.svg?style=flat-square)](https://packagist.org/packages/stayallive/laravel-mail-css-inliner)

## Why?

Most email clients won't render CSS (on a `<link>` or a `<style>`). The solution is inline your CSS directly on the HTML. 
Doing this by hand easily turns into unmaintainable templates. The goal of this package is to automate the process of inlining that CSS before sending the emails.

## How?

Using a wonderful [CSS inliner package](https://github.com/tijsverkoyen/CssToInlineStyles) wrapped in a SwiftMailer plugin and served as a Service Provider it just works without any configuration.
Since this is a SwiftMailer plugin, it will automatically inline your css when parsing an email template. You don't have to do anything!

Turns style tag:
```html
<html>
    <head>
        <style>
            h1 {
                font-size: 24px;
                color: #000;
            }
        </style>
    </head>
    <body>
        <h1>Hey you</h1>
    </body>
</html>
```

Or the link tag:
```html
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="./tests/css/test.css">
    </head>
    <body>
        <h1>Hey you</h1>
    </body>
</html>
```

Into this:
```html
<html>
    <head>
        <style>
            h1 {
                font-size: 24px;
                color: #000;
            }
        </style>
    </head>
    <body>
        <h1 style="font-size: 24px; color: #000;">Hey you</h1>
    </body>
</html>
```

## Installation

This package requires at least Laravel `9` and PHP `8`.

Begin by installing this package through composer. Require it directly from the CLI to take the last stable version:
```bash
composer require stayallive/laravel-mail-css-inliner
```

At this point the inliner should be already working with the default options. If you want to fine-tune these options, you can do so by publishing the configuration file:
```bash
$ php artisan vendor:publish --provider='Stayallive\LaravelMailCssInliner\ServiceProvider'
```
and changing the settings on the generated `config/mail-css-inliner.php` file.

### Testing

``` bash
composer test
```

## Found a bug?

Please, let me know! Send a pull request or a patch. Questions? Ask! I will respond to all filed issues.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email me@alexbouma.me instead of using the issue tracker.

## Credits

This is a fork from [fedeisas/laravel-mail-css-inliner](https://github.com/fedeisas/laravel-mail-css-inliner).

The forked package is greatly inspired, and mostly copied, from [SwiftMailer CSS Inliner](https://github.com/OpenBuildings/swiftmailer-css-inliner).

- [Alex Bouma](https://github.com/stayallive)
- [All Contributors](../../contributors)
- [All Contributors from fedeisas/laravel-mail-css-inliner](https://github.com/fedeisas/laravel-mail-css-inliner/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

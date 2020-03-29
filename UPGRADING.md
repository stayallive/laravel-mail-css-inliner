# Upgrading

## From `2.0` or lower (fork) to `3.0`

If you are coming from [fedeisas/laravel-mail-css-inliner](https://github.com/fedeisas/laravel-mail-css-inliner) v2 or lower there are not many changes needed.

- If you previously added the service provider to your `conig/app.php` you can remove that since Laravel 7 will autodiscover the service provider
- The config file renamed from `config/css-inliner.php` to `config/mail-css-inliner.php`
- The `css-files` key in the config file renamed to `inline_css_files`
- If you manually used code the namespace changed from `Fedeisas\LaravelMailCssInliner` to `Stayallive\LaravelMailCssInliner`.

<?php

namespace Stayallive\LaravelMailCssInliner;

use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/mail-css-inliner.php' => base_path('config/mail-css-inliner.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mail-css-inliner.php', 'mail-css-inliner');

        $this->app->singleton(CssInlinerPlugin::class, static function ($app) {
            return new CssInlinerPlugin(
                $app['config']->get('mail-css-inliner.inline_css_files', [])
            );
        });

        $this->app->extend('mail.manager', function (MailManager $manager) {
            $manager->getSwiftMailer()->registerPlugin(
                $this->app->make(CssInlinerPlugin::class)
            );

            return $manager;
        });
    }
}

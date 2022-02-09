<?php

namespace Stayallive\LaravelMailCssInliner;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/mail-css-inliner.php' => base_path('config/mail-css-inliner.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mail-css-inliner.php', 'mail-css-inliner');

        $this->app->singleton(SymfonyMailerCssInliner::class, static function ($app) {
            return new SymfonyMailerCssInliner(
                $app['config']->get('mail-css-inliner.inline_css_files', [])
            );
        });

        Event::listen(MessageSending::class, SymfonyMailerCssInliner::class);
    }
}

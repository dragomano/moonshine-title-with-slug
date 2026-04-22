<?php

declare(strict_types=1);

namespace Bugo\MoonShine\TitleWithSlug;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moonshine-title-with-slug.php', 'moonshine-title-with-slug');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moonshine-title-with-slug');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'moonshine-title-with-slug');

        $this->publishes([
            __DIR__ . '/../config/moonshine-title-with-slug.php' => config_path('moonshine-title-with-slug.php'),
        ], ['moonshine-title-with-slug-config']);

        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('vendor/moonshine-title-with-slug'),
        ], ['moonshine-title-with-slug-assets', 'laravel-assets']);
    }
}

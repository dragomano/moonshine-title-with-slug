<?php

declare(strict_types=1);

use Bugo\MoonShine\TitleWithSlug\ServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('loads the package service provider', function (): void {
    expect(class_exists(ServiceProvider::class))->toBeTrue()
        ->and(in_array(ServiceProvider::class, array_keys(app()->getLoadedProviders()), true))->toBeTrue()
        ->and(app()->getProvider(ServiceProvider::class))
        ->toBeInstanceOf(ServiceProvider::class);
});

it('merges package config', function (): void {
    expect(config('moonshine-title-with-slug'))->toBeArray()
        ->toHaveKey('field_title')
        ->toHaveKey('field_slug')
        ->toHaveKey('url_host');
});

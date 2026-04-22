# MoonShine Title With Slug

![PHP](https://img.shields.io/badge/PHP-^8.2-blue.svg?style=flat)
[![Coverage Status](https://coveralls.io/repos/github/dragomano/moonshine-title-with-slug/badge.svg?branch=main)](https://coveralls.io/github/dragomano/moonshine-title-with-slug?branch=main)

[Русская версия](README.ru.md)

This package adds a title + permalink field group for MoonShine 4.

<img width="1377" height="376" alt="Preview" src="https://github.com/user-attachments/assets/a34a900b-913d-40c6-9140-9d486372d2e1" />

## Features

- `TitleSlug::make(...)` helper for building a title + slug fieldset
- `Slug` field with permalink preview
- automatic slug generation from title
- manual slug editing with edit/reset/cancel controls
- auto-slug stops after manual slug editing
- create/edit context detection from the current model
- optional visit link with custom label or custom route URL
- configurable host/path visibility in permalink preview
- configurable title and slug labels
- configurable slug postfix
- custom slugifier callback
- `titleAfterStateUpdated` and `slugAfterStateUpdated` callbacks
- custom title field wrapper callback
- package config with default title field, slug field, and host
- translations and publishable assets/config

## Installation

```bash
composer require bugo/moonshine-title-with-slug
```

Optionally publish config and assets:

```bash
php artisan vendor:publish --tag=moonshine-title-with-slug-config
php artisan vendor:publish --tag=moonshine-title-with-slug-assets
```

## Basic usage

```php
use Bugo\MoonShine\TitleWithSlug\TitleSlug;

TitleSlug::make(
    titleColumn: 'custom_title',
    slugColumn: 'custom_slug',
);
```

## Example options

```php
TitleSlug::make(
    titleColumn: 'custom_title',
    slugColumn: 'custom_slug',
)
    ->titleOptions(label: 'Title')
    ->slugOptions(
        label: 'Permalink',
        labelPostfix: '.html',
    )
    ->urlOptions(
        path: '/posts/',
        hostVisible: false,
        visitLinkLabel: 'Open',
    );
```

## Validation

Use MoonShine page validation rules for server-side checks.

```php
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Stringable;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;

protected function rules(DataWrapperContract $item): array
{
    return [
        'title' => ['required'],
        'slug' => [
            'required',
            'regex:/^[a-z-]+$/',
            Rule::unique($item->getOriginal()::class, 'slug')
                ->ignoreModel($item->getOriginal()),
        ],
    ];
}
```

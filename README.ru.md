# MoonShine Title With Slug

![PHP](https://img.shields.io/badge/PHP-^8.2-blue.svg?style=flat)
[![Coverage Status](https://coveralls.io/repos/github/dragomano/moonshine-title-with-slug/badge.svg?branch=main)](https://coveralls.io/github/dragomano/moonshine-title-with-slug?branch=main)

[English version](README.md)

Этот пакет добавляет группу полей заголовка и постоянной ссылки для MoonShine 4.

<img width="1383" height="368" alt="Preview" src="https://github.com/user-attachments/assets/decff9f2-6fb5-47a4-87e8-4844a5485891" />

## Возможности

- хелпер `TitleSlug::make(...)` для создания fieldset с заголовком и слагом
- поле `Slug` с предпросмотром постоянной ссылки
- автоматическая генерация слага из заголовка
- ручное редактирование слага с контролами edit/reset/cancel
- автогенерация слага прекращается после ручного редактирования
- определение контекста create/edit на основе текущей модели
- необязательная ссылка перехода с кастомной меткой или кастомным URL маршрута
- настраиваемое отображение host/path в предпросмотре постоянной ссылки
- настраиваемые метки заголовка и слага
- настраиваемый postfix для слага
- кастомный колбек для slugifier
- колбеки `titleAfterStateUpdated` и `slugAfterStateUpdated`
- кастомный колбек-обёртка для поля заголовка
- конфиг пакета с полями заголовка, слага и host по умолчанию
- переводы и публикуемые assets/config

## Установка

```bash
composer require bugo/moonshine-title-with-slug
```

При необходимости можно опубликовать конфиг и assets:

```bash
php artisan vendor:publish --tag=moonshine-title-with-slug-config
php artisan vendor:publish --tag=moonshine-title-with-slug-assets
```

## Базовое использование

```php
use Bugo\MoonShine\TitleWithSlug\TitleSlug;

TitleSlug::make(
    titleColumn: 'custom_title',
    slugColumn: 'custom_slug',
);
```

## Пример с опциями

```php
TitleSlug::make(
    titleColumn: 'custom_title',
    slugColumn: 'custom_slug',
)
    ->titleOptions(label: 'Заголовок')
    ->slugOptions(
        label: 'Постоянная ссылка',
        labelPostfix: '.html',
    )
    ->urlOptions(
        path: '/posts/',
        hostVisible: false,
        visitLinkLabel: 'Открыть',
    );
```

## Валидация

Используйте правила валидации страницы MoonShine для серверной проверки.

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

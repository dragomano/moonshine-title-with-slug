<?php

declare(strict_types=1);

use Bugo\MoonShine\TitleWithSlug\Slug;
use MoonShine\AssetManager\Css;
use MoonShine\Laravel\Fields\Slug as MoonShineSlug;
use Tests\TestCase;

uses(TestCase::class);

it('instantiates slug input as moonshine slug field', function (): void {
    $field = Slug::make('Slug', 'slug')
        ->from('title')
        ->slugInputBaseUrl('https://example.com')
        ->slugInputBasePath('/posts/')
        ->slugInputRecordSlug(static fn(): string => 'hello-world')
        ->slugInputVisitLinkLabel('Open');

    expect($field)
        ->toBeInstanceOf(Slug::class)
        ->toBeInstanceOf(MoonShineSlug::class)
        ->and($field->getFullBaseUrl())->toBe('https://example.com/posts/')
        ->and($field->getRecordUrl())->toBe('https://example.com/posts/hello-world')
        ->and($field->getVisitLinkLabel())->toBe('Open');
});

it('resolves slug input configuration from closures', function (): void {
    $field = Slug::make('Slug', 'slug')
        ->slugInputContext(static fn(): string => 'edit')
        ->slugInputBaseUrl(static fn(): string => 'https://example.com/')
        ->slugInputBasePath(static fn(): string => '/articles/')
        ->slugInputModelName(static fn(): string => 'Article')
        ->slugInputSlugLabelPostfix(static fn(): string => '.html')
        ->slugInputRecordSlug(static fn(): string => 'test-slug')
        ->slugInputUrlVisitLinkVisible(static fn(): bool => false);

    expect($field->getContext())->toBe('edit')
        ->and($field->getBaseUrl())->toBe('https://example.com')
        ->and($field->getBasePath())->toBe('/articles/')
        ->and($field->getFullBaseUrl())->toBe('https://example.com/articles/')
        ->and($field->getSlugInputModelName())->toBe('Article')
        ->and($field->getSlugLabelPostfix())->toBe('.html')
        ->and($field->getRecordSlug())->toBe('test-slug')
        ->and($field->getSlugInputUrlVisitLinkVisible())->toBeFalse();
});

it('builds visit link label from translation when custom label is missing', function (): void {
    $field = Slug::make('Slug', 'slug')
        ->slugInputModelName(static fn(): string => 'Article');

    expect($field->getVisitLinkLabel())
        ->toBe('Visit Article');

    $field->slugInputVisitLinkLabel('');

    expect($field->getVisitLinkLabel())->toBe('');
});

it('prefers custom visit route over generated record url', function (): void {
    $field = Slug::make('Slug', 'slug')
        ->slugInputBaseUrl('https://example.com')
        ->slugInputBasePath('/posts/')
        ->slugInputRecordSlug('hello-world')
        ->slugInputVisitLinkRoute(static fn(): string => 'https://example.com/custom-url');

    expect($field->getRecordUrl())->toBe('https://example.com/custom-url');
});

it('returns null record url when record slug is missing', function (): void {
    $field = Slug::make('Slug', 'slug')
        ->slugInputBaseUrl('https://example.com')
        ->slugInputBasePath('/posts/');

    expect($field->getRecordUrl())->toBeNull();
});

it('exposes assets and computed view data', function (): void {
    $field = new class ('Slug', 'slug') extends Slug {
        public function exposedAssets(): array
        {
            return $this->assets();
        }

        public function exposedViewData(): array
        {
            return $this->viewData();
        }
    };

    $field
        ->slugInputBaseUrl('https://example.com')
        ->slugInputBasePath('/posts/')
        ->slugInputContext('edit')
        ->slugInputLabelPrefix('Permalink')
        ->slugInputRecordSlug('hello-world')
        ->slugInputShowUrl(false)
        ->slugInputSlugLabelPostfix('.html')
        ->slugInputUrlVisitLinkVisible(false)
        ->slugInputVisitLinkLabel('Open')
        ->customAttributes(['readonly' => true]);

    expect($field->exposedAssets())
        ->toHaveCount(1)
        ->and($field->exposedAssets()[0])->toBeInstanceOf(Css::class);

    expect($field->exposedViewData())->toBe([
        'basePath' => '/posts/',
        'context' => 'edit',
        'fullBaseUrl' => '/posts/',
        'labelPrefix' => 'Permalink',
        'recordSlug' => 'hello-world',
        'recordUrl' => '/posts/hello-world',
        'readonly' => true,
        'showUrl' => false,
        'showVisitLink' => false,
        'slugLabelPostfix' => '.html',
        'visitLinkLabel' => 'Open',
    ]);
});

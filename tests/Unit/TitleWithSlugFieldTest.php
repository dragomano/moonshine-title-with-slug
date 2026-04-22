<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Bugo\MoonShine\TitleWithSlug\Slug;
use Bugo\MoonShine\TitleWithSlug\TitleSlug;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Slug as MoonShineSlug;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Text;
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

it('builds a moonshine fieldset with title and slug fields', function (): void {
    $fieldset = TitleSlug::make()
        ->titleOptions(label: 'Title')
        ->slugOptions(label: 'Permalink');

    expect($fieldset)->toBeInstanceOf(TitleSlug::class)
        ->toBeInstanceOf(Fieldset::class);

    $fields = $fieldset->getFields()->values();

    expect($fields)->toHaveCount(3)
        ->and($fields[0])
        ->toBeInstanceOf(Text::class)
        ->and($fields[0]->getColumn())->toBe('title')
        ->and($fields[1])->toBeInstanceOf(Slug::class)
        ->and($fields[1]->getColumn())->toBe('slug')
        ->and($fields[2])->toBeInstanceOf(Hidden::class);
});

it('supports wrapper configuration', function (): void {
    $fieldset = TitleSlug::make()
        ->titleOptions(fieldWrapper: static fn(Text $field): Text => $field->hint('Wrapped'));

    $fields = $fieldset->getFields()->values();

    expect($fields[0]->getHint())->toBe('Wrapped');
});

it('marks slug context as edit for existing records', function (): void {
    $field = TitleSlug::make()->getFields()->values()->get(1);

    $record = new class extends Model {
        public $exists = true;

        protected $attributes = [
            'slug' => 'existing-slug',
        ];
    };

    fillFromModel($field, $record);

    expect($field)
        ->toBeInstanceOf(Slug::class)
        ->and($field->getContext())->toBe('edit')
        ->and($field->getRecordSlug())->toBe('existing-slug');
});

it('reactively updates the slug until manual editing is enabled', function (): void {
    $fields = TitleSlug::make()->getFields()->onlyFields(withApplyWrappers: true);

    $title = $fields->findByColumn('title');
    $slug = $fields->findByColumn('slug');

    $title->getReactiveCallback($fields, 'Hello World', [
        'title' => 'Hello World',
        'slug_auto_update_disabled' => '0',
    ], []);

    expect($slug->toValue())->toBe('hello-world');

    $title->getReactiveCallback($fields, 'Changed Title', [
        'title' => 'Changed Title',
        'slug_auto_update_disabled' => '1',
    ], []);

    expect($slug->toValue())->toBe('hello-world');
});

it('preserves auto-generated slug during the same reactive cycle', function (): void {
    $fields = TitleSlug::make()->getFields()->onlyFields(withApplyWrappers: true);

    $title = $fields->findByColumn('title');
    $slug = $fields->findByColumn('slug');

    $values = [
        'title' => 'Hello World',
        'slug' => '',
        'slug_auto_update_disabled' => '0',
    ];

    $title->getReactiveCallback($fields, 'Hello World', $values, []);
    $slug->getReactiveCallback($fields, '', $values, []);

    expect($slug->toValue())->toBe('hello-world');
});

it('continues auto-generating slug across sequential title updates', function (): void {
    $fields = TitleSlug::make()->getFields()->onlyFields(withApplyWrappers: true);

    $title = $fields->findByColumn('title');
    $slug = $fields->findByColumn('slug');

    $initialValues = [
        'title' => 'H',
        'slug' => '',
        'slug_auto_update_disabled' => '0',
    ];

    $title->getReactiveCallback($fields, 'H', $initialValues, []);
    $slug->getReactiveCallback($fields, '', $initialValues, []);

    $nextValues = [
        'title' => 'Hello World',
        'slug' => 'h',
        'slug_auto_update_disabled' => '0',
    ];

    $title->getReactiveCallback($fields, 'Hello World', $nextValues, []);
    $slug->getReactiveCallback($fields, 'h', $nextValues, []);

    expect($slug->toValue())->toBe('hello-world');
});

it('supports a custom slugifier callback', function (): void {
    $fields = TitleSlug::make()
        ->slugOptions(
            slugifier: static fn(string $value): string => str($value)->lower()->replace(' ', '_')->value(),
        )
        ->getFields()
        ->onlyFields(withApplyWrappers: true);

    $title = $fields->findByColumn('title');
    $slug = $fields->findByColumn('slug');

    $title->getReactiveCallback($fields, 'Hello World', [
        'title' => 'Hello World',
        'slug_auto_update_disabled' => '0',
    ], []);

    expect($slug->toValue())->toBe('hello_world');
});

it('clears auto-generated slug when title is empty', function (): void {
    $fields = TitleSlug::make()->getFields()->onlyFields(withApplyWrappers: true);

    $slug = $fields->findByColumn('slug');

    $slug->getReactiveCallback($fields, '', [
        'title' => '',
        'slug' => '',
        'slug_auto_update_disabled' => '0',
    ], []);

    expect($slug->toValue())->toBe('');
});

it('normalizes manual slug input and keeps auto slug disabled state', function (): void {
    $fields = TitleSlug::make()->getFields()->onlyFields(withApplyWrappers: true);

    $slug = $fields->findByColumn('slug');
    $auto = $fields->findByColumn('slug_auto_update_disabled');

    $slug->getReactiveCallback($fields, 'Manual Slug', [
        'title' => 'Ignored Title',
        'slug' => 'Manual Slug',
        'slug_auto_update_disabled' => '1',
    ], []);

    expect($slug->toValue())->toBe('manual-slug')
        ->and($auto->toValue())->toBe('1');
});

it('supports custom visit route and hidden host preview', function (): void {
    $field = TitleSlug::make()
        ->slugOptions(labelPostfix: '.html')
        ->urlOptions(
            path: '/blog/',
            hostVisible: false,
            visitLinkRoute: static fn(): string => 'https://example.com/custom-url',
            visitLinkLabel: 'View post',
        )
        ->getFields()
        ->values()
        ->get(1);

    $record = new class extends Model {
        public $exists = true;

        protected $attributes = [
            'slug' => 'post-title',
        ];
    };

    fillFromModel($field, $record);

    expect($field)
        ->toBeInstanceOf(Slug::class)
        ->and($field->getShowUrl())->toBeFalse()
        ->and($field->getFullBaseUrl())->toBe('/blog/')
        ->and($field->getRecordUrl())->toBe('https://example.com/custom-url')
        ->and($field->getVisitLinkLabel())->toBe('View post')
        ->and($field->getSlugLabelPostfix())->toBe('.html');
});

it('allows an empty slug when rules do not require it', function (): void {
    $fields = TitleSlug::make()
        ->slugOptions(rules: [])
        ->getFields()
        ->values();

    $slug = $fields->get(1);

    expect($slug)->toBeInstanceOf(Slug::class)
        ->and($slug->getAttribute('required'))->toBeNull();
});

it('does not apply internal auto slug state to the model', function (): void {
    $fields = TitleSlug::make()->getFields()->values();

    expect($fields[2])
        ->toBeInstanceOf(Hidden::class)
        ->and($fields[2]->getColumn())->toBe('slug_auto_update_disabled')
        ->and($fields[2]->isCanApply())->toBeFalse();
});

it('sets internal auto slug state based on record context', function (): void {
    $fields = TitleSlug::make()->getFields()->values();

    expect($fields[2]->toValue())->toBe('0');

    $fields[2]->fillData(new TitleWithSlugTestDataWrapper(
        new TitleWithSlugTestPost(['slug' => 'existing-slug'], exists: true),
        ['slug_auto_update_disabled' => '0'],
    ));

    expect($fields[2]->toValue())->toBe('1');
});

it('reads default field names from config', function (): void {
    config()->set('moonshine-title-with-slug.field_title', 'name');
    config()->set('moonshine-title-with-slug.field_slug', 'handle');

    $fields = TitleSlug::make()->getFields()->values();

    expect($fields[0]->getColumn())->toBe('name')
        ->and($fields[1]->getColumn())->toBe('handle');
});

it('supports readonly title and slug fields', function (): void {
    $fields = TitleSlug::make()
        ->titleOptions(readonly: true)
        ->slugOptions(readonly: true)
        ->getFields()
        ->values();

    expect($fields[0]->getAttribute('readonly'))->toBeTrue()
        ->and($fields[1]->getAttribute('readonly'))->toBeTrue();
});

it('supports dynamic visit link labels from closure', function (): void {
    $field = TitleSlug::make()
        ->urlOptions(visitLinkLabel: static fn(): string => 'Dynamic label')
        ->getFields()
        ->values()
        ->get(1);

    $record = new class extends Model {
        public $exists = true;

        protected $attributes = [
            'slug' => 'dynamic-label',
        ];
    };

    fillFromModel($field, $record);

    expect($field->getVisitLinkLabel())->toBe('Dynamic label');
});

it('resolves model name and record slug only for filled models', function (): void {
    $field = TitleSlug::make()->getFields()->values()->get(1);

    expect($field->getSlugInputModelName())->toBe('')
        ->and($field->getRecordSlug())->toBeNull();

    $record = new TitleWithSlugTestPost(['slug' => 'article-slug'], exists: true);

    fillFromModel($field, $record);

    expect($field->getSlugInputModelName())->toBe('Title With Slug Test Post')
        ->and($field->getRecordSlug())->toBe('article-slug');
});

it('derives field title from custom title field name', function (): void {
    $fields = TitleSlug::make(
        titleField: Text::make('Name', 'name'),
        slugColumn: 'handle',
    )->getFields()->onlyFields(withApplyWrappers: true);

    $title = $fields->findByColumn('name');
    $slug = $fields->findByColumn('handle');

    $title->getReactiveCallback($fields, 'My Page', [
        'name' => 'My Page',
        'handle_auto_update_disabled' => '0',
    ], []);

    expect($slug->toValue())->toBe('my-page');
});

it('supports extra input attributes on title field', function (): void {
    $fields = TitleSlug::make()
        ->titleOptions(extraInputAttributes: ['data-testid' => 'custom-title'])
        ->getFields()
        ->values();

    expect($fields[0]->getAttribute('data-testid'))->toBe('custom-title');
});

it('does not expose legacy unique compatibility attributes', function (): void {
    $fields = TitleSlug::make()->getFields()->values();

    expect($fields[0]->getAttribute('data-title-unique'))->toBeNull()
        ->and($fields[1]->getAttribute('data-slug-unique'))->toBeNull();
});

it('passes the full reactive context to after state updated callbacks', function (): void {
    $captured = [];

    $fields = TitleSlug::make()
        ->titleOptions(
            afterStateUpdated: static function (
                FieldsContract $fields,
                mixed $value,
                Field $field,
                array $values,
                array $additionally,
            ) use (&$captured): FieldsContract {
                $captured = [$value, $field->getColumn(), $values, $additionally];

                return $fields;
            },
        )
        ->getFields()
        ->onlyFields(withApplyWrappers: true);

    $title = $fields->findByColumn('title');

    $title->getReactiveCallback($fields, 'Hello World', [
        'title' => 'Hello World',
        'slug_auto_update_disabled' => '0',
    ], ['trigger' => 'test']);

    expect($captured)->toBe([
        'Hello World',
        'title',
        [
            'title' => 'Hello World',
            'slug_auto_update_disabled' => '0',
        ],
        ['trigger' => 'test'],
    ]);
});

final class TitleWithSlugTestPost extends Model
{
    protected $fillable = ['slug'];

    public function __construct(array $attributes = [], bool $exists = false)
    {
        parent::__construct($attributes);

        $this->exists = $exists;
    }
}

final readonly class TitleWithSlugTestDataWrapper implements DataWrapperContract
{
    public function __construct(
        private Model $original,
        private array $data,
        private int|string|null $key = null,
    ) {}

    public function getOriginal(): mixed
    {
        return $this->original;
    }

    public function getKey(): int|string|null
    {
        return $this->key;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}

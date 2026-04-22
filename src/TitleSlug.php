<?php

declare(strict_types=1);

namespace Bugo\MoonShine\TitleWithSlug;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Text;

class TitleSlug extends Fieldset
{
    private readonly string $titleColumn;

    private readonly string $slugColumn;

    private readonly ?Field $titleField;

    private string|Closure|null $urlPath = '/';

    private string|Closure|null $urlHost = null;

    private bool $urlHostVisible = true;

    private bool|Closure $urlVisitLinkVisible = true;

    private Closure|string|null $urlVisitLinkLabel = null;

    private ?Closure $urlVisitLinkRoute = null;

    private string|Closure|null $titleLabel = null;

    private ?string $titlePlaceholder = null;

    private array|Closure|null $titleExtraInputAttributes = null;

    private array $titleRules = ['required'];

    private bool|Closure $titleIsReadonly = false;

    private bool|Closure $titleAutofocus = true;

    private ?Closure $titleAfterStateUpdated = null;

    private ?Closure $titleFieldWrapper = null;

    private ?string $slugLabel = null;

    private array $slugRules = ['required'];

    private bool|Closure $slugIsReadonly = false;

    private ?Closure $slugAfterStateUpdated = null;

    private ?Closure $slugSlugifier = null;

    private string|Closure|null $slugLabelPostfix = null;

    public function __construct(
        ?string $titleColumn = null,
        ?string $slugColumn = null,
        ?Field $titleField = null,
    ) {
        parent::__construct('');

        $this->titleColumn = $titleColumn ?? $titleField?->getColumn() ?? config('moonshine-title-with-slug.field_title');
        $this->slugColumn = $slugColumn ?? config('moonshine-title-with-slug.field_slug');
        $this->titleField = $titleField;

        $this->withoutWrapper();
        $this->syncFields();
    }

    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }

    public function titleOptions(
        string|Closure|null $label = null,
        ?string $placeholder = null,
        array|Closure|null $extraInputAttributes = null,
        array $rules = ['required'],
        bool|Closure $readonly = false,
        bool|Closure $autofocus = true,
        ?Closure $afterStateUpdated = null,
        ?Closure $fieldWrapper = null,
    ): static {
        $this->titleLabel = $label;
        $this->titlePlaceholder = $placeholder;
        $this->titleExtraInputAttributes = $extraInputAttributes;
        $this->titleRules = $rules;
        $this->titleIsReadonly = $readonly;
        $this->titleAutofocus = $autofocus;
        $this->titleAfterStateUpdated = $afterStateUpdated;
        $this->titleFieldWrapper = $fieldWrapper;

        return $this->syncFields();
    }

    public function slugOptions(
        ?string $label = null,
        array $rules = ['required'],
        bool|Closure $readonly = false,
        ?Closure $afterStateUpdated = null,
        ?Closure $slugifier = null,
        string|Closure|null $labelPostfix = null,
    ): static {
        $this->slugLabel = $label;
        $this->slugRules = $rules;
        $this->slugIsReadonly = $readonly;
        $this->slugAfterStateUpdated = $afterStateUpdated;
        $this->slugSlugifier = $slugifier;
        $this->slugLabelPostfix = $labelPostfix;

        return $this->syncFields();
    }

    public function urlOptions(
        string|Closure|null $path = '/',
        string|Closure|null $host = null,
        bool $hostVisible = true,
        bool|Closure $visitLinkVisible = true,
        Closure|string|null $visitLinkLabel = null,
        ?Closure $visitLinkRoute = null,
    ): static {
        $this->urlPath = $path;
        $this->urlHost = $host;
        $this->urlHostVisible = $hostVisible;
        $this->urlVisitLinkVisible = $visitLinkVisible;
        $this->urlVisitLinkLabel = $visitLinkLabel;
        $this->urlVisitLinkRoute = $visitLinkRoute;

        return $this->syncFields();
    }

    private function syncFields(): static
    {
        $autoSlugColumn = "{$this->slugColumn}_auto_update_disabled";

        $this->fields([
            $this->makeTitleField($autoSlugColumn),
            $this->makeSlugField($autoSlugColumn),
            $this->makeAutoSlugField($autoSlugColumn),
        ]);

        $this->getFields()
            ->onlyFields()
            ->map(fn(FieldContract $field): FieldContract => $field->setParent($this));

        return $this;
    }

    private function makeTitleField(string $autoSlugColumn): Field
    {
        $title = $this->titleField instanceof Field
            ? clone $this->titleField
            : Text::make($this->titleLabel, $this->titleColumn)
                ->placeholder($this->titlePlaceholder ?: Str::of($this->titleColumn)->headline()->value());

        $title
            ->setColumn($this->titleColumn)
            ->setId(self::fieldId('moonshine-title-', $this->titleColumn))
            ->customAttributes(value($this->titleExtraInputAttributes, $title) ?: [])
            ->customAttributes([
                'autofocus' => value($this->titleAutofocus, $title),
            ])
            ->reactive(
                callback: function (FieldsContract $fields, mixed $value, Field $field, array $values, array $additionally) use (
                    $autoSlugColumn
                ): FieldsContract {
                    $slug = $fields->findByColumn($this->slugColumn);
                    $autoSlugState = $fields->findByColumn($autoSlugColumn);
                    $autoSlugDisabled = (bool) data_get($values, $autoSlugColumn, false);

                    if ($slug instanceof Slug && ! $autoSlugDisabled && filled($value)) {
                        $slug->setValue(self::slugify((string) $value, $this->slugSlugifier));
                    }

                    if ($autoSlugState instanceof Hidden) {
                        $autoSlugState->setValue($autoSlugDisabled ? '1' : '0');
                    }

                    return self::callAfterStateUpdated(
                        $this->titleAfterStateUpdated,
                        $fields,
                        $value,
                        $field,
                        $values,
                        $additionally,
                    ) ?? $fields;
                },
                silentSelf: true,
            );

        if (self::hasRequiredRule($this->titleRules)) {
            $title->required();
        }

        if (value($this->titleIsReadonly, $title)) {
            $title->readonly();
        }

        if ($this->titleFieldWrapper instanceof Closure) {
            $wrapped = ($this->titleFieldWrapper)($title);

            if ($wrapped instanceof Field) {
                return $wrapped;
            }
        }

        return $title;
    }

    private function makeSlugField(string $autoSlugColumn): Slug
    {
        $autoSlugId = self::fieldId('moonshine-title-with-slug-', $autoSlugColumn);

        $slug = Slug::make($this->slugLabel, $this->slugColumn)
            ->setId(self::fieldId('moonshine-title-with-slug-', $this->slugColumn))
            ->from($this->titleColumn)
            ->slugInputBasePath($this->urlPath)
            ->slugInputBaseUrl($this->urlHost ?? config('moonshine-title-with-slug.url_host'))
            ->slugInputContext(static fn(Slug $field): string => self::resolveContext($field))
            ->slugInputLabelPrefix($this->slugLabel)
            ->slugInputModelName(static fn(Slug $field): string => self::resolveModelName($field))
            ->slugInputRecordSlug(static fn(Slug $field): ?string => self::resolveRecordSlug($field))
            ->slugInputShowUrl($this->urlHostVisible)
            ->slugInputSlugLabelPostfix($this->slugLabelPostfix)
            ->slugInputUrlVisitLinkVisible($this->urlVisitLinkVisible)
            ->slugInputVisitLinkLabel($this->urlVisitLinkLabel)
            ->slugInputVisitLinkRoute($this->urlVisitLinkRoute)
            ->customAttributes([
                'data-auto-slug-input' => 'true',
                'data-auto-slug-control' => $autoSlugId,
                'x-on:input' => "if (\$el.dataset.syncing !== 'true') { const control = document.getElementById('$autoSlugId'); if (control) { control.value = '1'; control.dispatchEvent(new Event('input', { bubbles: true })); } }",
            ])
            ->reactive(
                callback: function (FieldsContract $fields, mixed $value, Slug $field, array $values, array $additionally) use (
                    $autoSlugColumn
                ): FieldsContract {
                    $autoSlugDisabled = (bool) data_get($values, $autoSlugColumn, false);
                    $titleColumn = $field->getFrom();
                    $titleValue = (string) data_get($values, $titleColumn, '');

                    if (! $autoSlugDisabled) {
                        $normalized = filled($titleValue)
                            ? self::slugify($titleValue, $this->slugSlugifier)
                            : '';
                    } else {
                        $normalized = filled($value) ? self::slugify((string) $value, $this->slugSlugifier) : '';
                    }

                    $field->setValue($normalized);

                    if ($auto = $fields->findByColumn($autoSlugColumn)) {
                        $auto->setValue($autoSlugDisabled ? '1' : '0');
                    }

                    return self::callAfterStateUpdated(
                        $this->slugAfterStateUpdated,
                        $fields,
                        $normalized,
                        $field,
                        $values,
                        $additionally,
                    ) ?? $fields;
                },
                silentSelf: true,
            );

        if (self::hasRequiredRule($this->slugRules)) {
            $slug->required();
        }

        if (value($this->slugIsReadonly, $slug)) {
            $slug->readonly();
        }

        return $slug;
    }

    private function makeAutoSlugField(string $autoSlugColumn): Hidden
    {
        return Hidden::make($autoSlugColumn, $autoSlugColumn)
            ->setId(self::fieldId('moonshine-title-with-slug-', $autoSlugColumn))
            ->default('0')
            ->canApply(static fn(): bool => false)
            ->reactive(
                callback: static fn(FieldsContract $fields): FieldsContract => $fields,
                silent: true,
                silentSelf: true,
            )
            ->afterFill(static function (Hidden $field): Hidden {
                $field->setValue(self::resolveContext($field) === 'edit' ? '1' : '0');

                return $field;
            });
    }

    private static function resolveContext(Field $field): string
    {
        $record = self::resolveRecord($field);

        return $record instanceof Model && $record->exists ? 'edit' : 'create';
    }

    private static function resolveRecord(Field $field): mixed
    {
        return $field->getData()?->getOriginal();
    }

    private static function resolveModelName(Field $field): string
    {
        $record = self::resolveRecord($field);

        if (! $record instanceof Model) {
            return '';
        }

        return Str::headline(class_basename($record));
    }

    private static function resolveRecordSlug(Field $field): ?string
    {
        $record = self::resolveRecord($field);

        if (! $record instanceof Model) {
            return null;
        }

        $slug = $record->getAttribute($field->getColumn());

        return is_scalar($slug) ? (string) $slug : null;
    }

    private static function slugify(string $value, ?Closure $slugSlugifier = null): string
    {
        $slug = trim($value);

        if ($slugSlugifier instanceof Closure) {
            $result = $slugSlugifier($slug);

            return is_string($result) ? $result : '';
        }

        return Str::slug($slug);
    }

    private static function hasRequiredRule(array $rules): bool
    {
        return in_array('required', $rules, true);
    }

    private static function callAfterStateUpdated(
        ?Closure $callback,
        FieldsContract $fields,
        mixed $value,
        Field $field,
        array $values,
        array $additionally,
    ): ?FieldsContract {
        if (! $callback instanceof Closure) {
            return null;
        }

        $result = $callback($fields, $value, $field, $values, $additionally);

        return $result instanceof FieldsContract ? $result : null;
    }

    private static function fieldId(string $prefix, string $column): string
    {
        return Str::of($column)->slug('-')->prepend($prefix)->value();
    }
}

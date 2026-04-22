<?php

declare(strict_types=1);

namespace Bugo\MoonShine\TitleWithSlug;

use Closure;
use Illuminate\Support\Str;
use MoonShine\AssetManager\Css;
use MoonShine\Laravel\Fields\Slug as MoonShineSlug;

class Slug extends MoonShineSlug
{
    protected string $view = 'moonshine-title-with-slug::forms.fields.slug-input';

    protected string|Closure|null $context = null;

    protected string|Closure $basePath = '/';

    protected string|Closure|null $baseUrl = null;

    protected bool $showUrl = true;

    protected string $labelPrefix = '';

    protected ?Closure $visitLinkRoute = null;

    protected string|Closure|null $visitLinkLabel = null;

    protected bool|Closure $slugInputUrlVisitLinkVisible = true;

    protected ?Closure $slugInputModelName = null;

    protected string|Closure|null $slugLabelPostfix = null;

    protected string|Closure|null $recordSlug = null;

    public function slugInputUrlVisitLinkVisible(bool|Closure $slugInputUrlVisitLinkVisible): static
    {
        $this->slugInputUrlVisitLinkVisible = $slugInputUrlVisitLinkVisible;

        return $this;
    }

    public function getSlugInputUrlVisitLinkVisible(): bool
    {
        return (bool) value($this->slugInputUrlVisitLinkVisible, $this);
    }

    public function slugInputModelName(?Closure $slugInputModelName): static
    {
        $this->slugInputModelName = $slugInputModelName;

        return $this;
    }

    public function getSlugInputModelName(): ?string
    {
        return value($this->slugInputModelName, $this);
    }

    public function slugInputVisitLinkRoute(?Closure $visitLinkRoute): static
    {
        $this->visitLinkRoute = $visitLinkRoute;

        return $this;
    }

    public function getVisitLinkRoute(): ?string
    {
        return value($this->visitLinkRoute, $this);
    }

    public function slugInputVisitLinkLabel(string|Closure|null $visitLinkLabel): static
    {
        $this->visitLinkLabel = $visitLinkLabel;

        return $this;
    }

    public function getVisitLinkLabel(): string
    {
        $label = value($this->visitLinkLabel, $this);

        if ($label === '') {
            return '';
        }

        return $label ?: trim(trans('moonshine-title-with-slug::package.permalink_label_link_visit') . ' ' . $this->getSlugInputModelName());
    }

    public function slugInputLabelPrefix(?string $labelPrefix): static
    {
        $this->labelPrefix = $labelPrefix ?? trans('moonshine-title-with-slug::package.permalink_label');

        return $this;
    }

    public function getLabelPrefix(): string
    {
        return $this->labelPrefix;
    }

    public function slugInputContext(string|Closure|null $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): ?string
    {
        return value($this->context, $this);
    }

    public function slugInputSlugLabelPostfix(string|Closure|null $slugLabelPostfix): static
    {
        $this->slugLabelPostfix = $slugLabelPostfix;

        return $this;
    }

    public function getSlugLabelPostfix(): ?string
    {
        return value($this->slugLabelPostfix, $this);
    }

    public function slugInputRecordSlug(string|Closure|null $recordSlug): static
    {
        $this->recordSlug = $recordSlug;

        return $this;
    }

    public function getRecordSlug(): ?string
    {
        return value($this->recordSlug, $this);
    }

    public function getRecordUrl(): ?string
    {
        if (! $this->getRecordSlug()) {
            return null;
        }

        return $this->getVisitLinkRoute() ?: $this->getFullBaseUrl() . $this->getRecordSlug();
    }

    public function slugInputBasePath(string|Closure|null $path): static
    {
        $this->basePath = $path ?? $this->basePath;

        return $this;
    }

    public function slugInputBaseUrl(string|Closure|null $url): static
    {
        $this->baseUrl = $url ?: config('app.url');

        return $this;
    }

    public function getBaseUrl(): string
    {
        return Str::of((string) value($this->baseUrl, $this))->rtrim('/')->value();
    }

    public function slugInputShowUrl(bool $showUrl): static
    {
        $this->showUrl = $showUrl;

        return $this;
    }

    public function getShowUrl(): bool
    {
        return $this->showUrl;
    }

    public function getFullBaseUrl(): string
    {
        return $this->showUrl
            ? $this->getBaseUrl() . $this->getBasePath()
            : $this->getBasePath();
    }

    public function getBasePath(): string
    {
        return (string) value($this->basePath, $this);
    }

    protected function assets(): array
    {
        return [
            Css::make('vendor/moonshine-title-with-slug/moonshine-title-with-slug.css'),
        ];
    }

    protected function viewData(): array
    {
        return [
            'basePath' => $this->getBasePath(),
            'context' => $this->getContext(),
            'fullBaseUrl' => $this->getFullBaseUrl(),
            'labelPrefix' => $this->getLabelPrefix(),
            'recordSlug' => $this->getRecordSlug(),
            'recordUrl' => $this->getRecordUrl(),
            'readonly' => (bool) $this->getAttribute('readonly', false),
            'showUrl' => $this->getShowUrl(),
            'showVisitLink' => $this->getSlugInputUrlVisitLinkVisible(),
            'slugLabelPostfix' => $this->getSlugLabelPostfix(),
            'visitLinkLabel' => $this->getVisitLinkLabel(),
        ];
    }
}

@props([
    'attributes'       => null,
    'basePath'         => '/',
    'context'          => 'create',
    'fullBaseUrl'      => '',
    'label'            => '',
    'labelPrefix'      => '',
    'readonly'         => false,
    'recordSlug'       => null,
    'recordUrl'        => null,
    'showUrl'          => true,
    'showVisitLink'    => true,
    'slugLabelPostfix' => null,
    'value'            => '',
    'visitLinkLabel'   => '',
])

<div
    class="mts-field"
    x-data="{
        fieldId: @js((string) ($attributes?->get('id') ?? 'moonshine-title-with-slug')),
        context: @js($context),
        isReadonly: @js($readonly),
        editing: false,
        mode: 'edit',
        persisted: @js((string) ($recordSlug ?? $value)),
        modified: false,
        init() {
            window.__mtsState ??= {}

            const saved = window.__mtsState[this.fieldId]

            if (saved) {
                this.mode = saved.mode
                this.editing = saved.editing
                this.persisted = saved.persisted
            } else {
                this.mode = this.context === 'create' ? 'create' : 'edit'
                this.editing = this.mode === 'create' && ! this.isReadonly
            }

            this.saveState()
            this.$nextTick(() => this.syncModified())
        },
        current() {
            return this.$refs.slugInput?.value ?? ''
        },
        isCreate() {
            return this.mode === 'create'
        },
        isEdit() {
            return this.mode === 'edit'
        },
        canStartEdit() {
            return this.isEdit() && ! this.editing && ! this.isReadonly
        },
        canSaveEdit() {
            return this.editing && ! this.isReadonly
        },
        canResetEdit() {
            return this.editing && this.modified && ! this.isReadonly
        },
        canCancelEdit() {
            return this.editing && this.isEdit() && ! this.isReadonly
        },
        shouldShowInput() {
            return this.editing || this.isCreate() || this.isReadonly
        },
        saveState() {
            window.__mtsState[this.fieldId] = {
                mode: this.mode,
                editing: this.editing,
                persisted: this.persisted,
            }
        },
        setValue(value) {
            if (! this.$refs.slugInput) {
                return
            }

            this.$refs.slugInput.dataset.syncing = 'true'
            this.$refs.slugInput.value = value
            this.$refs.slugInput.dispatchEvent(new Event('input', { bubbles: true }))
            delete this.$refs.slugInput.dataset.syncing
            this.syncModified()
        },
        syncModified() {
            this.modified = this.isEdit() && this.current() !== this.persisted
        },
        startEdit() {
            this.editing = true
            this.saveState()
            this.$nextTick(() => this.$refs.slugInput?.focus())
        },
        cancelEdit() {
            this.setValue(this.persisted)
            this.editing = false
            this.saveState()
        },
        saveEdit() {
            this.persisted = this.current()
            this.editing = false
            this.syncModified()
            this.saveState()
        },
        resetEdit() {
            this.setValue(this.persisted)
        },
    }"
    x-on:input.debounce.50ms="syncModified()"
>
    <div class="mts-preview">
        @if($label)
            <div class="mts-label">{{ $label }}</div>
        @endif

        <div class="mts-row">
            <div class="mts-main">
                <span class="mts-meta">
                    @if($labelPrefix)
                        <span class="mts-prefix">{{ $labelPrefix }}</span>
                    @endif

                    <span class="mts-base">{{ $showUrl ? $fullBaseUrl : $basePath }}</span>
                    <span class="mts-value" x-text="current() || @js((string) $value)"></span>

                    @if($slugLabelPostfix)
                        <span class="mts-postfix">{{ $slugLabelPostfix }}</span>
                    @endif
                </span>

                <span class="mts-status" x-show="modified" x-cloak>
                    {{ trans('moonshine-title-with-slug::package.permalink_status_changed') }}
                </span>
            </div>

            <div class="mts-tools">
                <div class="mts-actions" @if($readonly) style="display:none" @endif>
                    <button
                        type="button"
                        class="mts-button"
                        x-bind:style="canStartEdit() ? '' : 'display:none'"
                        x-on:click="startEdit()"
                    >
                        {{ trans('moonshine-title-with-slug::package.permalink_action_edit') }}
                    </button>

                    <button
                        type="button"
                        class="mts-button mts-button--primary"
                        x-bind:style="canSaveEdit() ? '' : 'display:none'"
                        x-on:click="saveEdit()"
                    >
                        {{ trans('moonshine-title-with-slug::package.permalink_action_ok') }}
                    </button>

                    <button
                        type="button"
                        class="mts-button"
                        x-bind:style="canResetEdit() ? '' : 'display:none'"
                        x-on:click="resetEdit()"
                    >
                        {{ trans('moonshine-title-with-slug::package.permalink_action_reset') }}
                    </button>

                    <button
                        type="button"
                        class="mts-button"
                        x-bind:style="canCancelEdit() ? '' : 'display:none'"
                        x-on:click="cancelEdit()"
                    >
                        {{ trans('moonshine-title-with-slug::package.permalink_action_cancel') }}
                    </button>
                </div>

                @if($showVisitLink && $recordUrl)
                    <a
                        class="mts-visit"
                        href="{{ $recordUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        x-show="! editing || isCreate()"
                    >
                        {{ $visitLinkLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div x-show="shouldShowInput()" x-cloak>
        <x-moonshine::form.input
            :attributes="$attributes->merge(['value' => $value])->merge([
                'x-ref'     => 'slugInput',
                ':readonly' => $readonly ? 'true' : 'false',
            ])"
        />
    </div>
</div>

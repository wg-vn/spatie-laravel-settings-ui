<?php

namespace WgVn\SettingsUi;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelSettings\Settings;
use WgVn\SettingsUi\Support\FieldGuesser;

/**
 * A page that edits one settings class.
 *
 * Every settings class without a page of its own gets a plain instance of this
 * class, with a form guessed from its properties. Extend it to choose the
 * fields, restrict access, or hook into loading and saving.
 */
class SettingsPage
{
    /**
     * @var class-string<Settings>
     */
    protected string $settings;

    protected ?string $title = null;

    protected ?string $slug = null;

    protected int $sort = 0;

    /**
     * @var array<int, Field>|null
     */
    private ?array $resolvedFields = null;

    /**
     * @var array<int, string>
     */
    private array $locked = [];

    /**
     * @param  class-string<Settings>  $settings
     */
    public static function for(string $settings): static
    {
        $page = new static;
        $page->settings = $settings;

        return $page;
    }

    /**
     * @return class-string<Settings>
     */
    public function getSettings(): string
    {
        return $this->settings ?? (string) str(class_basename(static::class))
            ->beforeLast('Settings')
            ->prepend(app()->getNamespace() . 'Settings\\')
            ->append('Settings');
    }

    public function getTitle(): string
    {
        return $this->title ?? Str::headline(Str::beforeLast(class_basename($this->getSettings()), 'Settings'));
    }

    public function getSlug(): string
    {
        return $this->slug ?? Str::slug($this->getSettings()::group());
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function canAccess(): bool
    {
        return true;
    }

    /**
     * Only gates saving: a user who can access the page still sees every value.
     * Restrict sensitive settings with canAccess() instead.
     */
    public function canEdit(): bool
    {
        return true;
    }

    /**
     * The form. Guessed from the settings class unless overridden.
     *
     * The name of each field must match a public property of the settings class.
     *
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return FieldGuesser::fields($this->getSettings());
    }

    /**
     * The fields, with requirement inferred from the property types and locked
     * properties disabled, since Spatie silently skips them on save.
     *
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        if ($this->resolvedFields !== null) {
            return $this->resolvedFields;
        }

        $settings = app($this->getSettings());
        $this->locked = $settings->getLockedProperties();

        return $this->resolvedFields = array_map(function (Field $field) use ($settings) {
            if ($field->isRequired() === null) {
                $type = (new ReflectionProperty($settings, $field->getName()))->getType();

                $field->required($type !== null && ! $type->allowsNull());
            }

            if (in_array($field->getName(), $this->locked, true)) {
                $field->disabled();
            }

            return $field;
        }, $this->fields());
    }

    public function isLocked(Field $field): bool
    {
        return in_array($field->getName(), $this->locked, true);
    }

    /**
     * The stored settings, as the form shows them.
     *
     * @return array<string, mixed>
     */
    public function getFormData(): array
    {
        $this->callHook('beforeFill');

        $data = $this->mutateFormDataBeforeFill(app($this->getSettings())->toArray());

        $this->callHook('afterFill');

        $values = [];

        foreach ($this->getFields() as $field) {
            $values[$field->getName()] = $field->formValue($data[$field->getName()] ?? null);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    public function save(Request $request): void
    {
        abort_unless($this->canEdit(), 403);

        $fields = array_filter($this->getFields(), fn (Field $field) => ! $field->isDisabled());

        DB::transaction(function () use ($request, $fields) {
            $this->callHook('beforeValidate');

            $request->validate(
                collect($fields)->mapWithKeys(fn (Field $field) => [$field->getName() => $field->getRules()])->all(),
                [],
                collect($fields)->mapWithKeys(fn (Field $field) => [$field->getName() => $field->getLabel()])->all(),
            );

            $this->callHook('afterValidate');

            $data = [];

            foreach ($fields as $field) {
                $value = $field->dehydrate($request->input($field->getName()));

                if ($field->getType() === 'password' && $value === null) {
                    continue;
                }

                $data[$field->getName()] = $this->castToProperty($field->getName(), $value);
            }

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $settings = app($this->getSettings());

            $settings->fill($data);
            $settings->save();

            $this->callHook('afterSave');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    /**
     * Converts a submitted value to the type the settings property declares.
     */
    protected function castToProperty(string $name, mixed $value): mixed
    {
        $type = (new ReflectionProperty($this->getSettings(), $name))->getType();

        if ($value === null || ! $type instanceof ReflectionNamedType) {
            return $value;
        }

        $class = $type->getName();

        return match (true) {
            $class === 'int' => (int) $value,
            $class === 'float' => (float) $value,
            $class === 'bool' => (bool) $value,
            $class === 'string' => (string) $value,
            is_subclass_of($class, BackedEnum::class) => $class::from($value),
            enum_exists($class) => constant("{$class}::{$value}"),
            $class === DateTimeInterface::class => new DateTimeImmutable($value),
            is_a($class, DateTimeInterface::class, true) => new $class($value),
            // spatie/laravel-data objects, and anything else built the same way.
            is_array($value) && method_exists($class, 'from') => $class::from($value),
            default => $value,
        };
    }

    public function getSavedNotificationTitle(): ?string
    {
        return __('settings-ui::ui.saved');
    }

    public function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function callHook(string $hook): void
    {
        if (method_exists($this, $hook)) {
            $this->{$hook}();
        }
    }
}

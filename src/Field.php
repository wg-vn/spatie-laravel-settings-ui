<?php

namespace WgVn\SettingsUi;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

/**
 * One input on a settings page, named after the settings property it edits.
 *
 * Stands in for Filament's form components. It only describes the input: the
 * page converts what comes back to the property's declared type, so a number
 * field works the same whether the property is an int or a float.
 */
class Field
{
    protected ?string $label = null;

    protected ?string $help = null;

    protected ?string $placeholder = null;

    /**
     * Null until set, so the page can require whatever the property does not
     * allow to be null.
     */
    protected ?bool $required = null;

    protected bool $disabled = false;

    protected bool $integer = false;

    /** @var array<int, mixed> */
    protected array $rules = [];

    /** @var array<string, string>|class-string<UnitEnum> */
    protected array|string $options = [];

    /** @var array<string, string|int|float> */
    protected array $attributes = [];

    final public function __construct(
        protected string $type,
        protected string $name,
    ) {}

    public static function make(string $type, string $name): static
    {
        return new static($type, $name);
    }

    public static function text(string $name): static
    {
        return static::make('text', $name);
    }

    public static function email(string $name): static
    {
        return static::make('email', $name);
    }

    /**
     * Never shows the stored value, and leaving it blank keeps it.
     */
    public static function password(string $name): static
    {
        return static::make('password', $name);
    }

    public static function url(string $name): static
    {
        return static::make('url', $name);
    }

    public static function tel(string $name): static
    {
        return static::make('tel', $name);
    }

    public static function number(string $name): static
    {
        return static::make('number', $name);
    }

    public static function textarea(string $name): static
    {
        return static::make('textarea', $name);
    }

    public static function toggle(string $name): static
    {
        return static::make('toggle', $name);
    }

    public static function select(string $name): static
    {
        return static::make('select', $name);
    }

    public static function date(string $name): static
    {
        return static::make('date', $name);
    }

    public static function datetime(string $name): static
    {
        return static::make('datetime', $name);
    }

    public static function color(string $name): static
    {
        return static::make('color', $name);
    }

    /**
     * Edits an array property as JSON.
     */
    public static function json(string $name): static
    {
        return static::make('json', $name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function help(string $help): static
    {
        $this->help = $help;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @param  array<int, mixed>|string  $rules
     */
    public function rules(array|string $rules): static
    {
        $this->rules = array_merge($this->rules, is_string($rules) ? explode('|', $rules) : $rules);

        return $this;
    }

    /**
     * @param  array<string, string>|class-string<UnitEnum>  $options  value => label, or an enum class
     */
    public function options(array|string $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function integer(): static
    {
        $this->integer = true;
        $this->attributes['step'] = 1;

        return $this;
    }

    public function min(int|float $min): static
    {
        $this->attributes['min'] = $min;

        return $this;
    }

    public function max(int|float $max): static
    {
        $this->attributes['max'] = $max;

        return $this;
    }

    public function step(int|float|string $step): static
    {
        $this->attributes['step'] = $step;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::ucfirst(str_replace('_', ' ', Str::snake($this->name)));
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isFullWidth(): bool
    {
        return in_array($this->type, ['textarea', 'json'], true);
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        if (is_array($this->options)) {
            return $this->options;
        }

        return collect($this->options::cases())->mapWithKeys(fn (UnitEnum $case) => [
            ($case instanceof BackedEnum ? $case->value : $case->name) => method_exists($case, 'getLabel')
                ? $case->getLabel()
                : Str::headline($case->name),
        ])->all();
    }

    /**
     * The input type attribute for fields rendered as a plain <input>.
     */
    public function getInputType(): string
    {
        return match ($this->type) {
            'datetime' => 'datetime-local',
            default => $this->type,
        };
    }

    /**
     * A settings value as the input should show it.
     */
    public function formValue(mixed $value): mixed
    {
        if ($this->type === 'password') {
            return null;
        }

        if ($value instanceof UnitEnum) {
            return $value instanceof BackedEnum ? $value->value : $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->type === 'date' ? 'Y-m-d' : 'Y-m-d\TH:i');
        }

        if ($this->type === 'json') {
            return $value === null ? null : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($this->type === 'toggle') {
            return (bool) $value;
        }

        return $value;
    }

    /**
     * Submitted input decoded into a value, before it is cast to the property type.
     */
    public function dehydrate(mixed $input): mixed
    {
        return match (true) {
            $this->type === 'toggle' => (bool) $input,
            $input === null || $input === '' => null,
            $this->type === 'json' => json_decode($input, true),
            default => $input,
        };
    }

    /**
     * @return array<int, mixed>
     */
    public function getRules(): array
    {
        if ($this->type === 'toggle') {
            return ['boolean', ...$this->rules];
        }

        // Blank keeps the stored value, so it can never be required.
        $presence = $this->required && $this->type !== 'password' ? 'required' : 'nullable';

        $rules = match ($this->type) {
            'email' => ['string', 'email'],
            'url' => ['string', 'url'],
            'number' => [$this->integer ? 'integer' : 'numeric'],
            'date', 'datetime' => ['date'],
            'color' => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'json' => ['json'],
            'select' => [Rule::in(array_map('strval', array_keys($this->getOptions())))],
            default => ['string'],
        };

        if ($this->type === 'number') {
            foreach (['min', 'max'] as $bound) {
                if (isset($this->attributes[$bound])) {
                    $rules[] = "{$bound}:{$this->attributes[$bound]}";
                }
            }
        }

        return [$presence, ...$rules, ...$this->rules];
    }
}

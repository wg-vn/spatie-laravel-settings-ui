<?php

namespace WgVn\SettingsUi\Support;

use DateTimeInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;
use WgVn\SettingsUi\Field;

/**
 * Guesses a form from the public properties of a settings class.
 *
 * Each guess is a field type plus the modifiers to call on it, so the same
 * guess can build fields at runtime or be written out as code by
 * `make:settings-ui-page --generate`.
 */
class FieldGuesser
{
    /**
     * @param  class-string<Settings>  $settings
     * @return array<string, array{type: string, modifiers: array<string, array<int, mixed>>}>
     */
    public static function guess(string $settings): array
    {
        $encrypted = $settings::encrypted();
        $guesses = [];

        foreach ((new ReflectionClass($settings))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            $type = $property->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
            $modifiers = [];

            $fieldType = match (true) {
                $typeName === 'bool' => 'toggle',
                $typeName === 'array' => 'json',
                $typeName !== null && is_a($typeName, DateTimeInterface::class, true) => 'datetime',
                $typeName !== null && enum_exists($typeName) => 'select',
                $typeName === 'int', $typeName === 'float' => 'number',
                in_array($name, $encrypted, true)
                    || $property->getAttributes(ShouldBeEncrypted::class)
                    || str_contains($name, 'password') => 'password',
                str_contains($name, 'email') => 'email',
                str_contains($name, 'phone') || str_contains($name, 'tel') => 'tel',
                str_contains($name, 'url') => 'url',
                default => 'text',
            };

            if ($fieldType === 'select') {
                $modifiers['options'] = [$typeName];
            }

            if ($typeName === 'int') {
                $modifiers['integer'] = [];
            }

            if ($fieldType !== 'toggle' && $type !== null && ! $type->allowsNull()) {
                $modifiers['required'] = [];
            }

            $guesses[$name] = ['type' => $fieldType, 'modifiers' => $modifiers];
        }

        return $guesses;
    }

    /**
     * @param  class-string<Settings>  $settings
     * @return array<int, Field>
     */
    public static function fields(string $settings): array
    {
        $fields = [];

        foreach (static::guess($settings) as $name => $guess) {
            $field = Field::make($guess['type'], $name);

            foreach ($guess['modifiers'] as $method => $arguments) {
                $field->{$method}(...$arguments);
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * The guessed fields as PHP source, one array element per field.
     *
     * @param  class-string<Settings>  $settings
     */
    public static function code(string $settings, string $indent = '            '): string
    {
        $lines = [];

        foreach (static::guess($settings) as $name => $guess) {
            $line = "Field::{$guess['type']}(" . var_export($name, true) . ')';

            foreach ($guess['modifiers'] as $method => $arguments) {
                $line .= "\n{$indent}    ->{$method}(" . implode(', ', array_map(
                    fn ($argument) => is_string($argument) && enum_exists($argument)
                        ? '\\' . ltrim($argument, '\\') . '::class'
                        : var_export($argument, true),
                    $arguments
                )) . ')';
            }

            $lines[] = $indent . $line . ',';
        }

        return implode("\n", $lines);
    }
}

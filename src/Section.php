<?php

namespace WgVn\SettingsUi;

/**
 * A titled group of fields on a settings page.
 *
 * Only affects layout: each section renders under its own heading with its own
 * grid, and the page still saves and validates the flat list of fields.
 */
class Section
{
    protected ?string $description = null;

    /**
     * @param  array<int, Field>  $fields
     */
    final public function __construct(
        protected ?string $heading,
        protected array $fields,
    ) {}

    /**
     * @param  array<int, Field>  $fields
     */
    public static function make(?string $heading, array $fields): static
    {
        return new static($heading, $fields);
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}

<?php

namespace WgVn\SettingsUi\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\LaravelSettings\SettingsContainer;
use WgVn\SettingsUi\SettingsPage;

/**
 * Every settings page, keyed by slug and in navigation order.
 *
 * Custom pages come from `settings-ui.pages.classes` and the discovery
 * directory. Each remaining settings class known to Spatie gets a generated one.
 */
class PageRegistry
{
    /**
     * @var Collection<string, SettingsPage>|null
     */
    protected ?Collection $pages = null;

    /**
     * @return Collection<string, SettingsPage>
     */
    public function all(): Collection
    {
        if ($this->pages !== null) {
            return $this->pages;
        }

        $pages = collect(config('settings-ui.pages.classes', []))
            ->merge($this->discover())
            ->unique()
            ->map(fn (string $class) => app($class));

        if (config('settings-ui.pages.auto', true)) {
            $covered = $pages->map(fn (SettingsPage $page) => $page->getSettings());

            $pages = $pages->merge(
                app(SettingsContainer::class)->getSettingClasses()
                    ->reject(fn (string $settings) => $covered->contains($settings))
                    ->map(fn (string $settings) => SettingsPage::for($settings))
            );
        }

        $excluded = config('settings-ui.pages.exclude', []);

        return $this->pages = $pages
            ->reject(fn (SettingsPage $page) => in_array($page->getSettings(), $excluded, true))
            ->sortBy([
                fn (SettingsPage $a, SettingsPage $b) => $a->getSort() <=> $b->getSort(),
                fn (SettingsPage $a, SettingsPage $b) => strcasecmp($a->getTitle(), $b->getTitle()),
            ])
            ->keyBy(fn (SettingsPage $page) => $page->getSlug());
    }

    /**
     * @return Collection<string, SettingsPage>
     */
    public function accessible(): Collection
    {
        return $this->all()->filter(fn (SettingsPage $page) => $page->canAccess());
    }

    public function find(string $slug): ?SettingsPage
    {
        return $this->all()->get($slug);
    }

    /**
     * SettingsPage subclasses in the discovery directory, which must sit
     * inside app_path() so a class name can be derived from each file.
     *
     * @return array<int, class-string<SettingsPage>>
     */
    protected function discover(): array
    {
        $path = config('settings-ui.pages.discover');

        if (! $path || ! is_dir($path)) {
            return [];
        }

        $classes = [];

        foreach (File::allFiles($path) as $file) {
            $class = app()->getNamespace() . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($class, SettingsPage::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}

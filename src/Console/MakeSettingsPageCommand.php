<?php

namespace WgVn\SettingsUi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\SettingsContainer;
use WgVn\SettingsUi\Support\FieldGuesser;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

class MakeSettingsPageCommand extends Command
{
    protected $signature = 'make:settings-ui-page
        {name? : The name of the page class, optionally prefixed with directories}
        {settings? : The settings class, relative to App\Settings or fully-qualified}
        {--G|generate : Generate the fields from the properties of the settings class}
        {--F|force : Overwrite the page if it already exists}';

    protected $description = 'Create a new Settings UI page class';

    public function handle(): int
    {
        $name = (string) Str::of($this->argument('name') ?? text(
            label: 'What is the page name?',
            placeholder: 'ManageGeneral',
            required: true,
        ))->trim('/\\ ')->replace('/', '\\')->studly();

        $settings = $this->settingsClass();

        if ($settings === null) {
            $this->components->error('The settings class must exist and extend ' . Settings::class . '.');

            return static::FAILURE;
        }

        $generate = $this->option('generate') || ($this->input->isInteractive() && confirm(
            label: 'Should the fields be generated from the properties of the settings class?',
            default: false,
        ));

        $directory = config('settings-ui.pages.discover') ?: app_path('SettingsUi');
        $path = $directory . '/' . str_replace('\\', '/', $name) . '.php';

        if (! $this->option('force') && File::exists($path)) {
            $this->components->error("Page [{$path}] already exists.");

            return static::FAILURE;
        }

        $namespace = app()->getNamespace() . str_replace(
            '/',
            '\\',
            trim(Str::after(str_replace('\\', '/', dirname($path)), str_replace('\\', '/', app_path())), '/')
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->render($namespace, class_basename($name), $settings, $generate));

        $this->components->info("Settings page [{$namespace}\\" . class_basename($name) . '] created successfully.');

        return static::SUCCESS;
    }

    /**
     * @return class-string<Settings>|null
     */
    protected function settingsClass(): ?string
    {
        $settings = trim(str_replace('/', '\\', (string) $this->argument('settings')), '\\ ');

        if ($settings === '') {
            $known = app(SettingsContainer::class)->getSettingClasses()->values()->all();

            $settings = suggest(
                label: 'What is the settings class?',
                options: $known,
                placeholder: app()->getNamespace() . 'Settings\\GeneralSettings',
                required: true,
                hint: 'Please provide the fully-qualified class name.',
            );
        }

        foreach ([app()->getNamespace() . 'Settings\\' . $settings, $settings] as $candidate) {
            if (is_subclass_of($candidate, Settings::class)) {
                return ltrim($candidate, '\\');
            }
        }

        return null;
    }

    /**
     * @param  class-string<Settings>  $settings
     */
    protected function render(string $namespace, string $class, string $settings, bool $generate): string
    {
        $settingsBasename = class_basename($settings);

        $code = $generate ? FieldGuesser::code($settings) : '';

        $fields = $generate
            ? <<<PHP


                public function fields(): array
                {
                    return [
            {$code}
                    ];
                }
            PHP
            : '';

        $fieldImport = $generate ? "use WgVn\\SettingsUi\\Field;\n" : '';

        return <<<PHP
            <?php

            namespace {$namespace};

            use {$settings};
            {$fieldImport}use WgVn\\SettingsUi\\SettingsPage;

            class {$class} extends SettingsPage
            {
                protected string \$settings = {$settingsBasename}::class;{$fields}
            }

            PHP;
    }
}

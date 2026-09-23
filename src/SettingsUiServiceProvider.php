<?php

namespace WgVn\SettingsUi;

use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use WgVn\SettingsUi\Console\MakeSettingsPageCommand;
use WgVn\SettingsUi\Http\Middleware\SettingsUiAccessMiddleware;
use WgVn\SettingsUi\Support\PageRegistry;

class SettingsUiServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

    protected static ?Closure $defaultGate = null;

    /**
     * Any signed-in user locally. Anywhere else, only once the application has
     * said who may in: its own gate replaces this one, or the allow-lists are
     * set, which the access middleware then enforces. The UI can change every
     * setting, and most applications let anyone sign up.
     */
    public static function defaultGate(): Closure
    {
        return static::$defaultGate ??= fn ($user = null) => $user !== null && (
            app()->environment('local')
            || config('settings-ui.access.allowed_users')
            || config('settings-ui.access.allowed_roles')
        );
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/settings-ui.php', 'settings-ui');

        // Pages decide access per user, so the registry lives for one request.
        $this->app->scoped(PageRegistry::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'settings-ui');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'settings-ui');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        // Defined only when the application has not defined its own.
        if (! Gate::has('viewSettingsUi')) {
            Gate::define('viewSettingsUi', static::defaultGate());
        }

        $this->app['router']->aliasMiddleware('settings-ui-access', SettingsUiAccessMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->commands([MakeSettingsPageCommand::class]);

            $this->publishes([
                __DIR__ . '/config/settings-ui.php' => config_path('settings-ui.php'),
            ], 'settings-ui-config');

            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/vendor/settings-ui'),
            ], 'settings-ui-views');

            $this->publishes([
                __DIR__ . '/resources/lang' => $this->app->langPath('vendor/settings-ui'),
            ], 'settings-ui-translations');
        }
    }
}

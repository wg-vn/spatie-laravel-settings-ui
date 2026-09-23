<?php

namespace WgVn\SettingsUi\Support;

use WgVn\SettingsUi\Http\Middleware\AuthenticateSettingsUser;
use WgVn\SettingsUi\Http\Middleware\SettingsUiAccessMiddleware;

/**
 * Builds the route middleware stack for the UI.
 *
 * The host may replace the base stack, but authentication and the access
 * checks are appended on top and cannot be removed by it.
 */
class RouteMiddleware
{
    /**
     * The package's own entries, and the alias it registers for one of them.
     */
    protected const OWN = [
        AuthenticateSettingsUser::class,
        SettingsUiAccessMiddleware::class,
        'settings-ui-access',
    ];

    /**
     * Append authentication and the access checks to a configured stack.
     *
     * Existing occurrences are dropped and re-appended, so both always run last
     * and in this order: access checks run before authentication would see a
     * guest and refuse with a 401 that signing in could not clear.
     *
     * @param  array<int, mixed>  $middleware
     * @return array<int, mixed>
     */
    public static function protect(array $middleware): array
    {
        $middleware = array_values(array_filter(
            $middleware,
            fn ($entry) => ! static::isOwn($entry)
        ));

        $middleware[] = AuthenticateSettingsUser::class;
        $middleware[] = SettingsUiAccessMiddleware::class;

        return $middleware;
    }

    protected static function isOwn(mixed $entry): bool
    {
        return is_string($entry) && in_array(explode(':', $entry, 2)[0], static::OWN, true);
    }
}

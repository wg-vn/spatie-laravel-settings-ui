<?php

namespace WgVn\SettingsUi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use WgVn\SettingsUi\SettingsUiServiceProvider;

class SettingsUiAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowedUsers = config('settings-ui.access.allowed_users', []);
        $allowedRoles = config('settings-ui.access.allowed_roles', []);

        if (config('settings-ui.authorization.enabled', true)) {
            $gate = config('settings-ui.authorization.gate', 'viewSettingsUi');

            if (Gate::forUser($user)->denies($gate)) {
                // A fresh install outside `local` denies everyone until access
                // is configured, which should not look like a bug.
                abort(403, $this->isUnconfigured($gate)
                    ? 'Settings UI access is not configured. Define the viewSettingsUi gate, or set settings-ui.access.allowed_users or allowed_roles.'
                    : 'Unauthorized access to Settings UI.');
            }
        } elseif (empty($allowedUsers) && empty($allowedRoles)) {
            // Authorization off and no allow-lists: the UI is public.
            return $next($request);
        } elseif ($user === null) {
            abort(401, 'Authentication required for Settings UI.');
        }

        // The allow-lists narrow access whether or not authorization is enabled.
        if (! empty($allowedUsers) && ! in_array($user?->email, $allowedUsers, true)) {
            abort(403, 'User not allowed to access Settings UI.');
        }

        if (! empty($allowedRoles) && ! $this->hasAnyAllowedRole($user, $allowedRoles)) {
            abort(403, 'User role not allowed to access Settings UI.');
        }

        return $next($request);
    }

    /**
     * Whether the denial came from the package's default gate with nothing set
     * up, rather than from the application's own rules.
     */
    protected function isUnconfigured(string $gate): bool
    {
        return (Gate::abilities()[$gate] ?? null) === SettingsUiServiceProvider::defaultGate()
            && ! app()->environment('local')
            && empty(config('settings-ui.access.allowed_users'))
            && empty(config('settings-ui.access.allowed_roles'));
    }

    /**
     * Whether the user holds one of the allowed roles.
     *
     * Roles come from whatever package the host uses, so the method may not
     * exist at all. A user who cannot be shown to hold an allowed role is
     * denied rather than turned into a 500.
     *
     * @param  array<int, string>  $allowedRoles
     */
    protected function hasAnyAllowedRole(mixed $user, array $allowedRoles): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return (bool) $user->hasAnyRole($allowedRoles);
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        return false;
    }
}

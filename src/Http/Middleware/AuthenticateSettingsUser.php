<?php

namespace WgVn\SettingsUi\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Runs whatever the application means by 'auth', on every request.
 *
 * Resolved at request time rather than decided when routes are registered:
 * middleware aliases live on the router, which `route:cache` boots without, so
 * inspecting them at registration produced different stacks cached and live.
 * Resolving the alias also keeps a host subclass that rejects suspended or
 * unverified accounts, and its unauthenticated() override.
 */
class AuthenticateSettingsUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $middleware = $this->hostAuthentication();

        if ($middleware === null) {
            return $next($request);
        }

        try {
            return $middleware->handle($request, $next, ...$this->guards());
        } catch (RouteNotFoundException $e) {
            // Laravel redirects guests to route('login'), which a fresh
            // application without auth scaffolding does not have. Refuse them
            // plainly instead of with a 500. Only while still a guest, so a
            // missing route further down the stack is not hidden.
            if ($request->user() !== null) {
                throw $e;
            }

            abort(401, 'Authentication required for Settings UI.');
        }
    }

    /**
     * The application's own 'auth' middleware, or the framework's if it has not
     * aliased one.
     */
    protected function hostAuthentication(): ?object
    {
        $class = Authenticate::class;

        try {
            $aliased = app('router')->getMiddleware()['auth'] ?? null;

            if (is_string($aliased) && class_exists($aliased)) {
                $class = $aliased;
            }
        } catch (\Throwable $e) {
            // No router, or no alias map: fall through to the framework class.
        }

        try {
            $middleware = app($class);
        } catch (\Throwable $e) {
            // An alias pointing at something unconstructable must not take the
            // page down; the access middleware behind this still refuses guests.
            return null;
        }

        return method_exists($middleware, 'handle') ? $middleware : null;
    }

    /**
     * Guards from authorization.guard; empty means the default guard.
     *
     * @return array<int, string>
     */
    protected function guards(): array
    {
        $guard = config('settings-ui.authorization.guard');

        return array_values(array_filter(is_array($guard) ? $guard : [$guard], 'is_string'));
    }
}

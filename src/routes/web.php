<?php

use Illuminate\Support\Facades\Route;
use WgVn\SettingsUi\Http\Controllers\AssetController;
use WgVn\SettingsUi\Http\Controllers\SettingsController;
use WgVn\SettingsUi\Support\RouteMiddleware;

$config = config('settings-ui.route', []);
$prefix = $config['prefix'] ?? 'settings-ui';
$name = $config['name'] ?? 'settings-ui.';
$domain = $config['domain'] ?? null;

// A custom stack replaces the base middleware only, so 'web' can be swapped
// for another group or a tenancy layer added.
$middleware = $config['middleware'] ?? ['web'];

// Authentication and the access checks are appended and not overridable. The
// fallback is true so a missing or partial config fails closed. Allow-lists
// configured with authorization off still need a signed-in user to match.
if (
    config('settings-ui.authorization.enabled', true)
    || config('settings-ui.access.allowed_users')
    || config('settings-ui.access.allowed_roles')
) {
    $middleware = RouteMiddleware::protect($middleware);
}

// Outside the protected group on purpose: a guest is redirected to the host's
// login page, and a stylesheet behind auth would leave that page unstyled. The
// version is a content hash, so an upgraded package is a new URL.
Route::get($prefix . '/assets/{version}/settings-ui.css', [AssetController::class, 'stylesheet'])
    ->name($name . 'assets.css')
    ->domain($domain);

Route::group([
    'prefix' => $prefix,
    'as' => $name,
    'middleware' => $middleware,
    'domain' => $domain,
], function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::get('{page}', [SettingsController::class, 'show'])->name('show');
    Route::put('{page}', [SettingsController::class, 'update'])->name('update');
});

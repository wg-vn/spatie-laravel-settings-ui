<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings UI Configuration
    |--------------------------------------------------------------------------
    |
    | An admin UI for spatie/laravel-settings. Every settings class that Spatie
    | knows about (its `settings.settings` list and `auto_discover_settings`
    | paths) gets a page, with a form guessed from the class's properties.
    | Extend WgVn\SettingsUi\SettingsPage to take control of a page.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | 'middleware' replaces the base stack (['web']) only. Authentication and
    | the access middleware are appended afterwards and cannot be removed by it.
    |
    */
    'route' => [
        'prefix' => 'settings-ui',
        'name' => 'settings-ui.',
        'middleware' => null,
        'domain' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled=true:  requires authentication, then the gate below.
    | When enabled=false: the entire UI is public. Anyone who can reach the URL
    |                     can read and change your application's settings.
    |
    | The default gate allows any authenticated user in the `local`
    | environment only. Anywhere else it denies everyone until you set the
    | access.allowed_users / access.allowed_roles lists, or define your own
    | `viewSettingsUi` gate to replace it. Turn authorization off only for
    | local development.
    |
    */
    'authorization' => [
        'enabled' => env('SETTINGS_UI_AUTHORIZATION', true),
        'gate' => 'viewSettingsUi',
        'guard' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control Configuration
    |--------------------------------------------------------------------------
    |
    | Enforced regardless of authorization.enabled: if either list is set, a
    | matching logged-in user is required.
    |
    */
    'access' => [
        // User emails allowed to access the UI.
        'allowed_users' => [
            // 'admin@example.com',
        ],

        // Roles allowed to access the UI. Needs a user model with hasAnyRole()
        // or hasRole(), such as the one Spatie Permission provides.
        'allowed_roles' => [
            // 'admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */
    'pages' => [
        // SettingsPage subclasses to register explicitly.
        'classes' => [
            // App\SettingsUi\ManageGeneral::class,
        ],

        // Directory scanned for SettingsPage subclasses, and where
        // `make:settings-ui-page` writes them. Classes are resolved against the
        // application namespace. Set to null to disable discovery.
        'discover' => app_path('SettingsUi'),

        // Generate a page for every settings class without one of its own.
        'auto' => true,

        // Settings classes that never get a page, custom or generated.
        'exclude' => [
            // App\Settings\InternalSettings::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'brand' => 'Settings',
        'logo' => null,

        // Shown as a link in the header, e.g. back to your own admin panel.
        'back_url' => null,
    ],
];

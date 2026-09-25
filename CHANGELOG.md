# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-09-25

### Added
- `Field::secret()`: shows the stored value masked, with the show/hide button. Leaving it blank clears the value.
- `sensitive_field` config option: set it to `'secret'` so generated pages use secret fields for encrypted and password-named properties. The default stays `'password'`.

### Changed
- Settings pages are sent with `Cache-Control: no-store, private`.

## [1.3.0] - 2026-09-24

### Added
- Password fields have a button to show or hide what was typed. They are masked again on save.

## [1.1.0] - 2026-09-23

### Added
- `Field::tags()`: edits an array property as removable chips, with `itemType('email')` / `itemType('url')` to validate each item.

### Changed
- Email fields reject addresses without a dotted domain, such as `user@localhost` (`email:rfc,filter` instead of `email`).

## [1.0.0] - 2026-09-23

### Added
- Initial release: a port of `filament/spatie-laravel-settings-plugin` to plain Blade views, with no Filament or Livewire dependency.
- A page at `/settings-ui` for every settings class registered with or discovered by `spatie/laravel-settings`, with fields guessed from its property types.
- `SettingsPage` base class for custom pages: `fields()`, `canAccess()`, `canEdit()`, `mutateFormDataBeforeFill()`, `mutateFormDataBeforeSave()`, before/after fill, validate and save hooks, `getSavedNotificationTitle()` and `getRedirectUrl()`.
- `Field` definitions: text, email, password, url, tel, number, textarea, toggle, select (arrays or enums), date, datetime, color and JSON.
- `make:settings-ui-page` command, with `--generate` to write the fields from the settings class.
- Authentication, a `viewSettingsUi` gate and user/role allow-lists, on by default. Outside the `local` environment the default gate denies everyone until an allow-list is set or the application defines its own gate.
- Translations for the save button and saved message in every locale the Filament plugin shipped.

[1.3.0]: https://github.com/wg-vn/spatie-laravel-settings-ui/releases/tag/v1.3.0
[1.1.0]: https://github.com/wg-vn/spatie-laravel-settings-ui/releases/tag/v1.1.0
[1.0.0]: https://github.com/wg-vn/spatie-laravel-settings-ui/releases/tag/v1.0.0

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('settings-ui.ui.brand', 'Settings') }}</title>

    {{-- Served by the package against a content hash: no build step for the
         host and no CSS generated in the browser. --}}
    <link rel="stylesheet" href="{{ route('settings-ui.assets.css', ['version' => \WgVn\SettingsUi\Http\Controllers\AssetController::version()]) }}">

    <script>
        // Applied before first paint so the page never flashes light then dark.
        (function () {
            try {
                var stored = localStorage.getItem('darkMode');
                var on = stored === null
                    ? window.matchMedia('(prefers-color-scheme: dark)').matches
                    : stored === 'true';
                document.documentElement.classList.toggle('dark', on);
            } catch (e) {}
        })();
    </script>

    @stack('head')
</head>
<body>
<div class="su-shell">
    <header class="su-header">
        <div class="su-container su-header__inner">
            <a href="{{ route('settings-ui.index') }}" class="su-brand">
                @if(config('settings-ui.ui.logo'))
                    <img src="{{ config('settings-ui.ui.logo') }}" alt="" style="height:1.625rem;width:auto;flex:none">
                @else
                    <span class="su-brand__mark" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/>
                            <circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>
                        </svg>
                    </span>
                @endif
                <span class="su-brand__text">{{ config('settings-ui.ui.brand', 'Settings') }}</span>
            </a>

            <div class="su-header__spacer"></div>

            @if(config('settings-ui.ui.back_url'))
                <a href="{{ config('settings-ui.ui.back_url') }}" class="su-btn su-btn--ghost">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span class="su-hide-sm">{{ __('settings-ui::ui.back') }}</span>
                </a>
            @endif

            <button type="button" class="su-btn su-btn--ghost su-btn--icon" data-dark-toggle aria-label="{{ __('settings-ui::ui.toggle_dark_mode') }}">
                <svg class="su-icon-moon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <svg class="su-icon-sun" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
                </svg>
            </button>

            @auth
                <span class="su-user">
                    <span class="su-user__name su-hide-sm">{{ auth()->user()->name ?? auth()->user()->email }}</span>
                    <span class="su-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email ?? '?', 0, 1)) }}</span>
                </span>
                {{-- Guarded: not every application names its logout route 'logout'. --}}
                @if(Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="su-btn su-btn--ghost su-btn--icon" aria-label="{{ __('settings-ui::ui.sign_out') }}" title="{{ __('settings-ui::ui.sign_out') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                            </svg>
                        </button>
                    </form>
                @endif
            @endauth
        </div>
    </header>

    <main class="su-main">
        <div class="su-container su-layout @if($navigation->isNotEmpty()) su-layout--with-nav @endif">
            @if($navigation->isNotEmpty())
                <nav class="su-nav" aria-label="{{ __('settings-ui::ui.navigation') }}">
                    <p class="su-nav__heading">{{ __('settings-ui::ui.navigation') }}</p>
                    @foreach($navigation as $item)
                        <a href="{{ route('settings-ui.show', $item->getSlug()) }}"
                           class="su-nav__link"
                           @if(isset($page) && $page->getSlug() === $item->getSlug()) aria-current="page" @endif>
                            {{ $item->getTitle() }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <div class="su-content">
                @yield('content')
            </div>
        </div>
    </main>
</div>

@if(session('settings-ui.saved'))
    <div class="su-toasts" role="status" aria-live="polite">
        <div class="su-toast" data-type="success" data-toast>
            <p class="su-toast__title su-grow">{{ session('settings-ui.saved') }}</p>
            <button type="button" class="su-btn su-btn--ghost su-btn--icon su-btn--sm" data-toast-dismiss aria-label="{{ __('settings-ui::ui.dismiss') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
@endif

<script>
    (function () {
        document.querySelector('[data-dark-toggle]')?.addEventListener('click', function () {
            var on = document.documentElement.classList.toggle('dark');

            try {
                localStorage.setItem('darkMode', on ? 'true' : 'false');
            } catch (e) {}
        });

        document.querySelectorAll('[data-toast]').forEach(function (toast) {
            var remove = function () { toast.remove(); };
            toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', remove);
            setTimeout(remove, 5000);
        });

        var form = document.querySelector('form[data-settings-form]');

        if (!form) {
            return;
        }

        // Warn before leaving with unsaved changes, as Filament does.
        var dirty = false;
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (event) {
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        // Ctrl/Cmd+S saves.
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's' && form.querySelector('[type=submit]')) {
                event.preventDefault();
                form.requestSubmit();
            }
        });
    })();
</script>

@stack('scripts')
</body>
</html>

@extends('settings-ui::layouts.app')

@section('title', $page->getTitle())

@section('content')
    <form method="POST"
          action="{{ route('settings-ui.update', $page->getSlug()) }}"
          class="su-card"
          data-settings-form
          novalidate>
        @csrf
        @method('PUT')

        <div class="su-card__header">
            <h1 class="su-card__title">{{ $page->getTitle() }}</h1>
            @unless($canEdit)
                <span class="su-badge">{{ __('settings-ui::ui.read_only') }}</span>
            @endunless
        </div>

        <div class="su-card__body su-stack su-stack--lg">
            @unless($canEdit)
                <p class="su-note">{{ __('settings-ui::ui.read_only_help') }}</p>
            @endunless

            @if($errors->any())
                <p class="su-note su-note--danger" role="alert">{{ __('settings-ui::ui.errors') }}</p>
            @endif

            {{-- A disabled fieldset disables every control in it, as Filament's
                 disabled form does when canEdit() is false. --}}
            <fieldset class="su-form-grid" @disabled(! $canEdit)>
                @foreach($fields as $field)
                    @include('settings-ui::fields.field', [
                        'field' => $field,
                        'value' => $data[$field->getName()] ?? null,
                        'locked' => $page->isLocked($field),
                    ])
                @endforeach
            </fieldset>
        </div>

        @if($canEdit)
            <div class="su-card__footer">
                <button type="submit" class="su-btn su-btn--primary">{{ __('settings-ui::ui.save') }}</button>
            </div>
        @endif
    </form>
@endsection

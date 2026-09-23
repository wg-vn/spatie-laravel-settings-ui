@extends('settings-ui::layouts.app')

@section('title', __('settings-ui::ui.empty.title'))

@section('content')
    <div class="su-card su-empty">
        <p class="su-empty__title">{{ __('settings-ui::ui.empty.title') }}</p>
        <p class="su-empty__body">{{ __('settings-ui::ui.empty.body') }}</p>
    </div>
@endsection

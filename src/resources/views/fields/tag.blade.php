{{-- One chip of a tags field. Rendered empty inside a <template> for the script to clone. --}}
<span class="su-tag" data-tag @if($invalid ?? false) data-invalid @endif>
    <span class="su-tag__text" data-tag-text>{{ $item }}</span>
    <input type="hidden" name="{{ $name }}[]" value="{{ $item }}" @disabled($disabled)>
    <button type="button"
            class="su-tag__remove"
            data-tag-remove
            aria-label="{{ __('settings-ui::ui.remove_item', ['item' => $item ?? ':item']) }}"
            @disabled($disabled)>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
    </button>
</span>

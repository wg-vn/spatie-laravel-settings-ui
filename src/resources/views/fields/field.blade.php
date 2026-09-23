@php
    $name = $field->getName();
    $id = 'field-' . $name;
    $type = $field->getType();
    // Passwords are never sent back to the browser, not even from old input.
    $current = $type === 'password' ? null : old($name, $value);
    $error = $errors->first($name);
    $help = $locked
        ? __('settings-ui::ui.locked_help')
        : ($field->getHelp() ?? ($type === 'password' ? __('settings-ui::ui.password_help') : null));
    $describedBy = trim(($help ? "{$id}-help " : '') . ($error ? "{$id}-error" : ''));
@endphp

<div class="su-field @if($field->isFullWidth()) su-field--full @endif" @if($error) data-invalid @endif>
    @if($type === 'toggle')
        {{-- Unchecked boxes are not submitted, so the hidden input sends false. --}}
        <input type="hidden" name="{{ $name }}" value="0" @disabled($field->isDisabled())>
        <label class="su-toggle" for="{{ $id }}">
            <input type="checkbox"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   value="1"
                   role="switch"
                   @checked((bool) $current)
                   @disabled($field->isDisabled())
                   @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            <span class="su-toggle__track" aria-hidden="true"></span>
            <span class="su-label">{{ $field->getLabel() }}</span>
            @if($locked)
                <span class="su-badge">{{ __('settings-ui::ui.locked') }}</span>
            @endif
        </label>
    @else
        <label class="su-label" for="{{ $id }}">
            {{ $field->getLabel() }}
            @if($field->isRequired() && $type !== 'password')
                <span class="su-required" aria-hidden="true">*</span>
            @endif
            @if($locked)
                <span class="su-badge">{{ __('settings-ui::ui.locked') }}</span>
            @endif
        </label>

        @if($type === 'select')
            <select id="{{ $id }}"
                    name="{{ $name }}"
                    class="su-select"
                    @required($field->isRequired())
                    @disabled($field->isDisabled())
                    @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                @unless($field->isRequired() && filled($current))
                    <option value="">{{ $field->getPlaceholder() ?? '—' }}</option>
                @endunless
                @foreach($field->getOptions() as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif($type === 'textarea' || $type === 'json')
            <textarea id="{{ $id }}"
                      name="{{ $name }}"
                      class="su-input @if($type === 'json') su-mono @endif"
                      rows="{{ $type === 'json' ? 8 : 4 }}"
                      spellcheck="{{ $type === 'json' ? 'false' : 'true' }}"
                      @if($field->getPlaceholder()) placeholder="{{ $field->getPlaceholder() }}" @endif
                      @required($field->isRequired())
                      @disabled($field->isDisabled())
                      @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $current }}</textarea>
        @else
            <input type="{{ $field->getInputType() }}"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   class="su-input @if($type === 'color') su-input--color @endif"
                   value="{{ $current }}"
                   @if($type === 'password') autocomplete="new-password" @endif
                   @if($field->getPlaceholder()) placeholder="{{ $field->getPlaceholder() }}" @endif
                   @foreach($field->getAttributes() as $attribute => $attributeValue) {{ $attribute }}="{{ $attributeValue }}" @endforeach
                   @required($field->isRequired() && $type !== 'password')
                   @disabled($field->isDisabled())
                   @if($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        @endif
    @endif

    @if($help)
        <p class="su-help" id="{{ $id }}-help">{{ $help }}</p>
    @endif

    @if($error)
        <p class="su-error" id="{{ $id }}-error">{{ $error }}</p>
    @endif
</div>

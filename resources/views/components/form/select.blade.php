{{-- options: [value => label] veya optgroup için [grup adı => [value => label]] --}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'required' => false])

@php
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <select id="{{ $name }}" name="{{ $name }}" @required($required)
            {{ $attributes->except('class')->merge(['class' => 'form-input'.($errors->has($name) ? ' border-red-400' : '')]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $option)
            @if (is_array($option))
                <optgroup label="{{ $key }}">
                    @foreach ($option as $k => $v)
                        <option value="{{ $k }}" @selected($selected === (string) $k)>{{ $v }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $key }}" @selected($selected === (string) $key)>{{ $option }}</option>
            @endif
        @endforeach
    </select>

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

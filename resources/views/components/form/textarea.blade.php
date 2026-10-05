@props(['name', 'label' => null, 'value' => null, 'rows' => 3, 'required' => false])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->except('class')->merge(['class' => 'form-input'.($errors->has($name) ? ' border-red-400' : '')]) }}>{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

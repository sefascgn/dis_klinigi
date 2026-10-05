@props(['name', 'label', 'checked' => false, 'hint' => null])

<div {{ $attributes->only('class') }}>
    <label class="inline-flex items-center gap-2">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
               class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
        <span class="text-sm text-gray-700">{{ $label }}</span>
    </label>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

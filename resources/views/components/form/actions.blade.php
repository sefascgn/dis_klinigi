@props(['cancel', 'submit' => 'Kaydet'])

<div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ $cancel }}" class="btn-secondary">Vazgeç</a>
    <button type="submit" class="btn-primary">{{ $submit }}</button>
</div>

{{-- label() ve color() metotlarına sahip herhangi bir enum için rozet. --}}
@props(['status'])

@if ($status)
    <x-badge :color="method_exists($status, 'color') ? $status->color() : 'gray'">{{ $status->label() }}</x-badge>
@endif

@props(['disabled' => false])

@php
    $isNumber = $attributes->get('type') === 'number';
@endphp

<input
    @disabled($disabled)
    @if ($isNumber) inputmode="decimal" @endif
    {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}
>

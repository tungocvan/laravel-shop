@props([
    'for' => null,
    'message' => null,
])

@php
    $resolved = $message ?: ($for ? $errors->first($for) : null);
@endphp

@if($resolved)
    <p {{ $attributes->class(['mt-1.5 text-sm text-red-600']) }}>{{ $resolved }}</p>
@endif

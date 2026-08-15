@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'helper' => null,
    'error' => null,
])

@php
    $model = $name ?: $attributes->whereStartsWith('wire:model')->first();
    $message = $error ?: ($model ? $errors->first($model) : null);
    $hasError = filled($message);

    $base = 'w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-900 transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:text-gray-500 disabled:opacity-75';
    $state = $hasError
        ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 hover:border-gray-400 focus:border-indigo-500 focus:ring-indigo-100';
@endphp

<div>
    @if($label)
        <label @if($attributes->get('id')) for="{{ $attributes->get('id') }}" @endif class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)<span class="text-red-500" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        {{ $attributes->class([($label ? 'mt-1 ' : '').$base.' '.$state])->merge(['aria-invalid' => $hasError ? 'true' : 'false']) }}
        @if($required) required @endif
    >
        {{ $slot }}
    </select>

    @if($message)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @elseif($helper)
        <p class="mt-1.5 text-xs text-gray-500">{{ $helper }}</p>
    @endif
</div>

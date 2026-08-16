@props([
    'placeholder' => 'Tìm kiếm...',
])

<div {{ $attributes->only('class')->merge(['class' => 'relative']) }}>
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.004l3.762 3.762a1 1 0 001.414-1.414l-3.762-3.762A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
        </svg>
    </div>

    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except('class')->merge([
            'class' => 'block w-full rounded-lg border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500',
        ]) }}
    >
</div>

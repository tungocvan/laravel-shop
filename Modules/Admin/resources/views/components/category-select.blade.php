@props(['categories' => [], 'label' => 'Danh mục', 'selected' => []])

@php
    $model = $attributes->wire('model')->value();
    $hasError = $model && $errors->has($model);
@endphp

<div class="mb-5">
    <label class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>

    <div @class([
        'max-h-80 overflow-y-auto rounded-xl border bg-white shadow-sm transition focus-within:ring-2 custom-scrollbar',
        'border-red-400 focus-within:border-red-500 focus-within:ring-red-100' => $hasError,
        'border-gray-300 hover:border-gray-400 focus-within:border-indigo-500 focus-within:ring-indigo-100' => ! $hasError,
    ])>
        @forelse($categories as $category)
            @include('Admin::components.category-select-row', [
                'category' => $category,
                'depth' => 0,
                'wireModel' => $attributes->wire('model'),
            ])
        @empty
            <div class="flex flex-col items-center justify-center px-4 py-8 text-gray-400">
                <p class="text-sm">Chưa có danh mục nào.</p>
            </div>
        @endforelse
    </div>

    @if($hasError)
        <p class="mt-1.5 text-sm text-red-600">{{ $errors->first($model) }}</p>
    @endif
</div>

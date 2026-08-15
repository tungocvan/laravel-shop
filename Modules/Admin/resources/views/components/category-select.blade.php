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
        @forelse($categories as $parent)
            <div class="border-b border-gray-100 last:border-0">
                <label class="group flex cursor-pointer select-none items-center space-x-3 px-4 py-3 transition hover:bg-gray-50">
                    <input type="checkbox" value="{{ $parent->id }}" {{ $attributes->wire('model') }} class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700">{{ $parent->name }}</span>
                </label>

                @if($parent->children->isNotEmpty())
                    <div class="bg-gray-50/60 pb-1">
                        @foreach($parent->children as $child)
                            <label class="group relative flex cursor-pointer select-none items-center space-x-3 px-4 py-2.5 pl-10 transition hover:bg-indigo-50/60">
                                <div class="absolute left-6 top-1/2 h-px w-3 bg-gray-300"></div>
                                <input type="checkbox" value="{{ $child->id }}" {{ $attributes->wire('model') }} class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700">{{ $child->name }}</span>
                            </label>

                            @if($child->children->isNotEmpty())
                                @foreach($child->children as $grandChild)
                                    <label class="group relative flex cursor-pointer select-none items-center space-x-3 px-4 py-2 pl-16 transition hover:bg-indigo-50">
                                        <div class="absolute left-12 top-1/2 h-px w-3 bg-gray-200"></div>
                                        <input type="checkbox" value="{{ $grandChild->id }}" {{ $attributes->wire('model') }} class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs text-gray-600 group-hover:text-indigo-700">{{ $grandChild->name }}</span>
                                    </label>
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
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

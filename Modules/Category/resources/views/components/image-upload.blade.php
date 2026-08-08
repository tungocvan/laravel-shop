@props([
    'label' => 'Ảnh đại diện',
    'oldImage' => null,
    'newImage' => null,
])

@php($model = $attributes->wire('model')->value())

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
    <h3 class="mb-4 border-b pb-2 text-base font-semibold text-gray-900">{{ $label }}</h3>

    <label for="{{ $model }}-input"
        class="relative flex min-h-48 cursor-pointer items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 transition hover:bg-gray-100">
        @if ($newImage)
            <img src="{{ $newImage->temporaryUrl() }}"
                class="absolute inset-0 h-full w-full rounded-lg object-contain p-2"
                alt="Ảnh xem trước">
        @elseif ($oldImage)
            <img src="{{ Illuminate\Support\Str::startsWith($oldImage, 'http') ? $oldImage : asset('storage/'.$oldImage) }}"
                class="absolute inset-0 h-full w-full rounded-lg object-contain p-2"
                alt="Ảnh danh mục hiện tại">
        @else
            <span class="text-sm font-semibold text-indigo-600">Chọn ảnh JPG, PNG hoặc WebP</span>
        @endif

        <input id="{{ $model }}-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            {{ $attributes->wire('model') }}>
    </label>

    @if ($newImage)
        <button type="button"
            wire:click="$set('{{ $model }}', null)"
            class="mt-3 text-sm font-medium text-red-600">
            Bỏ ảnh vừa chọn
        </button>
    @endif

    <div wire:loading wire:target="{{ $model }}" class="mt-2 text-sm text-indigo-600">
        Đang xử lý ảnh...
    </div>

    @error($model)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@props([
    'label' => 'Ảnh đại diện',
    'oldImage' => null,
    'newImage' => null
])

<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4 border-b pb-2">{{ $label }}</h3>

    <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 relative bg-gray-50 hover:bg-gray-100 transition cursor-pointer group"
         onclick="document.getElementById('{{ $attributes->wire('model')->value() }}-input').click()">

        @if ($newImage)
            <img src="{{ $newImage->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-contain rounded-lg p-2 z-10">
            <button type="button"
                    wire:click.stop="$set('{{ $attributes->wire('model')->value() }}', null)"
                    class="absolute top-2 right-2 bg-white text-red-500 rounded-full p-1 shadow hover:bg-red-50 z-20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>

        @elseif ($oldImage)
            <img src="{{ Illuminate\Support\Str::startsWith($oldImage, ['http']) ? $oldImage : asset('storage/'.$oldImage) }}" class="absolute inset-0 w-full h-full object-contain rounded-lg p-2 z-10">
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition z-20 rounded-lg">
                <span class="text-white font-medium text-sm">Bấm để thay đổi</span>
            </div>

        @else
            <div class="text-center z-0">
                <svg class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                </svg>
                <div class="mt-4 flex text-sm leading-6 text-gray-600 justify-center">
                    <span class="relative rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500">
                        <span>Tải ảnh lên</span>
                    </span>
                </div>
            </div>
        @endif

        <input id="{{ $attributes->wire('model')->value() }}-input"
               type="file"
               class="hidden"
               {{ $attributes->wire('model') }}>
    </div>

    <div wire:loading wire:target="{{ $attributes->wire('model')->value() }}" class="text-xs text-blue-500 mt-2 text-center w-full font-medium animate-pulse">
        Đang xử lý ảnh...
    </div>

    @error($attributes->wire('model')->value())
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@props([
    'label' => 'Thư viện ảnh',
    'gallery' => [],        // Mảng ảnh cũ
    'newGallery' => [],     // Mảng ảnh mới upload
    'removeOldAction' => 'removeOldGallery', // Tên hàm xóa ảnh cũ ở Parent
    'removeNewAction' => 'removeNewGallery'  // Tên hàm xóa ảnh mới ở Parent
])

<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
    <div class="p-6">
        <h3 class="text-base font-semibold leading-6 text-gray-900 border-b pb-2 mb-4">{{ $label }}</h3>

        <div class="flex items-center justify-center w-full mb-6">
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <p class="text-sm text-gray-500 font-medium">Bấm để tải nhiều ảnh lên</p>
                    <p class="text-xs text-gray-400">Giữ Ctrl để chọn nhiều file</p>
                </div>
                <input {{ $attributes->wire('model') }} type="file" multiple class="hidden" />
            </label>
        </div>

        @if(count($gallery) > 0 || count($newGallery) > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">

                @foreach($gallery as $index => $img)
                    <div class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border shadow-sm">
                        <img src="{{ Illuminate\Support\Str::startsWith($img, ['http']) ? $img : asset('storage/'.$img) }}" class="w-full h-full object-cover">

                        <button type="button"
                                wire:click="{{ $removeOldAction }}({{ $index }})"
                                class="absolute top-1 right-1 bg-white/90 text-red-500 p-1.5 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all hover:bg-red-500 hover:text-white transform hover:scale-110">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endforeach

                @foreach($newGallery as $index => $img)
                    <div class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border border-indigo-500 ring-2 ring-indigo-500/20 shadow-sm">
                        <img src="{{ $img->temporaryUrl() }}" class="w-full h-full object-cover">

                        <button type="button"
                                wire:click="{{ $removeNewAction }}({{ $index }})"
                                class="absolute top-1 right-1 bg-white/90 text-red-500 p-1.5 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all hover:bg-red-500 hover:text-white transform hover:scale-110">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <div class="absolute bottom-0 inset-x-0 bg-indigo-600/90 text-white text-[10px] text-center font-bold py-1 backdrop-blur-sm">MỚI</div>
                    </div>
                @endforeach
            </div>
        @else
             <div class="text-center py-6 text-sm text-gray-400 italic bg-gray-50 rounded-lg border border-dashed border-gray-200">
                 Chưa có ảnh nào trong thư viện.
             </div>
        @endif

        <div wire:loading wire:target="{{ $attributes->wire('model')->value() }}" class="text-xs text-blue-500 mt-2 text-center w-full">
            Đang tải ảnh lên...
        </div>
    </div>
</div>

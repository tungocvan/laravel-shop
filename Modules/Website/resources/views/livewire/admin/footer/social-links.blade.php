<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">

    {{-- FORM THÊM MỚI --}}
    <div class="flex flex-col md:flex-row gap-4 mb-8 border-b border-gray-100 pb-6 bg-blue-50 p-4 rounded-lg">
        <div class="flex-1">
            <label class="block text-xs font-bold text-gray-500 mb-1">Nền tảng</label>
            <input wire:model="platform" type="text" placeholder="VD: Facebook" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500">
            @error('platform') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex-1">
            <label class="block text-xs font-bold text-gray-500 mb-1">Đường dẫn (URL)</label>
            <input wire:model="url" type="text" placeholder="https://..." class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500">
            @error('url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex-1">
            <label class="block text-xs font-bold text-gray-500 mb-1">Icon Mặc định</label>
            <div class="relative">
                <select wire:model="icon_class" class="w-full rounded-md border-gray-300 text-sm appearance-none focus:border-blue-500">
                    <option value="">-- Chọn Icon --</option>
                    @foreach($defaultIcons as $class => $name)
                        <option value="{{ $class }}">{{ $name }}</option>
                    @endforeach
                    <option value="custom">Khác (Nhập tay sau)</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <div class="flex items-end">
            <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded-md font-bold text-sm hover:bg-blue-700 h-[38px] shadow-sm">
                + Thêm
            </button>
        </div>
    </div>

    {{-- DANH SÁCH (Grid Layout + Sortable) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
         x-data
         x-init="
            Sortable.create($el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'bg-blue-50',
                onEnd: function (evt) {
                    $wire.updateOrder(this.toArray());
                }
            })
         ">

        @foreach($links as $link)
            <div wire:key="social-{{ $link->id }}" data-id="{{ $link->id }}" class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm hover:shadow-md transition group">

                @if($editingId === $link->id)
                    {{-- MODE: ĐANG SỬA --}}
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" wire:model="edit_platform" class="w-1/2 text-xs border-gray-300 rounded" placeholder="Name">
                            <select wire:model="edit_icon_class" class="w-1/2 text-xs border-gray-300 rounded">
                                @foreach($defaultIcons as $class => $name)
                                    <option value="{{ $class }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" wire:model="edit_url" class="w-full text-xs border-gray-300 rounded" placeholder="URL">

                        <div class="flex justify-end gap-2 pt-2 border-t border-gray-50">
                            <button wire:click="cancelEdit" class="text-xs text-gray-500 hover:text-gray-700">Hủy</button>
                            <button wire:click="update" class="bg-green-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-green-700">Lưu lại</button>
                        </div>
                    </div>
                @else
                    {{-- MODE: HIỂN THỊ --}}
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3 overflow-hidden">
                            {{-- Drag Handle --}}
                            <span class="drag-handle cursor-move text-gray-300 hover:text-gray-500 px-1">⋮⋮</span>

                            {{-- Icon Preview --}}
                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                                <i class="{{ $link->icon_class }} text-lg"></i>
                            </div>

                            {{-- Info --}}
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate">{{ $link->platform }}</p>
                                <a href="{{ $link->url }}" target="_blank" class="text-xs text-gray-400 hover:text-blue-500 truncate block">{{ $link->url }}</a>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition">
                            <button wire:click="edit({{ $link->id }})" class="text-blue-500 hover:text-blue-700 p-1" title="Sửa">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button wire:confirm="Xóa?" wire:click="delete({{ $link->id }})" class="text-red-400 hover:text-red-600 p-1" title="Xóa">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

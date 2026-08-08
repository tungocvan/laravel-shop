<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form tạo cột mới --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-800 mb-3">Thêm Cột Mới</h4>
            <form wire:submit.prevent="createColumn" class="space-y-3">
                <input type="text" wire:model="col_title" placeholder="Tiêu đề (VD: Hỗ trợ)"
                    class="w-full rounded-md border-gray-300 text-sm">
                <input type="text" wire:model="col_slug" placeholder="Slug (VD: support)"
                    class="w-full rounded-md border-gray-300 text-sm">
                <input type="number" wire:model="col_sort" placeholder="Thứ tự"
                    class="w-full rounded-md border-gray-300 text-sm">
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded-md text-sm font-bold hover:bg-blue-700">Thêm
                    Cột</button>
            </form>
        </div>
    </div>


    {{-- Danh sách Cột & Links --}}
    <div class="lg:col-span-2 space-y-6" x-data x-init="Sortable.create($el, {
        handle: '.column-drag-handle', // Chỉ kéo được khi cầm vào handle này
        animation: 150,
        ghostClass: 'opacity-50',
        onEnd: function(evt) {
            $wire.updateColumnOrder(this.toArray());
        }
    })">

        @foreach ($columns as $column)
            {{-- Thêm data-id cho SortableJS --}}
            <div wire:key="col-{{ $column->id }}" data-id="{{ $column->id }}"
                class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all {{ !$column->is_active ? 'opacity-60 grayscale border-dashed' : '' }}"
                x-data="{ open: true }">

                {{-- HEADER CỘT --}}
                <div class="bg-gray-50 p-4 flex justify-between items-center border-b border-gray-100">

                    <div class="flex items-center gap-3 flex-1">
                        {{-- 1. Nút Kéo Thả --}}
                        <span class="column-drag-handle cursor-move text-gray-400 hover:text-gray-700 px-1 py-2"
                            title="Kéo để sắp xếp">
                            ⋮⋮
                        </span>

                        {{-- CHECK: ĐANG SỬA HAY HIỂN THỊ? --}}
                        @if ($editingColumnId === $column->id)
                            {{-- MODE: ĐANG SỬA (Inputs) --}}
                            <div class="flex items-center gap-2 flex-1 animate-fadeIn">
                                <input type="text" wire:model="edit_col_title"
                                    class="w-1/3 rounded border-gray-300 text-sm font-bold focus:ring-blue-500"
                                    placeholder="Tiêu đề">

                                <input type="text" wire:model="edit_col_slug"
                                    class="w-1/3 rounded border-gray-300 text-sm font-mono focus:ring-blue-500"
                                    placeholder="slug">

                                <div class="flex items-center gap-1">
                                    <button wire:click="updateColumn"
                                        class="bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold hover:bg-blue-700">Lưu</button>
                                    <button wire:click="cancelEditColumn"
                                        class="text-gray-500 hover:text-gray-700 px-2 py-1 text-xs">Hủy</button>
                                </div>
                            </div>
                        @else
                            {{-- MODE: HIỂN THỊ (Text) --}}
                            <div class="flex items-center gap-2 flex-1 group/title">
                                {{-- Click vào tên để mở/đóng accordion --}}
                                <div class="cursor-pointer select-none flex items-center gap-2" @click="open = !open">
                                    <span class="font-bold text-gray-800 text-lg">{{ $column->title }}</span>
                                    <span
                                        class="text-xs text-gray-500 font-mono bg-gray-200 px-1.5 rounded">{{ $column->slug }}</span>
                                </div>

                                {{-- Nút Sửa (Hiện khi hover vào vùng tiêu đề) --}}
                                <button wire:click="editColumn({{ $column->id }})"
                                    class="opacity-0 group-hover/title:opacity-100 text-blue-500 hover:text-blue-700 ml-2 transition"
                                    title="Sửa tên cột">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                @if (!$column->is_active)
                                    <span
                                        class="text-[10px] font-bold text-white bg-gray-500 px-1.5 py-0.5 rounded uppercase ml-2">Đang
                                        ẩn</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Actions Bên Phải (Ẩn/Hiện, Xóa) - Chỉ hiện khi KHÔNG sửa --}}
                    @if ($editingColumnId !== $column->id)
                        <div class="flex items-center gap-3 ml-4">
                            <button wire:click="toggleColumn({{ $column->id }})"
                                class="text-gray-400 hover:text-blue-600 transition"
                                title="{{ $column->is_active ? 'Nhấn để ẩn' : 'Nhấn để hiện' }}">
                                @if ($column->is_active)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                @endif
                            </button>
                            <div class="h-4 w-px bg-gray-300"></div>
                            <button wire:confirm="Xóa cột này?" wire:click="deleteColumn({{ $column->id }})"
                                class="text-red-400 hover:text-red-600 transition" title="Xóa cột">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- BODY (Giữ nguyên logic thêm link và danh sách link cũ) --}}
                <div x-show="open" class="p-4 bg-gray-50/50">
                    {{-- COPY LẠI PHẦN FORM ADD LINK VÀ DANH SÁCH LINKS CỦA BẠN VÀO ĐÂY --}}
                    {{-- (Phần này bạn đã làm ở bước trước, chỉ cần paste vào bên trong div này) --}}

                    {{-- Ví dụ tóm tắt để bạn dễ hình dung vị trí paste: --}}

                    {{-- 1. Form Add Link (Đã update mảng new_links) --}}
                    <div class="flex flex-col gap-2 mb-4 bg-white p-3 rounded-lg border border-gray-100">
                        {{-- ... Input & Button ... --}}
                        <div class="flex gap-2 items-start">
                            <div class="flex-1">
                                <input type="text" wire:model="new_links.{{ $column->id }}.label"
                                    placeholder="Tên Link" class="w-full rounded-md border-gray-300 text-xs"
                                    wire:keydown.enter="addLink({{ $column->id }})">
                            </div>
                            <div class="flex-1">
                                <input type="text" wire:model="new_links.{{ $column->id }}.url"
                                    placeholder="URL" class="w-full rounded-md border-gray-300 text-xs"
                                    wire:keydown.enter="addLink({{ $column->id }})">
                            </div>
                            <button wire:click="addLink({{ $column->id }})"
                                class="bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs font-bold whitespace-nowrap hover:bg-blue-700">
                                <span wire:loading.remove wire:target="addLink({{ $column->id }})">+ Link</span>
                                <span wire:loading wire:target="addLink({{ $column->id }})">...</span>
                            </button>
                        </div>
                        @error("new_links.$column->id.label")
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 2. List Links (Đã có Drag Drop & Edit) --}}
                    <ul class="space-y-2" x-data x-init="Sortable.create($el, { handle: '.drag-handle', animation: 150, onEnd: function(evt) { $wire.updateLinkOrder(this.toArray()); } })">
                        @foreach ($column->links as $link)
                            <li wire:key="link-{{ $link->id }}" data-id="{{ $link->id }}"
                                class="bg-white border border-gray-200 rounded px-3 py-2 group hover:border-blue-300 transition shadow-sm">
                                {{-- ... (Logic hiển thị Link / Edit Mode như cũ) ... --}}
                                @if ($editingLinkId === $link->id)
                                    {{-- Form Edit --}}
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="edit_label"
                                                class="w-1/2 rounded border-gray-300 text-xs px-2 py-1">
                                            <input type="text" wire:model="edit_url"
                                                class="w-1/2 rounded border-gray-300 text-xs px-2 py-1">
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button wire:click="cancelEdit" class="text-xs text-gray-500">Hủy</button>
                                            <button wire:click="updateLink"
                                                class="bg-blue-600 text-white text-xs px-2 py-1 rounded">Lưu</button>
                                        </div>
                                    </div>
                                @else
                                    {{-- Display Mode --}}
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="drag-handle cursor-move text-gray-300 hover:text-gray-500">⋮⋮</span>
                                            <div>
                                                <div
                                                    class="text-sm font-medium {{ !$link->is_active ? 'line-through text-gray-400' : 'text-gray-700' }}">
                                                    {{ $link->label }}</div>
                                                <div class="text-xs text-gray-400">{{ $link->url }}</div>
                                            </div>
                                        </div>
                                        <div
                                            class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                            <button
                                                wire:click="editLink({{ $link->id }}, '{{ addslashes($link->label) }}', '{{ addslashes($link->url) }}')"
                                                class="text-blue-500 text-xs font-bold px-2">Sửa</button>
                                            <button wire:confirm="Xóa?" wire:click="deleteLink({{ $link->id }})"
                                                class="text-red-400 text-xs font-bold px-2">Xóa</button>
                                        </div>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                </div>
            </div>
        @endforeach
    </div>
</div>

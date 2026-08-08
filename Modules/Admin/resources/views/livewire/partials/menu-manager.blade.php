<div class="space-y-6" x-data="{ open: @entangle('isModalOpen') }">
    {{-- 1. Location Selector --}}
    <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100">
        <div class="flex items-center gap-4">
            <label class="font-bold text-gray-700">Vị trí Menu:</label>
            <select wire:model.live="location" class="rounded-md border-gray-300 text-sm focus:ring-blue-500">
                @foreach($menuLocations as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        {{-- Button kích hoạt Modal --}}
        <button wire:click="openModal"
                class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm font-bold hover:bg-blue-700 shadow-sm transition">
            + Thêm mục mới
        </button>
    </div>

    {{-- 2. Menu Tree List --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        @if($menuTree->isEmpty())
            <div class="p-8 text-center text-gray-500">
                Chưa có menu nào cho vị trí này. Hãy thêm mới!
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($menuTree as $item)
                    <li class="p-4 hover:bg-gray-50 transition group">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <span class="cursor-move text-gray-400">⋮⋮</span>
                                <span class="font-bold text-gray-800">{{ $item->title }}</span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ $item->url }}</span>
                                @if(!$item->is_active)
                                    <span class="text-xs text-red-500 bg-red-50 px-2 py-0.5 rounded">Hidden</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                <button wire:click="openModal({{ $item->id }})" class="text-blue-600 hover:underline text-xs font-medium">Sửa</button>
                                <button wire:confirm="Xóa mục này và toàn bộ con?" wire:click="delete({{ $item->id }})" class="text-red-600 hover:underline text-xs font-medium">Xóa</button>
                            </div>
                        </div>

                        {{-- Children Recursive --}}
                        @if($item->children->count() > 0)
                            <ul class="mt-3 ml-8 border-l-2 border-gray-100 pl-4 space-y-2">
                                @foreach($item->children as $child)
                                    <li class="flex justify-between items-center text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">-</span>
                                            <span>{{ $child->title }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button wire:click="openModal({{ $child->id }})" class="text-blue-600 hover:underline text-xs">Sửa</button>
                                            <button wire:confirm="Xóa?" wire:click="delete({{ $child->id }})" class="text-red-600 hover:underline text-xs">Xóa</button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- 3. MODAL (Render luôn HTML nhưng ẩn bằng Alpine x-show) --}}
    @teleport('body')
        <div x-show="open"
             style="display: none;"
             class="fixed inset-0 z-[9999] overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true">

            {{-- Backdrop (Nền tối) --}}
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"
                 aria-hidden="true"
                 @click="open = false"> {{-- Click ra ngoài để đóng --}}
            </div>

            {{-- Căn giữa modal --}}
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

                {{-- Modal Panel --}}
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

                    <form wire:submit.prevent="save" class="p-6">
                        <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ $editingId ? 'Cập nhật Menu' : 'Thêm Menu Mới' }}
                            </h3>
                            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                                <span class="text-2xl">&times;</span>
                            </button>
                        </div>

                        <div class="space-y-4">
                            {{-- Loading State --}}
                            <div wire:loading wire:target="openModal" class="text-xs text-blue-500 font-medium">
                                Đang tải dữ liệu...
                            </div>

                            {{-- Tiêu đề --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tiêu đề <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Nhập tên menu...">
                                @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            {{-- URL --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Đường dẫn (URL)</label>
                                <input type="text" wire:model="url" placeholder="/gioi-thieu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Parent Select --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Menu Cha</label>
                                <select wire:model="parent_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">-- Là mục gốc --</option>
                                    @foreach($flatItems as $pItem)
                                        @if($pItem->id != $editingId)
                                            <option value="{{ $pItem->id }}">{{ $pItem->title }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sort & Active --}}
                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <label class="block text-sm font-medium text-gray-700">Thứ tự</label>
                                    <input type="number" wire:model="sort_order" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 text-sm">
                                </div>
                                <div class="w-1/2 flex items-center pt-6">
                                    <label class="flex items-center cursor-pointer select-none">
                                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 font-medium">Hiển thị</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="open = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium transition">Hủy</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium shadow-sm transition flex items-center gap-2">
                                <span wire:loading.remove wire:target="save">Lưu lại</span>
                                <span wire:loading wire:target="save">Đang lưu...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endteleport
</div>

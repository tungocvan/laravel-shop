<div class="space-y-6"
    x-data="{
        open: @entangle('isModalOpen'),
        dragItem: null,
        dropBefore(targetId, parentId, list) {
            if (!this.dragItem) return;
            let ids = Array.from(list.querySelectorAll(':scope > li[data-id]')).map(el => Number(el.dataset.id));
            ids = ids.filter(id => id !== Number(this.dragItem.id));
            const targetIndex = ids.indexOf(Number(targetId));
            if (targetIndex >= 0) ids.splice(targetIndex, 0, Number(this.dragItem.id));
            else ids.push(Number(this.dragItem.id));
            $wire.moveItemByDrag(Number(this.dragItem.id), parentId === null ? null : Number(parentId), ids);
            this.dragItem = null;
        },
        dropEnd(parentId, list) {
            if (!this.dragItem) return;
            let ids = Array.from(list.querySelectorAll(':scope > li[data-id]')).map(el => Number(el.dataset.id));
            ids = ids.filter(id => id !== Number(this.dragItem.id));
            ids.push(Number(this.dragItem.id));
            $wire.moveItemByDrag(Number(this.dragItem.id), parentId === null ? null : Number(parentId), ids);
            this.dragItem = null;
        }
    }">
    @php($fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100')

    <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between">
        <div class="w-full sm:max-w-md">
            <label class="block text-sm font-semibold text-gray-700">Vị trí Menu</label>
            <select wire:model.live="location" class="{{ $fieldClass }}">
                @foreach($menuLocations as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Danh sách location được lấy từ cấu hình Header, không hardcode trong Livewire.</p>
        </div>
        <button type="button" wire:click="openModal" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">+ Thêm mục mới</button>
    </div>

    @error('menu')<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="font-bold text-gray-900">Menu điều hướng</h3>
            <p class="mt-1 text-sm text-gray-500">Kéo tay cầm ⋮⋮ để đổi thứ tự. Có thể kéo item vào danh sách con của một mục cha hoặc kéo trở lại cấp gốc.</p>
        </div>

        @if($menuTree->isEmpty())
            <div class="m-5 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-sm text-gray-500">Chưa có menu ở vị trí này. Database chỉ được tạo khi bạn bấm Lưu item mới.</div>
        @else
            <ul class="space-y-3 p-5" data-menu-list="root">
                @foreach($menuTree as $item)
                    <li data-id="{{ $item->id }}" wire:key="header-menu-root-{{ $item->id }}"
                        draggable="true"
                        @dragstart.stop="dragItem = { id: {{ $item->id }} }; $event.dataTransfer.effectAllowed = 'move'"
                        @dragend="dragItem = null"
                        @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                        @drop.stop.prevent="dropBefore({{ $item->id }}, null, $el.parentElement)"
                        class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-blue-200">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="cursor-grab select-none pt-0.5 text-gray-400 active:cursor-grabbing" title="Kéo để sắp xếp">⋮⋮</span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-gray-900">{{ $item->title }}</span>
                                        <span class="max-w-full truncate rounded bg-white px-2 py-0.5 text-xs text-gray-500 ring-1 ring-gray-200">{{ $item->url ?: '#' }}</span>
                                        @if(!$item->is_active)<span class="rounded bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">Đang ẩn</span>@endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" wire:click="openModal({{ $item->id }})" class="rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">Sửa</button>
                                <button type="button" wire:confirm="Xóa mục này và toàn bộ con?" wire:click="delete({{ $item->id }})" class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Xóa</button>
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg border border-dashed border-gray-200 bg-white p-3">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Menu con</div>
                            <ul class="space-y-2" data-menu-list="children-{{ $item->id }}">
                                @foreach($item->children as $child)
                                    <li data-id="{{ $child->id }}" wire:key="header-menu-child-{{ $child->id }}"
                                        draggable="true"
                                        @dragstart.stop="dragItem = { id: {{ $child->id }} }; $event.dataTransfer.effectAllowed = 'move'"
                                        @dragend="dragItem = null"
                                        @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                                        @drop.stop.prevent="dropBefore({{ $child->id }}, {{ $item->id }}, $el.parentElement)"
                                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="cursor-grab select-none text-gray-400">⋮⋮</span>
                                            <span class="truncate text-sm font-semibold text-gray-800">{{ $child->title }}</span>
                                            <span class="truncate text-xs text-gray-400">{{ $child->url }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="openModal({{ $child->id }})" class="text-xs font-semibold text-blue-600">Sửa</button>
                                            <button type="button" wire:confirm="Xóa?" wire:click="delete({{ $child->id }})" class="text-xs font-semibold text-red-600">Xóa</button>
                                        </div>
                                    </li>
                                @endforeach
                                <li @dragover.prevent @drop.stop.prevent="dropEnd({{ $item->id }}, $el.parentElement)" class="rounded-lg border border-dashed border-gray-200 px-3 py-2 text-center text-xs text-gray-400" :class="dragItem ? 'border-blue-300 bg-blue-50 text-blue-600' : ''">Thả vào cuối menu con</li>
                            </ul>
                        </div>
                    </li>
                @endforeach
                <li @dragover.prevent @drop.stop.prevent="dropEnd(null, $el.parentElement)" class="rounded-lg border border-dashed border-gray-300 px-3 py-3 text-center text-xs text-gray-400" :class="dragItem ? 'border-blue-400 bg-blue-50 text-blue-600' : ''">Thả vào cuối menu cấp gốc</li>
            </ul>
        @endif
    </div>

    @teleport('body')
        <div x-show="open" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/70" @click="open = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="open" class="relative w-full max-w-lg rounded-xl bg-white text-left shadow-xl">
                    <form wire:submit.prevent="save" class="p-6">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <div><h3 class="text-lg font-bold text-gray-900">{{ $editingId ? 'Cập nhật Menu' : 'Thêm Menu Mới' }}</h3><p class="mt-1 text-xs text-gray-500">Menu item chỉ được lưu vào location đang chọn.</p></div>
                            <button type="button" @click="open = false" class="text-2xl text-gray-400 hover:text-gray-600">&times;</button>
                        </div>

                        <div class="mt-5 space-y-5">
                            <div><label class="block text-sm font-semibold text-gray-700">Tiêu đề <span class="text-red-500">*</span></label><input type="text" wire:model="title" class="{{ $fieldClass }}" placeholder="Nhập tên menu...">@error('title')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror</div>
                            <div><label class="block text-sm font-semibold text-gray-700">Đường dẫn (URL)</label><input type="text" wire:model="url" placeholder="/gioi-thieu" class="{{ $fieldClass }}"></div>
                            <div><label class="block text-sm font-semibold text-gray-700">Menu Cha</label><select wire:model="parent_id" class="{{ $fieldClass }}"><option value="">-- Là mục gốc --</option>@foreach($flatItems as $pItem)@if($pItem->id != $editingId)<option value="{{ $pItem->id }}">{{ $pItem->title }}</option>@endif @endforeach</select>@error('parent_id')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror</div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div><label class="block text-sm font-semibold text-gray-700">Thứ tự</label><input type="number" wire:model="sort_order" class="{{ $fieldClass }}"></div>
                                <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 sm:self-end"><input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"> Hiển thị</label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <button type="button" @click="open = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</button>
                            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"><span wire:loading.remove wire:target="save">Lưu lại</span><span wire:loading wire:target="save">Đang lưu...</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endteleport
</div>

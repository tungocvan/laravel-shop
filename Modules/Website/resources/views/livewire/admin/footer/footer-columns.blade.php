<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <h4 class="font-bold text-gray-800 mb-3">Thêm Cột Mới</h4>
            <form wire:submit.prevent="createColumn" class="space-y-3">
                <input type="text" wire:model="col_title" placeholder="Tiêu đề (VD: Hỗ trợ)" class="w-full rounded-md border-gray-300 text-sm">
                <input type="text" wire:model="col_slug" placeholder="Slug (VD: support)" class="w-full rounded-md border-gray-300 text-sm">
                <input type="number" wire:model="col_sort" placeholder="Thứ tự" class="w-full rounded-md border-gray-300 text-sm">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md text-sm font-bold hover:bg-blue-700">Thêm Cột</button>
            </form>
        </div>
        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            <div class="font-semibold">Mẹo quản trị</div>
            <p class="mt-1">Kéo tay cầm ⋮⋮ để đổi vị trí menu link trong cùng cột hoặc kéo sang cột Footer khác. Nút Nhân bản tạo nhanh một cột mới kèm toàn bộ links.</p>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6" wire:ignore.self x-data x-init="
        if ($el._footerColumnsSortable) $el._footerColumnsSortable.destroy();
        $el._footerColumnsSortable = Sortable.create($el, {
            handle: '.column-drag-handle',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd() { $wire.updateColumnOrder(this.toArray()); }
        });
    ">
        @foreach ($columns as $column)
            <div wire:key="col-{{ $column->id }}" data-id="{{ $column->id }}"
                class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden transition-all {{ !$column->is_active ? 'opacity-60 grayscale border-dashed' : '' }}"
                x-data="{ open: true }">
                <div class="bg-gray-50 p-4 flex justify-between items-center border-b border-gray-100">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="column-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-700 px-1 py-2" title="Kéo để sắp xếp cột">⋮⋮</span>

                        @if ($editingColumnId === $column->id)
                            <div class="flex items-center gap-2 flex-1 animate-fadeIn">
                                <input type="text" wire:model="edit_col_title" class="w-1/3 rounded border-gray-300 text-sm font-bold focus:ring-blue-500" placeholder="Tiêu đề">
                                <input type="text" wire:model="edit_col_slug" class="w-1/3 rounded border-gray-300 text-sm font-mono focus:ring-blue-500" placeholder="slug">
                                <div class="flex items-center gap-1">
                                    <button wire:click="updateColumn" class="bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold hover:bg-blue-700">Lưu</button>
                                    <button wire:click="cancelEditColumn" class="text-gray-500 hover:text-gray-700 px-2 py-1 text-xs">Hủy</button>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-2 flex-1 group/title">
                                <div class="cursor-pointer select-none flex items-center gap-2" @click="open = !open">
                                    <span class="font-bold text-gray-800 text-lg">{{ $column->title }}</span>
                                    <span class="text-xs text-gray-500 font-mono bg-gray-200 px-1.5 rounded">{{ $column->slug }}</span>
                                </div>
                                <button wire:click="editColumn({{ $column->id }})" class="opacity-0 group-hover/title:opacity-100 text-blue-500 hover:text-blue-700 ml-2 transition" title="Sửa tên cột">Sửa</button>
                                @if (!$column->is_active)
                                    <span class="text-[10px] font-bold text-white bg-gray-500 px-1.5 py-0.5 rounded uppercase ml-2">Đang ẩn</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($editingColumnId !== $column->id)
                        <div class="flex items-center gap-2 ml-4">
                            <button type="button" wire:click="duplicateColumn({{ $column->id }})" wire:loading.attr="disabled" wire:target="duplicateColumn({{ $column->id }})" class="rounded-md border border-blue-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 disabled:opacity-50">Nhân bản</button>
                            <button wire:click="toggleColumn({{ $column->id }})" class="text-gray-400 hover:text-blue-600 transition" title="{{ $column->is_active ? 'Nhấn để ẩn' : 'Nhấn để hiện' }}">◉</button>
                            <button wire:confirm="Xóa cột này?" wire:click="deleteColumn({{ $column->id }})" class="text-red-400 hover:text-red-600 transition" title="Xóa cột">🗑</button>
                        </div>
                    @endif
                </div>

                <div x-show="open" class="p-4 bg-gray-50/50">
                    <div class="flex flex-col gap-2 mb-4 bg-white p-3 rounded-lg border border-gray-100">
                        <div class="flex gap-2 items-start">
                            <div class="flex-1"><input type="text" wire:model="new_links.{{ $column->id }}.label" placeholder="Tên Link" class="w-full rounded-md border-gray-300 text-xs" wire:keydown.enter="addLink({{ $column->id }})"></div>
                            <div class="flex-1"><input type="text" wire:model="new_links.{{ $column->id }}.url" placeholder="URL" class="w-full rounded-md border-gray-300 text-xs" wire:keydown.enter="addLink({{ $column->id }})"></div>
                            <button wire:click="addLink({{ $column->id }})" class="bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs font-bold whitespace-nowrap hover:bg-blue-700">+ Link</button>
                        </div>
                        @error("new_links.$column->id.label")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                    </div>

                    <ul class="min-h-10 space-y-2 rounded-md transition"
                        data-column-id="{{ $column->id }}"
                        wire:key="links-sortable-{{ $column->id }}"
                        x-data
                        x-init="
                            if ($el._footerLinksSortable) $el._footerLinksSortable.destroy();
                            $el._footerLinksSortable = Sortable.create($el, {
                                group: 'footer-menu-links',
                                handle: '.drag-handle',
                                animation: 150,
                                ghostClass: 'opacity-40',
                                chosenClass: 'ring-2',
                                dragClass: 'shadow-lg',
                                onEnd(evt) {
                                    const linkId = Number(evt.item.dataset.id);
                                    const fromColumnId = Number(evt.from.dataset.columnId);
                                    const toColumnId = Number(evt.to.dataset.columnId);
                                    const targetIds = Array.from(evt.to.querySelectorAll(':scope > [data-id]')).map(el => Number(el.dataset.id));
                                    $wire.moveLinkByDrag(linkId, fromColumnId, toColumnId, targetIds);
                                }
                            });
                        ">
                        @foreach ($column->links as $link)
                            <li wire:key="link-{{ $link->id }}" data-id="{{ $link->id }}"
                                class="bg-white border border-gray-200 rounded px-3 py-2 group hover:border-blue-300 transition shadow-sm">
                                @if ($editingLinkId === $link->id)
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2"><input type="text" wire:model="edit_label" class="w-1/2 rounded border-gray-300 text-xs px-2 py-1"><input type="text" wire:model="edit_url" class="w-1/2 rounded border-gray-300 text-xs px-2 py-1"></div>
                                        <div class="flex justify-end gap-2"><button wire:click="cancelEdit" class="text-xs text-gray-500">Hủy</button><button wire:click="updateLink" class="bg-blue-600 text-white text-xs px-2 py-1 rounded">Lưu</button></div>
                                    </div>
                                @else
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-blue-500 px-1 py-2 select-none" title="Kéo để đổi vị trí hoặc chuyển sang cột khác">⋮⋮</span>
                                            <div><div class="text-sm font-medium {{ !$link->is_active ? 'line-through text-gray-400' : 'text-gray-700' }}">{{ $link->label }}</div><div class="text-xs text-gray-400">{{ $link->url }}</div></div>
                                        </div>
                                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                            <button wire:click="editLink({{ $link->id }}, '{{ addslashes($link->label) }}', '{{ addslashes($link->url) }}')" class="text-blue-500 text-xs font-bold px-2">Sửa</button>
                                            <button wire:confirm="Xóa?" wire:click="deleteLink({{ $link->id }})" class="text-red-400 text-xs font-bold px-2">Xóa</button>
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

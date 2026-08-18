@if ($showExportConfigModal)
    <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4" wire:click.self="closeExportConfig">
        <div class="w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Cấu hình xuất Excel</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900">Chọn cột, kéo sắp xếp, canh lề và độ rộng</h3>
                    <p class="mt-1 text-xs text-gray-500">Tất cả cột mặc định Wrap Text. Chiều cao dòng để Auto; độ rộng cột được lưu theo tài khoản.</p>
                </div>
                <button type="button" wire:click="closeExportConfig" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-500 hover:bg-gray-50">×</button>
            </div>

            <div class="max-h-[68vh] overflow-y-auto px-5 py-5" x-data="{ dragging: null }">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="selectAllExportColumns" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Chọn tất cả cột</button>
                    <button type="button" wire:click="clearAllExportColumns" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Bỏ chọn tất cả</button>
                    <span class="text-xs text-gray-500">Kéo ⋮⋮ để đổi vị trí. Width hợp lệ 5–80.</span>
                </div>

                <div class="space-y-2">
                    @foreach ($exportColumnOrder as $position => $key)
                        @php($definition = $exportColumnDefinitions[$key] ?? ['label' => $key, 'align' => 'left', 'width' => 18])
                        <div
                            wire:key="export-column-{{ $key }}"
                            draggable="true"
                            @dragstart="dragging = '{{ $key }}'; $el.classList.add('opacity-50')"
                            @dragend="$el.classList.remove('opacity-50'); dragging = null"
                            @dragover.prevent="$el.classList.add('ring-2','ring-blue-200')"
                            @dragleave="$el.classList.remove('ring-2','ring-blue-200')"
                            @drop.prevent="$el.classList.remove('ring-2','ring-blue-200'); if (dragging && dragging !== '{{ $key }}') { $wire.moveExportColumn(dragging, '{{ $key }}') }"
                            class="grid grid-cols-[44px_54px_minmax(0,1fr)_140px_120px] items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 transition"
                        >
                            <div class="cursor-grab select-none text-center text-lg font-bold tracking-tighter text-gray-400" title="Kéo để sắp xếp">⋮⋮</div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-gray-500 shadow-sm ring-1 ring-gray-200">{{ $position + 1 }}</div>
                            <label class="flex min-w-0 cursor-pointer items-center gap-3">
                                <input type="checkbox" wire:model.live="exportSelectedColumns.{{ $key }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="truncate text-sm font-semibold text-gray-800">{{ $definition['label'] }}</span>
                            </label>
                            <select wire:model.live="exportAlignments.{{ $key }}" class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-200">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400">Width</label>
                                <input type="number" min="5" max="80" step="1" wire:model.live.debounce.300ms="exportWidths.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-200">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                <p class="text-xs text-gray-500">Excel sẽ luôn Wrap Text toàn bộ cột dữ liệu và để chiều cao dòng Auto theo nội dung.</p>
                <div class="flex gap-2">
                    <button type="button" wire:click="closeExportConfig" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Hủy</button>
                    <button type="button" wire:click="saveExportConfig" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Lưu cấu hình</button>
                </div>
            </div>
        </div>
    </div>
@endif
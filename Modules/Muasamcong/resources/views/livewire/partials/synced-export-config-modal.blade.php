@if ($showExportConfigModal)
    <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4" wire:click.self="closeExportConfig">
        <div class="w-full max-w-7xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Cấu hình xuất Excel</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900">Chọn cột, kéo sắp xếp, kiểu dữ liệu, canh lề và độ rộng</h3>
                    <p class="mt-1 text-xs text-gray-500">Tất cả cột mặc định Wrap Text. Chiều cao dòng Auto; Width được nhập theo pixel và lưu theo tài khoản.</p>
                </div>
                <button type="button" wire:click="closeExportConfig" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-500 hover:bg-gray-50">×</button>
            </div>

            <div class="max-h-[68vh] overflow-y-auto px-5 py-5" x-data="{ dragging: null }">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="selectAllExportColumns" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Chọn tất cả cột</button>
                    <button type="button" wire:click="clearAllExportColumns" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Bỏ chọn tất cả</button>
                    <span class="text-xs text-gray-500">Kéo ⋮⋮ để đổi vị trí. Width: 40–600 px.</span>
                </div>

                <div class="mb-2 hidden grid-cols-[44px_54px_minmax(0,1fr)_150px_130px_120px] gap-3 px-3 text-[10px] font-semibold uppercase tracking-wide text-gray-400 lg:grid">
                    <span></span>
                    <span>Vị trí</span>
                    <span>Cột</span>
                    <span>Kiểu dữ liệu</span>
                    <span>Canh lề</span>
                    <span>Width (px)</span>
                </div>

                <div class="space-y-2">
                    @foreach ($exportColumnOrder as $position => $key)
                        @php($definition = $exportColumnDefinitions[$key] ?? ['label' => $key, 'align' => 'left', 'width' => 140, 'type' => 'auto'])
                        <div
                            wire:key="export-column-{{ $key }}"
                            draggable="true"
                            @dragstart="dragging = '{{ $key }}'; $el.classList.add('opacity-50')"
                            @dragend="$el.classList.remove('opacity-50'); dragging = null"
                            @dragover.prevent="$el.classList.add('ring-2','ring-blue-200')"
                            @dragleave="$el.classList.remove('ring-2','ring-blue-200')"
                            @drop.prevent="$el.classList.remove('ring-2','ring-blue-200'); if (dragging && dragging !== '{{ $key }}') { $wire.moveExportColumn(dragging, '{{ $key }}') }"
                            class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 transition lg:grid-cols-[44px_54px_minmax(0,1fr)_150px_130px_120px] lg:items-center"
                        >
                            <div class="cursor-grab select-none text-center text-lg font-bold tracking-tighter text-gray-400" title="Kéo để sắp xếp">⋮⋮</div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-gray-500 shadow-sm ring-1 ring-gray-200">{{ $position + 1 }}</div>
                            <label class="flex min-w-0 cursor-pointer items-center gap-3">
                                <input type="checkbox" wire:model.live="exportSelectedColumns.{{ $key }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="truncate text-sm font-semibold text-gray-800">{{ $definition['label'] }}</span>
                            </label>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 lg:hidden">Kiểu dữ liệu</label>
                                <select wire:model.live="exportDataTypes.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-200">
                                    <option value="auto">Auto</option>
                                    <option value="number">Number</option>
                                    <option value="string">String</option>
                                    <option value="date">Date (dd/mm/yyyy)</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 lg:hidden">Canh lề</label>
                                <select wire:model.live="exportAlignments.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-200">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 lg:hidden">Width (px)</label>
                                <div class="relative">
                                    <input type="number" min="40" max="600" step="1" wire:model.live.debounce.300ms="exportWidths.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 pr-8 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-200">
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[10px] font-semibold text-gray-400">px</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                <p class="text-xs text-gray-500">Auto giữ kiểu dữ liệu gốc; Number ép số; String ép Text; Date xuất ngày theo dd/mm/yyyy. Wrap Text luôn bật và row height để Auto.</p>
                <div class="flex gap-2">
                    <button type="button" wire:click="closeExportConfig" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Hủy</button>
                    <button type="button" wire:click="saveExportConfig" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Lưu cấu hình</button>
                </div>
            </div>
        </div>
    </div>
@endif
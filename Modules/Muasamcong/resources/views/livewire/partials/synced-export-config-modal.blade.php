@if ($showExportConfigModal)
    <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4" wire:click.self="closeExportConfig">
        <div class="w-full max-w-[1600px] overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Cấu hình xuất Excel</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900">Nhiều cấu hình, đổi tên header, kiểu dữ liệu, decimal, canh lề và độ rộng</h3>
                    <p class="mt-1 text-xs text-gray-500">Có thể nhân đôi cấu hình để dùng lại toàn bộ tham số. Number mặc định Decimal = 0; Date xuất dd/mm/yyyy; toàn bộ cột Wrap Text.</p>
                </div>
                <button type="button" wire:click="closeExportConfig" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-500 hover:bg-gray-50">×</button>
            </div>

            <div class="border-b border-gray-200 bg-blue-50/50 px-5 py-4">
                <div class="grid gap-3 lg:grid-cols-[260px_minmax(0,1fr)_auto_auto] lg:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Cấu hình đang mở</label>
                        <select wire:model.live="activeExportProfileId" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm">
                            <option value="">Cấu hình mới</option>
                            @foreach ($exportProfiles as $profile)
                                <option value="{{ $profile['id'] }}">{{ $profile['name'] }}{{ $profile['is_default'] ? ' • Mặc định' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Tên cấu hình</label>
                        <input type="text" wire:model="exportProfileName" maxlength="120" placeholder="Ví dụ: Báo giá bệnh viện, Danh mục nội bộ..." class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700">
                        <input type="checkbox" wire:model="exportProfileDefault" class="rounded border-gray-300 text-blue-600">
                        Mặc định
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="newExportProfile" class="rounded-xl border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-700">+ Cấu hình mới</button>
                        <button type="button" wire:click="duplicateExportProfile" @disabled($activeExportProfileId === null) class="rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 disabled:opacity-40">⧉ Nhân đôi</button>
                        <button type="button" wire:click="deleteExportProfile" wire:confirm="Xóa cấu hình xuất Excel đang chọn?" @disabled($activeExportProfileId === null) class="rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 disabled:opacity-40">Xóa</button>
                    </div>
                </div>
            </div>

            <div class="max-h-[62vh] overflow-y-auto px-5 py-5" x-data="{ dragging: null }">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="selectAllExportColumns" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Chọn tất cả cột</button>
                    <button type="button" wire:click="clearAllExportColumns" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Bỏ chọn tất cả</button>
                    <span class="text-xs text-gray-500">Kéo ⋮⋮ để đổi vị trí. Width: 40–600 px. Decimal: 0–6, chỉ áp dụng khi Type = Number.</span>
                </div>

                <div class="mb-2 hidden grid-cols-[40px_48px_160px_minmax(170px,1fr)_130px_85px_110px_100px] gap-3 px-3 text-[10px] font-semibold uppercase tracking-wide text-gray-400 2xl:grid">
                    <span></span><span>Vị trí</span><span>Cột gốc</span><span>Header xuất</span><span>Kiểu dữ liệu</span><span>Decimal</span><span>Canh lề</span><span>Width</span>
                </div>

                <div class="space-y-2">
                    @foreach ($exportColumnOrder as $position => $key)
                        @php($definition = $exportColumnDefinitions[$key] ?? ['label' => $key, 'align' => 'left', 'width' => 140, 'type' => 'auto'])
                        <div wire:key="export-column-{{ $key }}" draggable="true"
                            @dragstart="dragging = '{{ $key }}'; $el.classList.add('opacity-50')"
                            @dragend="$el.classList.remove('opacity-50'); dragging = null"
                            @dragover.prevent="$el.classList.add('ring-2','ring-blue-200')"
                            @dragleave="$el.classList.remove('ring-2','ring-blue-200')"
                            @drop.prevent="$el.classList.remove('ring-2','ring-blue-200'); if (dragging && dragging !== '{{ $key }}') { $wire.moveExportColumn(dragging, '{{ $key }}') }"
                            class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 transition 2xl:grid-cols-[40px_48px_160px_minmax(170px,1fr)_130px_85px_110px_100px] 2xl:items-center">
                            <div class="cursor-grab select-none text-center text-lg font-bold text-gray-400" title="Kéo để sắp xếp">⋮⋮</div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-gray-500 ring-1 ring-gray-200">{{ $position + 1 }}</div>
                            <label class="flex min-w-0 cursor-pointer items-center gap-2">
                                <input type="checkbox" wire:model.live="exportSelectedColumns.{{ $key }}" class="rounded border-gray-300 text-blue-600">
                                <span class="truncate text-sm font-semibold text-gray-800">{{ $definition['label'] }}</span>
                            </label>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 2xl:hidden">Header xuất</label>
                                <input type="text" maxlength="120" wire:model="exportHeaders.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 2xl:hidden">Kiểu dữ liệu</label>
                                <select wire:model.live="exportDataTypes.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700">
                                    <option value="auto">Auto</option>
                                    <option value="number">Number</option>
                                    <option value="string">String</option>
                                    <option value="date">Date (dd/mm/yyyy)</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 2xl:hidden">Decimal</label>
                                @if (($exportDataTypes[$key] ?? $definition['type']) === 'number')
                                    <input type="number" min="0" max="6" step="1" wire:model.live="exportDecimals.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700" title="Số chữ số sau dấu thập phân">
                                @else
                                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-100 px-2 py-1.5 text-center text-xs text-gray-400">—</div>
                                @endif
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 2xl:hidden">Canh lề</label>
                                <select wire:model.live="exportAlignments.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700">
                                    <option value="left">Left</option><option value="center">Center</option><option value="right">Right</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase text-gray-400 2xl:hidden">Width (px)</label>
                                <div class="relative">
                                    <input type="number" min="40" max="600" step="1" wire:model.live.debounce.300ms="exportWidths.{{ $key }}" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 pr-7 text-xs text-gray-700">
                                    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[10px] font-semibold text-gray-400">px</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                <p class="text-xs text-gray-500">Number + Decimal 0 xuất dạng #,##0, không có phần thập phân; Decimal 2 xuất #,##0.00. Nhân đôi giữ nguyên toàn bộ cột, header, type, decimal, canh lề và width.</p>
                <div class="flex gap-2">
                    <button type="button" wire:click="closeExportConfig" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Đóng</button>
                    <button type="button" wire:click="saveExportConfig" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Lưu cấu hình</button>
                </div>
            </div>
        </div>
    </div>
@endif
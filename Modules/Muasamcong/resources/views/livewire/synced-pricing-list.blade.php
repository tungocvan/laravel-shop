<div class="space-y-4">
    @php
        $selectedLookup = array_fill_keys(array_map('intval', $selectedIds), true);
        $allCurrentSelected = $currentPageIds !== [] && $currentPageSelected === count($currentPageIds);
        $canManage = auth('admin')->check() && auth('admin')->user()->can('muasamcong.pricing.sync');
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="flex-1">
                <label class="mb-1 block text-xs font-semibold text-gray-600">Tìm trong danh sách đồng bộ</label>
                <x-search wire:model.live.debounce.250ms="search" placeholder="Tên thuốc, hoạt chất, nhóm, TBMT, đơn vị trúng thầu, số quyết định, STT TT20/2022..." />
            </div>

            @if ($canManage)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Đã chọn {{ count($selectedIds) }}</span>
                    <button type="button" wire:click="editSelected" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">Sửa đã chọn</button>
                    <button type="button" wire:click="clearSelection" @disabled($selectedIds === []) class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 disabled:opacity-40">Bỏ chọn</button>
                    <button type="button" wire:click="openExportConfig" @disabled($selectedIds === []) class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40">Cấu hình xuất ({{ count($selectedIds) }})</button>
                    <button type="button" wire:click="deleteSelected" wire:confirm="Bạn có chắc muốn xóa các bản ghi đồng bộ đã chọn?" @disabled($selectedIds === []) class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-40">Xóa đã chọn</button>
                </div>
            @endif
        </div>
    </div>

    @if ($statusMessage !== '')
        <div class="rounded-xl border px-4 py-3 text-sm {{ $statusType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1900px] w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        @if ($canManage)
                            <th class="w-16 px-3 py-3 text-center">
                                <button type="button" wire:click="toggleCurrentPage(@js($currentPageIds))" class="inline-flex flex-col items-center gap-1" title="Chọn/bỏ chọn tất cả trang hiện tại">
                                    <input type="checkbox" tabindex="-1" @checked($allCurrentSelected) class="pointer-events-none rounded border-gray-300">
                                    <span class="text-[10px] normal-case text-gray-500">{{ $currentPageSelected }}/{{ count($currentPageIds) }}</span>
                                </button>
                            </th>
                        @endif
                        <th class="w-14 px-3 py-3">STT</th>
                        <th class="min-w-48 px-4 py-3">Thuốc</th>
                        <th class="min-w-32 px-4 py-3">Nhóm</th>
                        <th class="min-w-48 px-4 py-3">Hoạt chất</th>
                        <th class="min-w-40 px-4 py-3">Nồng độ</th>
                        <th class="min-w-28 px-4 py-3 text-right">Giá</th>
                        <th class="min-w-28 px-4 py-3 text-right">Số lượng</th>
                        <th class="min-w-72 px-4 py-3">Đơn vị trúng thầu</th>
                        <th class="min-w-40 px-4 py-3">Mã nhà thầu</th>
                        <th class="min-w-64 px-4 py-3">Chủ đầu tư / Bên mời thầu</th>
                        <th class="min-w-36 px-4 py-3">Mã TBMT</th>
                        <th class="min-w-40 px-4 py-3">Số quyết định</th>
                        <th class="min-w-36 px-4 py-3">Ngày quyết định</th>
                        <th class="min-w-44 px-4 py-3">Nhà sản xuất</th>
                        <th class="min-w-36 px-4 py-3">Nước SX</th>
                        <th class="min-w-36 px-4 py-3">Đồng bộ lúc</th>
                        @if ($canManage)<th class="w-24 px-4 py-3 text-center">Thao tác</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($items as $item)
                        <tr wire:key="synced-result-{{ $item->id }}" class="align-top {{ isset($selectedLookup[$item->id]) ? 'bg-indigo-50/50' : 'hover:bg-indigo-50/30' }}">
                            @if ($canManage)
                                <td class="px-3 py-4 text-center">
                                    <input type="checkbox" value="{{ $item->id }}" wire:model.live="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                            @endif
                            <td class="px-3 py-4 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-4 font-semibold text-gray-950">{{ $item->ten_thuoc ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $item->nhom_thuoc ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $item->ten_hoat_chat ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $item->nong_do ?: '-' }}</td>
                            <td class="px-4 py-4 text-right font-semibold">{{ is_numeric($item->don_gia) ? number_format((float) $item->don_gia, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-4 text-right">{{ is_numeric($item->so_luong) ? number_format((float) $item->so_luong, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-4">
                                @forelse ((array) $item->winning_name as $name)
                                    <div class="font-semibold text-emerald-700">{{ $name }}</div>
                                @empty
                                    <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Chưa cập nhật</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-4">
                                @forelse ((array) $item->winning_code as $code)
                                    <div class="font-mono text-xs">{{ $code }}</div>
                                @empty
                                    <span class="text-gray-400">-</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-4">{{ $item->ten_cdt_bmt ?: '-' }}</td>
                            <td class="px-4 py-4 font-mono text-xs">{{ $item->ma_tbmt ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $item->so_quyet_dinh ?: '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ $item->ngay_ban_hanh_quyet_dinh?->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $item->ten_co_so_san_xuat ?: '-' }}</td>
                            <td class="px-4 py-4">{{ $item->nuoc_san_xuat ?: '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ $item->synced_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            @if ($canManage)
                                <td class="px-4 py-4 text-center">
                                    <button type="button" wire:click="openEdit({{ $item->id }})" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">Sửa</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="18" class="px-5 py-12 text-center text-gray-500">Chưa có dữ liệu đã đồng bộ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-gray-200 px-4 py-4">{{ $items->links() }}</div>
        @endif
    </div>

    @if ($showEditModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" wire:click.self="closeEdit">
            <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Cập nhật thông tin trúng thầu</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">{{ $editingMedicine ?: 'Bản ghi đồng bộ' }}</h3>
                        <p class="mt-1 font-mono text-xs text-gray-500">{{ $editingTbmt ?: '-' }}</p>
                    </div>
                    <button type="button" wire:click="closeEdit" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-500 hover:bg-gray-50">×</button>
                </div>

                <div class="max-h-[72vh] space-y-5 overflow-y-auto px-5 py-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Đơn vị trúng thầu</label>
                            <textarea wire:model="winningName" rows="5" placeholder="Mỗi nhà thầu một dòng" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                            @error('winningName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Mã nhà thầu</label>
                            <textarea wire:model="winningCode" rows="5" placeholder="Mỗi mã nhà thầu một dòng" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 font-mono text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                            @error('winningCode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Số quyết định KQLCNT</label>
                            <input type="text" wire:model="decisionNo" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @error('decisionNo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Ngày ban hành quyết định</label>
                            <input type="date" wire:model="decisionDate" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @error('decisionDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4">
                        <div class="mb-3">
                            <p class="text-sm font-bold text-blue-950">Dữ liệu báo giá bổ sung thủ công</p>
                            <p class="mt-1 text-xs text-blue-700">Ba trường này không có từ nguồn đồng bộ Mua sắm công và được lưu riêng trên bản ghi để dùng khi xuất Excel/BBG.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">STT TT20/2022</label>
                                <input type="text" wire:model="sttTt202022" placeholder="Ví dụ: 125" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('sttTt202022')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Giá KK / KKL</label>
                                <input type="number" min="0" step="0.0001" wire:model="giaKkKkl" placeholder="0" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('giaKkKkl')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Đơn giá (VAT)</label>
                                <input type="number" min="0" step="0.0001" wire:model="donGiaVat" placeholder="0" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('donGiaVat')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                        Thông tin thuốc, hoạt chất, giá nguồn, số lượng và dữ liệu gốc vẫn giữ nguyên snapshot đã đồng bộ. Chỉ các trường KQLCNT và dữ liệu báo giá bổ sung ở trên được chỉnh thủ công.
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4">
                    <button type="button" wire:click="closeEdit" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Hủy</button>
                    <button type="button" wire:click="saveEdit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lưu cập nhật</button>
                </div>
            </div>
        </div>
    @endif

    @include('Muasamcong::livewire.partials.synced-export-config-modal')
</div>

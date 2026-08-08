<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Quản lý Hồ sơ Thuốc</h1>
            <p class="text-sm text-gray-500 mt-1">Danh mục thông tin chi tiết các sản phẩm dược phẩm và hồ sơ pháp lý trong hệ thống.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.pharma.hssp.create') }}"
               class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-sm text-white hover:bg-blue-700 transition-colors shadow-sm gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm thuốc mới
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    @livewire('shared.import-export.panel', [
        'serviceClass' => \Modules\Pharma\Services\MedicineImportExport::class,
        'title' => 'Import / Export hồ sơ thuốc',
        'description' => 'Dùng file Excel chuẩn A–U; dữ liệu rỗng không ghi đè giá trị hiện có.',
        'filters' => [
            'search' => $search,
            'circular_group' => $filterCircularGroup,
            'is_special_control' => $filterSpecialControl === '' ? null : $filterSpecialControl === 'yes',
        ],
    ], key('medicine-import-export-' . md5(json_encode([$search, $filterCircularGroup, $filterSpecialControl]))))

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-6">
                <label class="text-sm font-medium text-gray-600 block">Tìm kiếm</label>
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Nhập tên thuốc hoặc hoạt chất để tìm kiếm..."
                           class="w-full rounded-xl border border-gray-300 pl-11 pr-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    <div class="absolute inset-y-0 left-0 pl-4 mt-1 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="md:col-span-3">
                <label class="text-sm font-medium text-gray-600 block">Phân nhóm Thông tư</label>
                <select wire:model.live="filterCircularGroup" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow bg-white">
                    <option value="">Tất cả phân nhóm</option>
                    @foreach($circularGroups as $group)
                        @if($group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="text-sm font-medium text-gray-600 block">Loại kiểm soát</label>
                <select wire:model.live="filterSpecialControl" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow bg-white">
                    <option value="">Tất cả danh mục</option>
                    <option value="yes">Kiểm soát đặc biệt (KSĐB)</option>
                    <option value="no">Thuốc thường</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end pt-2 border-t border-gray-100">
            <div class="md:col-span-4">
                <label class="text-sm font-medium text-gray-600 block">Hiển thị dữ liệu</label>
                <select wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow bg-white">
                    <option value="10">10 bản ghi / trang</option>
                    <option value="20">20 bản ghi / trang</option>
                    <option value="50">50 bản ghi / trang</option>
                    <option value="All">Hiển thị tất cả (All)</option>
                </select>
            </div>

            <div class="md:col-span-6"></div>

            <div class="md:col-span-2">
                <button type="button"
                        wire:click="resetFilters"
                        class="w-full inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-colors gap-2">
                    Xóa bộ lọc
                </button>
            </div>
        </div>
    </div>

    @if(!empty($selectedIds))
        <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 flex items-center justify-between transition-all">
            <div class="text-sm text-gray-700 font-medium">
                Đang chọn <span class="text-blue-600 font-bold">{{ count($selectedIds) }}</span> bản ghi thuốc trên trang này.
            </div>
            <button type="button"
                    wire:click="deleteSelected"
                    wire:confirm="Bạn có chắc chắn muốn xóa hàng loạt các bản ghi thuốc đã chọn không?"
                    class="inline-flex items-center justify-center rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 font-semibold text-sm text-rose-700 hover:bg-rose-100 transition-colors gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Xóa các mục đã chọn
            </button>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="py-4 px-6 text-center w-12">
                            <input type="checkbox" wire:model.live="selectAll" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="py-4 px-6 text-center w-16">STT</th>
                        <th class="py-4 px-6">Thông tin thuốc</th>
                        <th class="py-4 px-6">Hoạt chất & Hàm lượng</th>
                        <th class="py-4 px-6">Số đăng ký</th>
                        <th class="py-4 px-6">Xuất xứ</th>
                        <th class="py-4 px-6">Hồ sơ</th>
                        <th class="py-4 px-6 text-right w-24">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse($medicines as $index => $medicine)
                        <tr class="hover:bg-gray-50/70 transition-colors {{ in_array($medicine->id, $selectedIds) ? 'bg-blue-50/40 hover:bg-blue-50/60' : '' }}">
                            <td class="py-4 px-6 text-center">
                                <input type="checkbox" wire:model.live="selectedIds" value="{{ $medicine->id }}" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </td>
                            <td class="py-4 px-6 text-center text-gray-500 font-medium">
                                {{ $perPage === 'All' ? $index + 1 : ($medicines->currentPage() - 1) * $medicines->perPage() + $index + 1 }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-900 flex items-center gap-2">
                                    {{ $medicine->name }}
                                    @if($medicine->is_special_control)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-100">KSĐB</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ $medicine->dosage_form }} • {{ $medicine->packaging_specification }}
                                    @if($medicine->circular_group)
                                        • <span class="text-indigo-600 font-medium">Nhóm: {{ $medicine->circular_group }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-800 truncate max-w-xs" title="{{ $medicine->active_ingredients }}">
                                    {{ $medicine->active_ingredients }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">Hàm lượng: {{ $medicine->concentration }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $medicine->registration_number }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-800">{{ $medicine->manufacturing_country }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs" title="{{ $medicine->manufacturing_company }}">
                                    {{ $medicine->manufacturing_company }}
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if($medicine->profile_link)
                                    <a href="{{ $medicine->profile_link }}"
                                       target="_blank"
                                       class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                        Xem tài liệu
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pharma.hssp.edit', $medicine->id) }}"
                                       class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                       title="Chỉnh sửa">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                            wire:click="deleteMedicine({{ $medicine->id }})"
                                            wire:confirm="Bạn có chắc chắn muốn xóa vĩnh viễn hồ sơ thuốc này khỏi hệ thống không?"
                                            class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Xóa dữ liệu">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2m0-5a7 7 0 1114 0 7 7 0 01-14 0z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-900">Không tìm thấy kết quả</p>
                                    <p class="text-xs text-gray-500">Thử thay đổi từ khóa tìm kiếm hoặc kiểm tra lại các bộ lọc lọc của bạn.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($perPage !== 'All' && $medicines->hasPages())
            <div class="bg-white border-t border-gray-200 px-4 py-4 sm:px-6 flex items-center justify-between">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs text-gray-500">
                            Hiển thị từ <span class="font-medium text-gray-700">{{ $medicines->firstItem() }}</span> đến <span class="font-medium text-gray-700">{{ $medicines->lastItem() }}</span> trong tổng số <span class="font-medium text-gray-700">{{ $medicines->total() }}</span> bản ghi
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px" aria-label="Pagination">
                            <button type="button"
                                    wire:click="gotoPage({{ $medicines->currentPage() - 1 }})"
                                    @if($medicines->onFirstPage()) disabled @endif
                                    class="relative inline-flex items-center px-3 py-2 rounded-l-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:hover:bg-white">
                                <span class="sr-only">Trước</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>

                            <button type="button"
                                    wire:click="gotoPage({{ $medicines->currentPage() + 1 }})"
                                    @if(!$medicines->hasMorePages()) disabled @endif
                                    class="relative inline-flex items-center px-3 py-2 rounded-r-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:hover:bg-white">
                                <span class="sr-only">Sau</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        @elseif($perPage === 'All' && $medicines->total() > 0)
            <div class="bg-white border-t border-gray-200 px-4 py-4 sm:px-6 text-center">
                <p class="text-xs text-gray-500">
                    Đang hiển thị toàn bộ <span class="font-medium text-gray-700">{{ $medicines->total() }}</span> bản ghi thuốc có trên hệ thống.
                </p>
            </div>
        @endif
    </div>
</div>

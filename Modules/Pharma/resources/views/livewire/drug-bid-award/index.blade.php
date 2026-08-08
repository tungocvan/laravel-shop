<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Quản lý Thuốc Trúng Thầu</h1>
            <p class="text-sm text-gray-500 mt-1">Quản lý thông tin kết quả trúng thầu y tế của các sản phẩm dược phẩm
                tại các cơ sở bệnh viện.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.pharma.drug-bid-awards.create') }}"
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-sm text-white hover:bg-blue-700 transition-colors shadow-sm gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Thêm hồ sơ mới
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl text-sm">
            {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    @livewire('shared.import-export.panel', [
        'serviceClass' => \Modules\Pharma\Services\DrugBidAwardImportExport::class,
        'title' => 'Import / Export thuốc trúng thầu',
        'description' => 'Dùng file chuẩn A–L; dữ liệu rỗng không ghi đè giá trị hiện có.',
        'filters' => [
            'search' => $search,
            'investor' => $filterInvestor,
            'company' => $filterCompany,
        ],
    ], key('drug-bid-award-import-export-' . md5(json_encode([$search, $filterInvestor, $filterCompany]))))

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-6">
                <label class="text-sm font-medium text-gray-600 block">Tìm kiếm thông tin</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Tìm theo tên thuốc, mã mời thầu, số quyết định..."
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
            </div>

            <div class="md:col-span-3">
                <label class="text-sm font-medium text-gray-600 block">Chủ đầu tư</label>
                <div class="mt-1">
                    <x-select-search id="filter-investor-id" wire:model.live="filterInvestor"
                        placeholder="Tất cả chủ đầu tư">
                        <option value="">Tất cả chủ đầu tư</option>
                        @foreach ($investors as $investor)
                            <option value="{{ $investor }}">{{ $investor }}</option>
                        @endforeach
                    </x-select-search>
                </div>
            </div>

            <div class="md:col-span-3">
                <label class="text-sm font-medium text-gray-600 block">Nhà thầu trúng thầu</label>
                <div class="mt-1">
                    <x-select-search id="filter-company-id" wire:model.live="filterCompany"
                        placeholder="Tất cả nhà thầu">
                        <option value="">Tất cả nhà thầu</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company }}">{{ $company }}</option>
                        @endforeach
                    </x-select-search>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            <div class="w-64">
                <select wire:model.live="perPage"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow bg-white">
                    <option value="10">10 bản ghi / trang</option>
                    <option value="20">20 bản ghi / trang</option>
                    <option value="50">50 bản ghi / trang</option>
                    <option value="All">Hiển thị tất cả (All)</option>
                </select>
            </div>
            <button type="button" wire:click="resetFilters"
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-colors">Xóa
                bộ lọc</button>
        </div>
    </div>

    @if (!empty($selectedIds))
        <div
            class="bg-indigo-50 border border-indigo-100 rounded-2xl px-6 py-4 flex items-center justify-between shadow-sm animate-fadeIn">
            <div class="text-sm text-indigo-900 font-medium">Đang lựa chọn hàng loạt <span
                    class="text-indigo-600 font-bold">{{ count($selectedIds) }}</span> hồ sơ trúng thầu trên trang này.
            </div>
            <button type="button" wire:click="deleteSelected"
                wire:confirm="Xác nhận xóa diện rộng các bản ghi thầu đã chọn?"
                class="inline-flex items-center justify-center rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 font-semibold text-sm text-rose-700 hover:bg-rose-100 transition-colors gap-2">Xóa
                mục đã chọn</button>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap min-w-full text-sm">
                <thead>
                    <tr
                        class="bg-gray-50/75 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="py-4 px-6 text-center w-12"><input type="checkbox" wire:model.live="selectAll"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="py-4 px-6">Thông tin thuốc trúng thầu</th>
                        <th class="py-4 px-6">Khối lượng & Đơn giá</th>
                        <th class="py-4 px-6">Chủ đầu tư (Bệnh viện)</th>
                        <th class="py-4 px-6">Pháp lý gói thầu</th>
                        <th class="py-4 px-6">Đơn vị trúng thầu</th>
                        <th class="py-4 px-6 text-right w-24">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                    @forelse($awards as $award)
                        <tr
                            class="hover:bg-gray-50/50 transition-colors {{ in_array($award->id, $selectedIds) ? 'bg-indigo-50/30' : '' }}">
                            <td class="py-4 px-6 text-center"><input type="checkbox" wire:model.live="selectedIds"
                                    value="{{ $award->id }}"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-900">{{ $award->medicine_name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">Quy cách:
                                    {{ $award->packaging_specification }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-800">{{ number_format($award->quantity) }} đơn vị
                                </div>
                                <div class="text-xs text-indigo-600 font-medium mt-0.5">Giá:
                                    {{ number_format($award->unit_price, 0, ',', '.') }} VNĐ</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-800 max-w-xs truncate"
                                    title="{{ $award->investor_name }}">{{ $award->investor_name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">Mã mời thầu: <span
                                        class="font-mono">{{ $award->bidding_notice_code }}</span></div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-800">QĐ: {{ $award->decision_number }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">Ngày ký:
                                    {{ date('d/m/Y', strtotime($award->decision_date)) }} • Hạn:
                                    {{ $award->contract_duration_months }}T</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900 max-w-xs truncate"
                                    title="{{ $award->winning_company_name }}">{{ $award->winning_company_name }}
                                </div>
                                @if ($award->decision_document_url)
                                    <a href="{{ $award->decision_document_url }}" target="_blank"
                                        class="inline-flex items-center text-xs text-blue-600 hover:underline mt-1 gap-1">Vanbanphaply.pdf</a>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pharma.drug-bid-awards.edit', $award->id) }}"
                                        class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"><svg
                                            class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg></a>
                                    <button type="button" wire:click="deleteAward({{ $award->id }})"
                                        wire:confirm="Xác nhận xóa vĩnh viễn dòng kết quả trúng thầu này?"
                                        class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"><svg
                                            class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center text-gray-500">Hệ thống chưa ghi nhận hồ
                                sơ hoặc kết quả tìm kiếm không phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- XÓA HOẶC THAY THẾ ĐOẠN {{ $awards->links() }} BẰNG KHỐI DƯỚI ĐÂY --}}
        @if ($perPage !== 'All' && $awards->hasPages())
            <div class="bg-white border-t border-gray-100 px-6 py-4 flex items-center justify-between">
                <div class="text-xs font-medium text-gray-500">
                    Hiển thị từ <span class="font-semibold text-gray-700">{{ $awards->firstItem() }}</span>
                    đến <span class="font-semibold text-gray-700">{{ $awards->lastItem() }}</span>
                    trong số <span class="font-semibold text-gray-700">{{ $awards->total() }}</span> bản ghi
                </div>

                <div class="inline-flex items-center gap-2">
                    {{-- Nút quay lại (Previous) --}}
                    @if ($awards->onFirstPage())
                        <button type="button" disabled
                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-400 cursor-not-allowed">
                            Trước
                        </button>
                    @else
                        <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                            Trước
                        </button>
                    @endif

                    {{-- Hiển thị số trang hiện tại / tổng số trang ngắn gọn --}}
                    <span class="text-xs font-medium text-gray-600 px-2">
                        Trang {{ $awards->currentPage() }} / {{ $awards->lastPage() }}
                    </span>

                    {{-- Nút tiếp theo (Next) --}}
                    @if ($awards->hasMorePages())
                        <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                            Tiếp
                        </button>
                    @else
                        <button type="button" disabled
                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-400 cursor-not-allowed">
                            Tiếp
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

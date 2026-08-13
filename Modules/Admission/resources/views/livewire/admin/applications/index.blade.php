<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
    @if (session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @error('documents')
        <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            {{ $message }}
        </div>
    @enderror

    @if ($documentBatch)
        <div wire:poll.2s="$refresh" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 sm:p-5 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="font-semibold text-indigo-900">Tiến độ tạo tài liệu</div>
                    <div class="text-sm text-indigo-800 mt-1">
                        Tổng {{ $documentBatch->totalJobs }} · Đã xử lý {{ $documentBatch->processedJobs() }} · Lỗi {{ $documentBatch->failedJobs }} · Còn lại {{ $documentBatch->pendingJobs }}
                    </div>
                </div>
                <div class="text-sm font-bold text-indigo-900">{{ $documentBatch->progress() }}%</div>
            </div>
            <div class="h-2.5 rounded-full bg-indigo-100 overflow-hidden">
                <div class="h-full bg-indigo-600 transition-all" style="width: {{ $documentBatch->progress() }}%"></div>
            </div>
            <div class="text-xs text-indigo-700">
                Queue: <strong>admission-documents</strong>
                @if ($documentBatch->finished())
                    · Hoàn tất batch
                @elseif ($documentBatch->cancelled())
                    · Batch đã hủy
                @else
                    · Đang xử lý nền
                @endif
            </div>
        </div>
    @endif

    @if (session('import_summary'))
        @php($summary = session('import_summary'))
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 sm:p-5 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="font-semibold text-blue-900">Kết quả Import #{{ $summary['run_id'] }}</div>
                    <div class="text-sm text-blue-800 mt-1">
                        Tổng {{ $summary['total'] }} · Thành công {{ $summary['success'] }} · Lỗi {{ $summary['failed'] }} · Tạo mới {{ $summary['created'] }} · Cập nhật {{ $summary['updated'] }}
                        @if ($summary['restore_status'] ?? false)
                            · Đã khôi phục trạng thái
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (($summary['failed'] ?? 0) > 0)
                        <a href="{{ route('admin.admission.imports.errors', $summary['run_id']) }}"
                           class="inline-flex items-center px-4 h-10 rounded-xl bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700">
                            Xem {{ $summary['failed'] }} lỗi Import
                        </a>
                    @endif
                    <a href="{{ route('admin.admission.imports.index') }}"
                       class="inline-flex items-center px-4 h-10 rounded-xl border border-blue-300 bg-white text-blue-700 text-sm font-semibold hover:bg-blue-100">
                        Lịch sử Import
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Hồ sơ tuyển sinh</h2>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100">
                {{ $applications->total() }} hồ sơ
            </span>

            <label class="sr-only" for="admission-per-page">Số hồ sơ mỗi trang</label>
            <select id="admission-per-page" wire:model.live="perPage"
                class="h-10 px-3 rounded-xl border border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
        <label class="sr-only" for="admission-search">Tìm hồ sơ</label>
        <input id="admission-search" type="text" wire:model.live.debounce.300ms="search"
            placeholder="Tìm tên, CCCD, SĐT..."
            class="w-full h-11 px-4 rounded-xl border border-gray-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">

        <label class="sr-only" for="admission-class-filter">Lọc loại lớp</label>
        <select id="admission-class-filter" wire:model.live="filterClass"
            class="w-full h-11 px-4 rounded-xl border border-gray-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
            <option value="">Tất cả lớp</option>
            <option value="Lớp thường">Lớp thường</option>
            <option value="Tăng cường Tiếng Anh">Tăng cường Tiếng Anh</option>
            <option value="Tích hợp">Tích hợp</option>
            <option value="Tăng cường TA + Toán và Khoa học">Tăng cường TA + Toán & Khoa học</option>
        </select>

        <label class="sr-only" for="admission-status-filter">Lọc trạng thái</label>
        <select id="admission-status-filter" wire:model.live="filterStatus"
            class="w-full h-11 px-4 rounded-xl border border-gray-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
            <option value="">Tất cả trạng thái</option>
            <option value="import">Import</option>
            <option value="pending">Chờ duyệt</option>
            <option value="approved">Đã duyệt</option>
            <option value="rejected">Từ chối</option>
        </select>
    </div>

    @can('download_admission_documents')
        @if ($filterStatus === 'approved' || count($selected) > 0)
            <div class="bg-white border border-emerald-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Định dạng tài liệu</div>
                    <div class="text-xs text-gray-500 mt-1">DOCX được chọn mặc định. Tick PDF khi bạn muốn xuất thêm PDF cho batch này.</div>
                </div>
                <div class="flex items-center gap-5">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" wire:model.live="generateDocx"
                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        DOCX
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" wire:model.live="generatePdf"
                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        PDF
                    </label>
                </div>
            </div>
        @endif

        @if ($filterStatus === 'approved')
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-emerald-900">Tài liệu hồ sơ đã duyệt</div>
                    <div class="text-xs text-emerald-700 mt-1">
                        Tạo các định dạng còn thiếu theo toàn bộ bộ lọc hiện tại bằng batch queue riêng.
                    </div>
                </div>
                <button type="button" wire:click="generateDocuments"
                    wire:confirm="Đưa tất cả hồ sơ Đã duyệt đang thiếu định dạng đã chọn vào batch tạo tài liệu?"
                    wire:loading.attr="disabled" wire:target="generateDocuments"
                    class="inline-flex items-center justify-center px-4 h-11 rounded-xl bg-emerald-700 text-white text-sm font-semibold hover:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="generateDocuments">Tạo file còn thiếu</span>
                    <span wire:loading wire:target="generateDocuments">Đang tạo batch...</span>
                </button>
            </div>
        @endif

        @if (count($selected) > 0)
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-emerald-900">Tạo tài liệu cho hồ sơ đã chọn</div>
                    <div class="text-xs text-emerald-700 mt-1">
                        Đã chọn {{ count($selected) }} hồ sơ. Chỉ hồ sơ Đã duyệt và thiếu định dạng bạn chọn mới được đưa vào batch.
                    </div>
                </div>
                <button type="button" wire:click="generateSelectedDocuments"
                    wire:confirm="Tạo các định dạng đã chọn cho những hồ sơ Đã duyệt đang được tick?"
                    wire:loading.attr="disabled" wire:target="generateSelectedDocuments"
                    class="inline-flex items-center justify-center px-4 h-11 rounded-xl bg-emerald-700 text-white text-sm font-semibold hover:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="generateSelectedDocuments">Tạo file đã chọn ({{ count($selected) }})</span>
                    <span wire:loading wire:target="generateSelectedDocuments">Đang tạo batch...</span>
                </button>
            </div>
        @endif
    @endcan

    @canany(['export_admission', 'import_admission'])
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            @can('export_admission')
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export"
                        class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm">
                        <span wire:loading.remove wire:target="export">Export Excel</span>
                        <span wire:loading wire:target="export">Đang xuất...</span>
                    </button>
                    <span class="text-xs text-gray-500">Xuất dữ liệu theo bộ lọc hiện tại</span>
                </div>
            @endcan

            @can('import_admission')
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <a href="{{ route('admin.admission.imports.index') }}"
                       class="inline-flex items-center justify-center px-4 h-11 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Lịch sử Import
                    </a>
                    <form action="{{ route('admin.admission.import') }}" method="POST" enctype="multipart/form-data"
                        x-data="{ fileName: '' }" class="flex flex-col gap-2">
                        @csrf
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <label class="flex items-center gap-2 px-3 h-11 rounded-xl border border-gray-300 bg-white text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition-colors">
                                <span x-text="fileName || 'Chọn file Excel'"></span>
                                <input type="file" name="file" class="hidden" accept=".xlsx,.xls"
                                    @change="fileName = $event.target.files[0]?.name">
                            </label>
                            <button type="submit" :disabled="!fileName"
                                class="inline-flex items-center justify-center px-4 h-11 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                Import
                            </button>
                        </div>
                        @can('approve_admission')
                            <label class="inline-flex items-start gap-2 text-xs text-gray-600 max-w-lg">
                                <input type="checkbox" name="restore_status" value="1"
                                    class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>
                                    Khôi phục trạng thái từ file export (pending/approved/rejected/import). Chỉ dùng khi phục hồi dữ liệu đã export từ hệ thống.
                                </span>
                            </label>
                        @endcan
                    </form>
                </div>
            @endcan
        </div>
    @endcanany

    @can('delete_admission')
        @if (count($selected) > 0)
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 bg-rose-50 border border-rose-100 p-4 rounded-2xl">
                <div>
                    <div class="text-sm font-semibold text-rose-900">Xóa hồ sơ đã chọn</div>
                    <div class="text-xs text-rose-700 mt-1">Đã chọn {{ count($selected) }} hồ sơ.</div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Bạn có chắc muốn xóa các hồ sơ đã chọn? Hành động này không thể hoàn tác."
                        wire:loading.attr="disabled" wire:target="deleteSelected"
                        class="px-4 py-2 bg-rose-600 text-white rounded-xl text-sm font-semibold hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="deleteSelected">Xóa đã chọn ({{ count($selected) }})</span>
                        <span wire:loading wire:target="deleteSelected">Đang xóa...</span>
                    </button>
                    <button type="button" wire:click="$set('selected', [])"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50">Bỏ chọn</button>
                </div>
            </div>
        @endif

        @if ($applications->total() > 0)
            <div class="flex justify-end">
                <button type="button" wire:click="deleteAll"
                    wire:confirm="CẢNH BÁO: Bạn sắp xóa TOÀN BỘ hồ sơ tuyển sinh. Tất cả dữ liệu hồ sơ và file PDF/Word liên quan sẽ bị xóa, ID sẽ quay về 1. Hành động này KHÔNG THỂ hoàn tác. Bạn có chắc chắn?"
                    wire:loading.attr="disabled" wire:target="deleteAll"
                    class="px-4 py-2 bg-red-800 text-white rounded-xl text-sm font-semibold hover:bg-red-900 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="deleteAll">Xóa tất cả</span>
                    <span wire:loading wire:target="deleteAll">Đang xóa tất cả...</span>
                </button>
            </div>
        @endif
    @endcan

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm align-middle">
                <thead class="bg-gray-50/75 border-b border-gray-200">
                    <tr>
                        @canany(['delete_admission', 'download_admission_documents'])
                            <th class="px-6 py-4">
                                <label class="sr-only" for="admission-select-all">Chọn tất cả hồ sơ trên trang</label>
                                <input id="admission-select-all" type="checkbox" wire:model.live="selectAll"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                        @endcanany
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Học sinh</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Lớp</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Loại lớp</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Ngày sinh</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Trạng thái</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-600">Thao tác</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($applications as $item)
                        <tr wire:key="admission-application-{{ $item->id }}" class="hover:bg-gray-50/50">
                            @canany(['delete_admission', 'download_admission_documents'])
                                <td class="px-6 py-4">
                                    <label class="sr-only" for="admission-select-{{ $item->id }}">Chọn hồ sơ {{ $item->ho_va_ten_hoc_sinh }}</label>
                                    <input id="admission-select-{{ $item->id }}" type="checkbox" value="{{ $item->id }}"
                                        wire:model.live="selected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                            @endcanany

                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $item->ho_va_ten_hoc_sinh }}</div>
                                <div class="text-xs text-gray-500">{{ $item->ma_dinh_danh }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><span class="inline-flex px-2.5 py-1 rounded-md text-xs bg-gray-100">{{ $item->lop }}</span></td>
                            <td class="px-6 py-4 text-gray-600"><span class="inline-flex px-2.5 py-1 rounded-md text-xs bg-gray-100">{{ $item->loai_lop_dang_ky }}</span></td>
                            <td class="px-6 py-4 text-gray-500">{{ $item->ngay_sinh ? \Carbon\Carbon::parse($item->ngay_sinh)->format('d/m/Y') : '' }}</td>
                            <td class="px-6 py-4">
                                @if (blank($item->status) || $item->status === 'pending')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-1 text-xs bg-amber-100 text-amber-800 rounded-full">Chờ duyệt</span>
                                        @can('approve_admission')
                                            <button type="button" wire:click="approve({{ $item->id }})" wire:loading.attr="disabled" wire:target="approve({{ $item->id }})"
                                                class="text-emerald-600 hover:text-emerald-700 disabled:opacity-50 text-xs font-medium">Duyệt</button>
                                        @endcan
                                        @can('reject_admission')
                                            <button type="button" wire:click="reject({{ $item->id }})" wire:loading.attr="disabled" wire:target="reject({{ $item->id }})"
                                                class="text-rose-600 hover:text-rose-700 disabled:opacity-50 text-xs font-medium">Từ chối</button>
                                        @endcan
                                    </div>
                                @elseif ($item->status === 'approved')
                                    <span class="px-2.5 py-1 text-xs bg-emerald-100 text-emerald-800 rounded-full">Đã duyệt</span>
                                    @can('download_admission_documents')
                                        <a href="{{ route('admission.receipt', $item->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-orange-600 hover:bg-orange-50 rounded-lg transition">Biên nhận</a>
                                    @endcan
                                @elseif ($item->status === 'rejected')
                                    <span class="px-2.5 py-1 text-xs bg-rose-100 text-rose-800 rounded-full">Từ chối</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">Import</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                @can('edit_admission')
                                    <a href="{{ route('admin.admission.edit', $item->id) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition">Chi tiết</a>
                                @endcan

                                @can('download_admission_documents')
                                    @if ($item->pdf_path && Storage::disk('local')->exists($item->pdf_path) && $item->status === 'approved')
                                        <a href="{{ route('admission.download', ['id' => $item->id, 'type' => 'pdf']) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg transition">PDF</a>
                                    @endif
                                    @if ($item->word_path && Storage::disk('local')->exists($item->word_path) && $item->status === 'approved')
                                        <a href="{{ route('admission.download', ['id' => $item->id, 'type' => 'word']) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">Word</a>
                                    @endif
                                @endcan

                                @can('delete_admission')
                                    <button type="button" wire:click="delete({{ $item->id }})"
                                        wire:confirm="Bạn có chắc muốn xóa hồ sơ này?" wire:loading.attr="disabled" wire:target="delete({{ $item->id }})"
                                        class="text-rose-500 hover:text-rose-700 disabled:opacity-50 text-sm">Xóa</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                Không có hồ sơ phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
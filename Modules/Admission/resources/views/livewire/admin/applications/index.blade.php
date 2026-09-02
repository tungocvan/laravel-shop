<div class="w-full space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @error('documents')
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $message }}</div>
    @enderror

    @if ($documentBatch)
        <section wire:poll.2s="$refresh" class="space-y-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 sm:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-indigo-900">Tiến độ tạo tài liệu</h3>
                    <p class="mt-1 text-sm text-indigo-800">Tổng {{ $documentBatch->totalJobs }} · Đã xử lý {{ $documentBatch->processedJobs() }} · Lỗi {{ $documentBatch->failedJobs }} · Còn lại {{ $documentBatch->pendingJobs }}</p>
                </div>
                <strong class="text-sm text-indigo-900">{{ $documentBatch->progress() }}%</strong>
            </div>
            <div class="h-2.5 overflow-hidden rounded-full bg-indigo-100"><div class="h-full bg-indigo-600 transition-all" style="width: {{ $documentBatch->progress() }}%"></div></div>
        </section>
    @endif

    @if (session('import_summary'))
        @php($summary = session('import_summary'))
        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-blue-900">Kết quả Import #{{ $summary['run_id'] }}</h3>
                    <p class="mt-1 text-sm text-blue-800">Tổng {{ $summary['total'] }} · Thành công {{ $summary['success'] }} · Lỗi {{ $summary['failed'] }} · Tạo mới {{ $summary['created'] }} · Cập nhật {{ $summary['updated'] }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (($summary['failed'] ?? 0) > 0)
                        <a href="{{ route('admin.admission.imports.errors', $summary['run_id']) }}" class="inline-flex h-10 items-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700">Xem {{ $summary['failed'] }} lỗi</a>
                    @endif
                    <a href="{{ route('admin.admission.imports.index') }}" class="inline-flex h-10 items-center rounded-xl border border-blue-300 bg-white px-4 text-sm font-semibold text-blue-700 hover:bg-blue-100">Lịch sử Import</a>
                </div>
            </div>
        </section>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Hồ sơ tuyển sinh</h2>
            <p class="mt-1 text-sm text-gray-500">Quản lý, duyệt và theo dõi toàn bộ hồ sơ đăng ký nhập học.</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">{{ $applications->total() }} hồ sơ</span>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Bộ lọc tìm kiếm</h3>
                    <p class="mt-1 text-xs text-gray-500">Lọc theo học sinh, loại lớp và trạng thái hồ sơ.</p>
                </div>
                @if ($search !== '' || $filterClass !== '' || $filterStatus !== '')
                    <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Đặt lại bộ lọc</button>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label class="sr-only" for="admission-search">Tìm hồ sơ</label>
                    <input id="admission-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm tên, mã định danh, SĐT..." class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </div>
                <div class="lg:col-span-4">
                    <label class="sr-only" for="admission-class-filter">Lọc loại lớp</label>
                    <select id="admission-class-filter" wire:model.live="filterClass" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="">Tất cả lớp</option>
                        <option value="Lớp thường">Lớp thường</option>
                        <option value="Tăng cường Tiếng Anh">Tăng cường Tiếng Anh</option>
                        <option value="Tích hợp">Tích hợp</option>
                        <option value="Tăng cường TA + Toán và Khoa học">Tăng cường TA + Toán & Khoa học</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="sr-only" for="admission-status-filter">Lọc trạng thái</label>
                    <select id="admission-status-filter" wire:model.live="filterStatus" class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        <option value="import">Import</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                </div>
            </div>
        </div>

        @canany(['export_admission', 'import_admission'])
            <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50/60 p-4 sm:p-5 xl:flex-row xl:items-center xl:justify-between">
                @can('export_admission')
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="inline-flex h-10 items-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="export">Export Excel</span><span wire:loading wire:target="export">Đang xuất...</span>
                        </button>
                        <span class="text-xs text-gray-500">Xuất dữ liệu theo bộ lọc hiện tại</span>
                    </div>
                @endcan

                @can('import_admission')
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                        <a href="{{ route('admin.admission.imports.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Lịch sử Import</a>
                        <form action="{{ route('admin.admission.import') }}" method="POST" enctype="multipart/form-data" x-data="{ fileName: '' }" class="flex flex-col gap-2">
                            @csrf
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label class="inline-flex h-10 cursor-pointer items-center rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-600 hover:bg-gray-50"><span x-text="fileName || 'Chọn file Excel'"></span><input type="file" name="file" class="hidden" accept=".xlsx,.xls" @change="fileName = $event.target.files[0]?.name"></label>
                                <button type="submit" :disabled="!fileName" class="inline-flex h-10 items-center justify-center rounded-xl bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50">Import</button>
                            </div>
                            @can('approve_admission')
                                <label class="inline-flex max-w-xl items-start gap-2 text-xs text-gray-600"><input type="checkbox" name="restore_status" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span>Khôi phục trạng thái từ file export khi phục hồi dữ liệu.</span></label>
                            @endcan
                        </form>
                    </div>
                @endcan
            </div>
        @endcanany
    </section>

    @can('download_admission_documents')
        @if ($filterStatus === 'approved' || count($selected) > 0)
            <section class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="text-sm font-semibold text-emerald-900">Tạo tài liệu hồ sơ</h3><p class="mt-1 text-xs text-emerald-700">Chọn định dạng cần tạo cho hồ sơ đã duyệt.</p></div>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" wire:model.live="generateDocx" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">DOCX</label>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" wire:model.live="generatePdf" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">PDF</label>
                    @if (count($selected) > 0)
                        <button type="button" wire:click="generateSelectedDocuments" wire:confirm="Tạo các định dạng đã chọn cho những hồ sơ Đã duyệt đang được tick?" wire:loading.attr="disabled" wire:target="generateSelectedDocuments" class="h-10 rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Tạo file đã chọn ({{ count($selected) }})</button>
                    @elseif ($filterStatus === 'approved')
                        <button type="button" wire:click="generateDocuments" wire:confirm="Đưa tất cả hồ sơ Đã duyệt đang thiếu định dạng đã chọn vào batch tạo tài liệu?" wire:loading.attr="disabled" wire:target="generateDocuments" class="h-10 rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">Tạo file còn thiếu</button>
                    @endif
                </div>
            </section>
        @endif
    @endcan

    @can('delete_admission')
        @if (count($selected) > 0)
            <section class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="text-sm font-semibold text-rose-900">Đã chọn {{ count($selected) }} hồ sơ</h3><p class="mt-1 text-xs text-rose-700">Thao tác xóa không thể hoàn tác.</p></div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="deleteSelected" wire:confirm="Bạn có chắc muốn xóa các hồ sơ đã chọn? Hành động này không thể hoàn tác." wire:loading.attr="disabled" wire:target="deleteSelected" class="h-10 rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700">Xóa đã chọn</button>
                    <button type="button" wire:click="$set('selected', [])" class="h-10 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Bỏ chọn</button>
                </div>
            </section>
        @endif
    @endcan

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="text-sm text-gray-600"><span class="font-semibold text-gray-900">Danh sách hồ sơ</span> · {{ $applications->firstItem() ?? 0 }}–{{ $applications->lastItem() ?? 0 }} / {{ $applications->total() }}</div>
            <div class="flex flex-wrap items-center gap-3">
                @can('delete_admission')
                    @if ($applications->total() > 0)
                        <button type="button" wire:click="deleteAll" wire:confirm="CẢNH BÁO: Bạn sắp xóa TOÀN BỘ hồ sơ tuyển sinh. Hành động này KHÔNG THỂ hoàn tác. Bạn có chắc chắn?" wire:loading.attr="disabled" wire:target="deleteAll" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Xóa tất cả</button>
                    @endif
                @endcan
                <label for="admission-per-page" class="text-sm text-gray-500">Hiển thị</label>
                <select id="admission-per-page" wire:model.live="perPage" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm align-middle">
                <thead class="border-b border-gray-200 bg-gray-50/80">
                    <tr>
                        @canany(['delete_admission', 'download_admission_documents'])
                            <th class="w-12 px-4 py-3 text-center"><label class="sr-only" for="admission-select-all">Chọn tất cả hồ sơ trên trang</label><input id="admission-select-all" type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                        @endcanany
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Học sinh</th><th class="px-4 py-3 text-left font-semibold text-gray-600">Lớp</th><th class="px-4 py-3 text-left font-semibold text-gray-600">Loại lớp</th><th class="px-4 py-3 text-left font-semibold text-gray-600">Ngày sinh</th><th class="px-4 py-3 text-left font-semibold text-gray-600">Trạng thái</th><th class="px-4 py-3 text-right font-semibold text-gray-600">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($applications as $item)
                        <tr wire:key="admission-application-{{ $item->id }}" class="hover:bg-gray-50/70">
                            @canany(['delete_admission', 'download_admission_documents'])
                                <td class="px-4 py-3 text-center"><label class="sr-only" for="admission-select-{{ $item->id }}">Chọn hồ sơ {{ $item->ho_va_ten_hoc_sinh }}</label><input id="admission-select-{{ $item->id }}" type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                            @endcanany
                            <td class="px-4 py-3"><div class="font-semibold text-gray-900">{{ $item->ho_va_ten_hoc_sinh }}</div><div class="mt-0.5 text-xs text-gray-500">{{ $item->ma_dinh_danh }}</div></td>
                            <td class="px-4 py-3 text-gray-600"><span class="inline-flex rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $item->lop }}</span></td>
                            <td class="px-4 py-3 text-gray-600"><span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs">{{ $item->loai_lop_dang_ky }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $item->ngay_sinh ? \Carbon\Carbon::parse($item->ngay_sinh)->format('d/m/Y') : '' }}</td>
                            <td class="px-4 py-3">
                                @if (blank($item->status) || $item->status === 'pending')
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Chờ duyệt</span>@can('approve_admission')<button type="button" wire:click="approve({{ $item->id }})" wire:loading.attr="disabled" wire:target="approve({{ $item->id }})" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Duyệt</button>@endcan @can('reject_admission')<button type="button" wire:click="reject({{ $item->id }})" wire:loading.attr="disabled" wire:target="reject({{ $item->id }})" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Từ chối</button>@endcan</div>
                                @elseif ($item->status === 'approved')
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800">Đã duyệt</span>@can('download_admission_documents')<a href="{{ route('admission.receipt', $item->id) }}" class="text-xs font-semibold text-orange-600 hover:text-orange-700">Biên nhận</a>@endcan</div>
                                @elseif ($item->status === 'rejected')<span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-800">Từ chối</span>
                                @else<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">Import</span>@endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @can('edit_admission')<a href="{{ route('admin.admission.edit', $item->id) }}" class="inline-flex rounded-lg px-2.5 py-1.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">Chi tiết</a>@endcan
                                @can('download_admission_documents')
                                    @if ($item->pdf_path && Storage::disk('local')->exists($item->pdf_path) && $item->status === 'approved')<a href="{{ route('admission.download', ['id' => $item->id, 'type' => 'pdf']) }}" class="inline-flex rounded-lg px-2.5 py-1.5 text-sm font-semibold text-emerald-600 hover:bg-emerald-50">PDF</a>@endif
                                    @if ($item->word_path && Storage::disk('local')->exists($item->word_path) && $item->status === 'approved')<a href="{{ route('admission.download', ['id' => $item->id, 'type' => 'word']) }}" class="inline-flex rounded-lg px-2.5 py-1.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">Word</a>@endif
                                @endcan
                                @can('delete_admission')<button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Bạn có chắc muốn xóa hồ sơ này?" wire:loading.attr="disabled" wire:target="delete({{ $item->id }})" class="inline-flex rounded-lg px-2.5 py-1.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Xóa</button>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-gray-500"><div class="font-medium text-gray-700">Không có hồ sơ phù hợp</div><div class="mt-1 text-sm">Hãy thử thay đổi hoặc đặt lại bộ lọc.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="border-t border-gray-200 bg-white px-4 py-4 sm:px-5">{{ $applications->links('Admission::vendor.pagination.admin-admission') }}</div>
        @endif
    </section>
</div>

<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">

    {{-- SUCCESS --}}
    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- ERROR --}}
    @if (session('error'))
        <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            {{ session('error') }}
        </div>
    @endif
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
            Hồ sơ tuyển sinh
        </h2>

        <div class="flex items-center gap-3">
            <span
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100">
                {{ method_exists($applications, 'total') ? $applications->total() : count($applications) }} hồ sơ
            </span>

            <select wire:model.live="perPage"
                class="h-10 px-3 rounded-xl border border-gray-300 text-sm shadow-sm
               focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="all">All</option>
            </select>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm tên, CCCD, SĐT..."
            class="w-full h-11 px-4 rounded-xl border border-gray-300 bg-white text-sm shadow-sm
focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">

        <select wire:model.live="filterClass"
            class="w-full h-11 px-4 rounded-xl border border-gray-300 bg-white text-sm shadow-sm
focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
            <option value="">Tất cả lớp</option>
            <option value="Lớp thường">Lớp thường</option>
            <option value="Tăng cường Tiếng Anh">Tăng cường Tiếng Anh</option>
            <option value="Tích hợp">Tích hợp</option>
            <option value="Tăng cường TA + Toán và Khoa học">Tăng cường TA + Toán & Khoa học</option>
        </select>

        <select wire:model.live="filterStatus"
            class="w-full h-11 px-4 rounded-xl border border-gray-300 bg-white text-sm shadow-sm
focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
            <option value="">Tất cả trạng thái</option>
            <option value="import">Import</option>
            <option value="pending">Chờ duyệt</option>
            <option value="approved">Đã duyệt</option>
            <option value="rejected">Từ chối</option>
        </select>
    </div>

    @can('create_admission')
        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            {{-- LEFT: EXPORT --}}
            <div class="flex items-center gap-3">
                <button wire:click="export"
                    class="inline-flex items-center gap-2 px-4 h-11 rounded-xl bg-blue-600 text-white text-sm font-semibold
                       hover:bg-blue-700 transition-colors shadow-sm">

                    {{-- icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 16v-8m0 0l-3 3m3-3l3 3M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                    </svg>

                    Export Excel
                </button>

                <span class="text-xs text-gray-500">
                    Xuất dữ liệu theo bộ lọc hiện tại
                </span>
            </div>

            {{-- RIGHT: IMPORT --}}
            <form action="{{ route('admin.admission.import') }}" method="POST" enctype="multipart/form-data"
                x-data="{ fileName: '' }" class="flex items-center gap-3">

                @csrf

                <label
                    class="flex items-center gap-2 px-3 h-11 rounded-xl border border-gray-300 bg-white text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition-colors">

                    <!-- ICON -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-3-3m3 3l3-3" />
                    </svg>

                    <!-- TEXT -->
                    <span x-text="fileName || 'Chọn file Excel'"></span>

                    <!-- INPUT -->
                    <input type="file" name="file" class="hidden" accept=".xlsx,.xls"
                        @change="fileName = $event.target.files[0]?.name">
                </label>

                <button type="submit" :disabled="!fileName"
                    class="inline-flex items-center px-4 h-11 rounded-xl bg-gray-900 text-white text-sm font-semibold
           hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    Import
                </button>
            </form>
        </div>


        {{-- BULK BAR --}}
        @if (count($selected) > 0)
            <div class="flex justify-between items-center bg-indigo-50 border border-indigo-100 p-4 rounded-2xl">
                <span class="text-sm font-medium text-indigo-800">
                    Đã chọn {{ count($selected) }} hồ sơ
                </span>

                <div class="flex items-center gap-2">
                    <button wire:click="deleteSelected"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50">
                        Xóa
                    </button>

                    <button wire:click="$set('selected', [])"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm hover:bg-gray-50">
                        Bỏ chọn
                    </button>
                </div>
            </div>
        @endif
    @endcan
    {{-- TABLE --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm align-middle">

                <thead class="bg-gray-50/75 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">
                            <input type="checkbox" wire:model.live="selectAll"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Học sinh</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Lớp</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Loại lớp</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Ngày sinh</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Trạng thái</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-600">Thao tác</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    @foreach ($applications as $item)
                        <tr class="hover:bg-gray-50/50">

                            <td class="px-6 py-4">
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">
                                    {{ $item->ho_va_ten_hoc_sinh }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $item->ma_dinh_danh }}
                                </div>
                            </td>

                            <td class="px-6 py-4 text-gray-600">
                                <span class="inline-flex px-2.5 py-1 rounded-md text-xs bg-gray-100">
                                    {{ $item->lop }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-600">
                                <span class="inline-flex px-2.5 py-1 rounded-md text-xs bg-gray-100">
                                    {{ $item->loai_lop_dang_ky }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-500">
                                {{ $item->ngay_sinh ? \Carbon\Carbon::parse($item->ngay_sinh)->format('d/m/Y') : '' }}
                            </td>

                            <td class="px-6 py-4">
                                @if ($item->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 text-xs bg-amber-100 text-amber-800 rounded-full">
                                            Chờ duyệt
                                        </span>
                                        @can('create_admission')
                                            <button wire:click="approve({{ $item->id }})"
                                                class="text-emerald-600 hover:text-emerald-700 text-xs font-medium">
                                                Duyệt
                                            </button>

                                            <button wire:click="reject({{ $item->id }})"
                                                class="text-rose-600 hover:text-rose-700 text-xs font-medium">
                                                Từ chối
                                            </button>
                                        @endcan
                                    </div>
                                @elseif($item->status === 'approved')
                                    <span class="px-2.5 py-1 text-xs bg-emerald-100 text-emerald-800 rounded-full">
                                        Đã duyệt
                                    </span>
                                    {{-- BIÊN NHẬN --}}
                                    @if ($item->status === 'approved')
                                        <a href="{{ route('admission.receipt', $item->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-orange-600 hover:bg-orange-50 rounded-lg transition">
                                            Biên nhận
                                        </a>
                                    @endif
                                @elseif($item->status === 'rejected')
                                    <span class="px-2.5 py-1 text-xs bg-rose-100 text-rose-800 rounded-full">
                                        Từ chối
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs bg-rose-100 text-rose-800 rounded-full">
                                        Import
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                {{-- Chi tiết / Edit --}}
                                <a href="{{ route('admin.admission.edit', $item->id) }}"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600
              hover:bg-blue-50 rounded-lg transition">
                                    Chi tiết
                                </a>

                                {{-- DOWNLOAD PDF --}}

                                @if ($item->pdf_path && Storage::disk('local')->exists($item->pdf_path) && $item->status === 'approved')
                                    <a href="{{ route('admission.download', ['id' => $item->id, 'type' => 'pdf']) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                                        PDF
                                    </a>
                                @endif
                                @can('delete_admission')
                                {{-- DOWNLOAD WORD --}}
                                @if ($item->word_path && Storage::disk('local')->exists($item->word_path) && $item->status === 'approved')
                                    <a href="{{ route('admission.download', ['id' => $item->id, 'type' => 'word']) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                                        Word
                                    </a>
                                @endif


                                {{-- DELETE --}}

                                    <button wire:click="delete({{ $item->id }})"
                                        class="text-rose-500 hover:text-rose-700 text-sm">
                                        Xóa
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if ($perPage !== 'all' && method_exists($applications, 'hasPages') && $applications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                {{ $applications->links() }}
            </div>
        @endif

    </div>
</div>

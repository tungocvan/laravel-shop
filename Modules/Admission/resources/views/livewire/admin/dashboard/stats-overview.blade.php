<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">

        {{-- ================= TOTAL ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Tổng hồ sơ</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-gray-200 text-xs font-medium text-gray-500">
                    All
                </span>
            </div>
            <div class="mt-4 text-2xl font-bold text-gray-900 tracking-tight">
                {{ number_format($total) }}
            </div> 
            <p class="mt-1 text-xs text-gray-500">Toàn bộ đơn đăng ký</p>
        </div>

        {{-- ================= PENDING ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Chờ duyệt</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-amber-200 text-xs font-medium text-amber-600">
                    Pending
                </span>
            </div>
            <div class="mt-4 text-2xl font-bold text-gray-900 tracking-tight">
                {{ number_format($pending) }}
            </div>
            <p class="mt-1 text-xs text-gray-500">Đang xử lý</p>
        </div>

        {{-- ================= APPROVED ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Đã duyệt</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-emerald-200 text-xs font-medium text-emerald-600">
                    Approved
                </span>
            </div>
            <div class="mt-4 text-2xl font-bold text-gray-900 tracking-tight">
                {{ number_format($approved) }}
            </div>
            <p class="mt-1 text-xs text-gray-500">Hồ sơ hợp lệ</p>
        </div>

        {{-- ================= REJECTED ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Từ chối</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-red-200 text-xs font-medium text-red-600">
                    Rejected
                </span>
            </div>
            <div class="mt-4 text-2xl font-bold text-gray-900 tracking-tight">
                {{ number_format($rejected) }}
            </div>
            <p class="mt-1 text-xs text-gray-500">Không hợp lệ</p>
        </div>

        {{-- ================= IMPORT ================= --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Import</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-indigo-200 text-xs font-medium text-indigo-600">
                    Imported
                </span>
            </div>
            <div class="mt-4 text-2xl font-bold text-gray-900 tracking-tight">
                {{ number_format($import) }}
            </div>
            <p class="mt-1 text-xs text-gray-500">Dữ liệu nhập từ file</p>
        </div>

    </div>

    {{-- ================= CLASS TYPES ================= --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-medium text-gray-600">Loại lớp</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-blue-200 text-xs font-medium text-blue-600">
                Phân loại
            </span>
        </div>

        <div class="space-y-2">
            @forelse($classTypes as $type => $count)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 truncate">
                        {{ $type ?: 'Không xác định' }}
                    </span>
                    <span class="font-semibold text-gray-900">
                        {{ $count }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500">Không có dữ liệu</p>
            @endforelse
        </div>

        <p class="mt-2 text-xs text-gray-500">Theo loại đăng ký</p>
    </div>

</div>
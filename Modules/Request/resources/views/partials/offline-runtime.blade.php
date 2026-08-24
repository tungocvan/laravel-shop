<div
    data-request-offline-root
    data-request-user-id="{{ (int) auth('admin')->id() }}"
    data-request-installation="{{ request()->getHost() }}"
    data-request-offline="loading"
    data-request-connectivity="online"
    class="group mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600"
    role="status"
    aria-live="polite"
>
    <span class="font-medium">An toàn dữ liệu cục bộ của Đề nghị</span>
    <span class="hidden group-data-[request-connectivity=offline]:inline">Đang ngoại tuyến — chỉ đọc dữ liệu tóm tắt đã lưu và chỉnh sửa các bản nháp cục bộ được phép.</span>
    <span class="hidden group-data-[request-connectivity=online]:inline">Đang trực tuyến</span>
    <button
        type="button"
        data-request-clear-local
        data-request-offline-allowed
        class="ml-auto min-h-10 rounded-lg border border-slate-300 px-3 py-2 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    >
        Xóa dữ liệu Đề nghị cục bộ
    </button>
</div>

@pushOnce('scripts')
    @vite('Modules/Request/resources/js/request-offline.js')
@endPushOnce

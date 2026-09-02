@extends('Admin::layouts.master')

@section('title', __('Request::request.reports.title'))

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-3 sm:p-4 lg:p-6">
    @include('Request::partials.workspace-navigation')

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Báo cáo & xuất dữ liệu</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Không gian báo cáo Đề nghị</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi số lượng đề nghị trong đúng phạm vi được phân quyền, thu hẹp dữ liệu bằng bộ lọc rõ ràng và tạo tệp xuất riêng tư có thời hạn.</p>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950">
                <div class="font-semibold">Cập nhật lúc {{ $refreshedAt->format('d/m/Y H:i:s') }}</div>
                <div class="mt-1 text-xs text-sky-800">Múi giờ hiển thị: {{ config('app.timezone') }}</div>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Kết quả đang xem</div><div class="mt-1 text-2xl font-bold text-indigo-950">{{ number_format($filteredCount) }}</div><div class="mt-1 text-xs text-indigo-800">{{ $activeFilterCount }} bộ lọc đang áp dụng</div></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Tổng phạm vi</div><div class="mt-1 text-2xl font-bold text-slate-950">{{ number_format($totalCount) }}</div><div class="mt-1 text-xs text-slate-600">Không bao gồm dữ liệu ngoài quyền truy cập</div></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Đang chờ duyệt</div><div class="mt-1 text-2xl font-bold text-amber-950">{{ number_format($pendingCount) }}</div><div class="mt-1 text-xs text-amber-800">Đếm theo đề nghị, không đếm tác vụ</div></div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Đã kết thúc</div><div class="mt-1 text-2xl font-bold text-emerald-950">{{ number_format($terminalCount) }}</div><div class="mt-1 text-xs text-emerald-800">Đã duyệt, từ chối hoặc đã hủy</div></div>
        </div>

        <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 text-sm leading-6 text-indigo-950">
            <strong>Phạm vi an toàn:</strong> báo cáo và tệp xuất dùng cùng bộ lọc, cùng quyền truy cập hiện tại và không đọc dữ liệu biểu mẫu nhạy cảm. Xuất đồng bộ tối đa {{ number_format($syncRowLimit) }} dòng; phạm vi lớn hơn sẽ xếp hàng, tối đa {{ number_format($maxRows) }} dòng.
        </div>
    </header>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
            <div class="font-semibold">Không thể áp dụng yêu cầu hiện tại.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if(session('request_export_message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900" role="status">{{ session('request_export_message') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="report-filter-title">
        <div class="border-b border-slate-200 p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 id="report-filter-title" class="text-lg font-bold text-slate-900">Bộ lọc báo cáo</h2><p class="mt-1 text-sm text-slate-600">Ngày được hiểu theo múi giờ {{ config('app.timezone') }} và chuyển sang UTC khi truy vấn.</p></div>
                @if($activeFilterCount > 0)<a href="{{ route('request.admin.reports') }}" class="min-h-11 rounded-lg border border-slate-300 px-4 py-2 text-center text-sm font-semibold leading-6 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">Xóa bộ lọc</a>@endif
            </div>
        </div>

        <form method="GET" action="{{ route('request.admin.reports') }}" class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-6">
            <div><label for="report-group" class="mb-1 block text-sm font-semibold text-slate-700">Nhóm đề nghị</label><select id="report-group" name="group_public_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả nhóm</option>@foreach($groups as $group)<option value="{{ $group->public_id }}" @selected(($filters['group_public_id'] ?? '') === $group->public_id)>{{ $group->name }}</option>@endforeach</select></div>
            <div><label for="report-type" class="mb-1 block text-sm font-semibold text-slate-700">Loại đề nghị</label><select id="report-type" name="type_public_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả loại</option>@foreach($types as $type)<option value="{{ $type->public_id }}" @selected(($filters['type_public_id'] ?? '') === $type->public_id)>{{ $type->name }}@if($type->group) · {{ $type->group->name }}@endif</option>@endforeach</select></div>
            <div><label for="report-status" class="mb-1 block text-sm font-semibold text-slate-700">Trạng thái</label><select id="report-status" name="status" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả trạng thái</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ __('Request::request.statuses.'.$status->value) }}</option>@endforeach</select></div>
            <div><label for="report-created-from" class="mb-1 block text-sm font-semibold text-slate-700">Tạo từ ngày</label><input id="report-created-from" type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></div>
            <div><label for="report-created-to" class="mb-1 block text-sm font-semibold text-slate-700">Đến hết ngày</label><input id="report-created-to" type="date" name="created_to" value="{{ $filters['created_to'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></div>
            <div><label for="report-page-size" class="mb-1 block text-sm font-semibold text-slate-700">Số dòng mỗi trang</label><select id="report-page-size" name="per_page" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">@foreach($pageSizes as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach</select></div>
            <div class="flex flex-col gap-2 md:col-span-2 sm:flex-row sm:justify-end xl:col-span-6"><button type="submit" class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Áp dụng bộ lọc</button></div>
        </form>
    </section>

    <section aria-labelledby="status-summary-title">
        <div class="mb-3 flex items-center justify-between gap-3"><h2 id="status-summary-title" class="text-lg font-bold text-slate-900">Phân bố theo trạng thái</h2><span class="text-xs text-slate-500">Đơn vị: đề nghị</span></div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <a href="{{ route('request.admin.reports', $filtersWithoutStatus + ['per_page' => $perPage]) }}" class="rounded-xl border p-4 shadow-sm transition {{ $selectedStatus === '' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white hover:border-indigo-300' }}"><div class="text-sm font-semibold text-slate-700">Tất cả</div><div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($totalCount) }}</div></a>
            @foreach($statuses as $status)<a href="{{ route('request.admin.reports', $filtersWithoutStatus + ['status' => $status->value, 'per_page' => $perPage]) }}" class="rounded-xl border p-4 shadow-sm transition {{ $selectedStatus === $status->value ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white hover:border-indigo-300' }}"><div class="text-sm font-semibold text-slate-700">{{ __('Request::request.statuses.'.$status->value) }}</div><div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($statusCounts[$status->value] ?? 0) }}</div></a>@endforeach
        </div>
    </section>

    @if($canExport)
        <section class="rounded-2xl border border-indigo-200 bg-indigo-50/50 shadow-sm" aria-labelledby="export-review-title">
            <div class="border-b border-indigo-200 p-5">
                <h2 id="export-review-title" class="text-lg font-bold text-slate-900">Xem lại phạm vi trước khi xuất</h2>
                <p class="mt-1 text-sm text-slate-600">Chọn một hoặc nhiều đề nghị ở sổ đăng ký để chỉ xuất các dòng đã chọn. Nếu không chọn dòng nào, hệ thống xuất toàn bộ {{ number_format($filteredCount) }} đề nghị trong bộ lọc hiện tại.</p>
            </div>
            <form id="request-export-form" method="POST" action="{{ route('request.admin.reports.exports.store') }}" class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_16rem] lg:items-end">
                @csrf
                @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div class="space-y-4">
                    @unless($exportAllowed)<div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="alert">Toàn bộ phạm vi hiện tại vượt {{ number_format($maxRows) }} dòng. Bạn vẫn có thể chọn tối đa {{ number_format(config('request.settings.max_page_size', 100)) }} dòng đang hiển thị để xuất riêng; nếu không chọn dòng nào, máy chủ sẽ từ chối phạm vi quá lớn.</div>@endunless
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-white bg-white p-4"><span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Toàn bộ bộ lọc</span><strong class="mt-1 block text-slate-900">{{ number_format($filteredCount) }} dòng</strong></div>
                        <div class="rounded-xl border border-white bg-white p-4"><span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Nếu không chọn dòng</span><strong class="mt-1 block text-slate-900">{{ $filteredCount <= $syncRowLimit ? 'Tạo ngay' : 'Xử lý qua hàng đợi' }}</strong></div>
                        <div class="rounded-xl border border-white bg-white p-4"><span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Thời hạn tệp</span><strong class="mt-1 block text-slate-900">{{ config('request.exports.expiry_hours', 24) }} giờ</strong></div>
                    </div>
                    <label class="flex items-start gap-3 rounded-xl border border-indigo-200 bg-white p-4 text-sm leading-6 text-slate-700"><input type="checkbox" name="confirmed" value="1" required class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Tôi xác nhận xuất các dòng đã chọn; nếu không chọn dòng nào thì xuất toàn bộ phạm vi bộ lọc hiện tại. Quyền tải xuống sẽ được kiểm tra lại.</span></label>
                </div>
                <div class="space-y-3">
                    <div><label for="export-format" class="mb-1 block text-sm font-semibold text-slate-700">Định dạng tệp</label><select id="export-format" name="format" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="csv">CSV</option><option value="xlsx">XLSX</option></select></div>
                    <button type="submit" class="min-h-11 w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Tạo tệp xuất an toàn</button>
                </div>
            </form>
        </section>
    @else
        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700" aria-label="Quyền xuất dữ liệu">Bạn có thể xem báo cáo nhưng chưa được cấp quyền tạo hoặc tải tệp xuất dữ liệu.</section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="request-register-title">
        <div class="flex flex-col gap-2 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 id="request-register-title" class="text-lg font-bold text-slate-900">Sổ đăng ký đề nghị</h2><p class="mt-1 text-sm text-slate-600">Hiển thị đề nghị, không cộng lặp các lần xử lý hoặc tác vụ phê duyệt. Checkbox chỉ chọn các dòng của trang đang hiển thị.</p></div>
            <div class="text-sm font-semibold text-slate-700">{{ number_format($requests->total()) }} kết quả</div>
        </div>

        @if($requests->isEmpty())
            <div class="p-8 text-center"><div class="font-semibold text-slate-900">Không có dữ liệu phù hợp</div><p class="mt-1 text-sm text-slate-600">Hãy thay đổi bộ lọc nhưng vẫn giữ phạm vi phân quyền hiện tại.</p></div>
        @else
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($requests as $item)
                    <article class="space-y-3 p-4">
                        @if($canExport)<label class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"><input form="request-export-form" type="checkbox" name="selected_request_public_ids[]" value="{{ $item->public_id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Chọn để xuất đề nghị này</span></label>@endif
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><div class="font-mono text-xs text-slate-500">{{ $item->request_number }}</div><div class="mt-1 break-words font-bold text-slate-900">{{ $item->title_snapshot }}</div></div><span class="shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$item->status->value) }}</span></div>
                        <div class="grid grid-cols-2 gap-3 text-sm"><div><span class="block text-xs text-slate-500">Loại đề nghị</span><strong class="text-slate-800">{{ $item->type?->name ?? 'Không xác định' }}</strong></div><div><span class="block text-xs text-slate-500">Nhóm</span><strong class="text-slate-800">{{ $item->type?->group?->name ?? '—' }}</strong></div><div><span class="block text-xs text-slate-500">Đã gửi</span><strong class="text-slate-800">{{ $item->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</strong></div><div><span class="block text-xs text-slate-500">Cập nhật</span><strong class="text-slate-800">{{ $item->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</strong></div></div>
                        @if($canDeleteRequests && in_array($item->status->value, ['approved', 'rejected', 'cancelled'], true))<form method="POST" action="{{ route('request.admin.reports.requests.destroy', $item->public_id) }}" onsubmit="return confirm('Xóa vĩnh viễn đề nghị đã kết thúc này? Tệp đính kèm cũng sẽ bị xóa.')">@csrf @method('DELETE')<button class="min-h-11 w-full rounded-lg border border-rose-300 bg-white px-4 py-2 font-semibold text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500">Xóa lịch sử đề nghị</button></form>@endif
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"><tr>@if($canExport)<th class="w-16 px-4 py-3"><span class="sr-only">Chọn xuất</span></th>@endif<th class="px-4 py-3">Mã đề nghị</th><th class="px-4 py-3">Nội dung</th><th class="px-4 py-3">Loại / Nhóm</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Đã gửi</th><th class="px-4 py-3">Cập nhật</th>@if($canDeleteRequests)<th class="px-4 py-3"><span class="sr-only">Hành động</span></th>@endif</tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($requests as $item)
                            <tr>
                                @if($canExport)<td class="px-4 py-3"><input form="request-export-form" type="checkbox" name="selected_request_public_ids[]" value="{{ $item->public_id }}" aria-label="Chọn {{ $item->request_number }} để xuất" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>@endif
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $item->request_number }}</td>
                                <td class="max-w-sm px-4 py-3"><div class="break-words font-semibold text-slate-900">{{ $item->title_snapshot }}</div></td>
                                <td class="px-4 py-3"><div class="font-semibold text-slate-800">{{ $item->type?->name ?? 'Không xác định' }}</div><div class="mt-1 text-xs text-slate-500">{{ $item->type?->group?->name ?? 'Chưa phân nhóm' }}</div></td>
                                <td class="px-4 py-3"><span class="whitespace-nowrap rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$item->status->value) }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $item->submitted_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $item->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                @if($canDeleteRequests)<td class="px-4 py-3 text-right">@if(in_array($item->status->value, ['approved', 'rejected', 'cancelled'], true))<form method="POST" action="{{ route('request.admin.reports.requests.destroy', $item->public_id) }}" onsubmit="return confirm('Xóa vĩnh viễn đề nghị đã kết thúc này? Tệp đính kèm cũng sẽ bị xóa.')">@csrf @method('DELETE')<button class="min-h-10 rounded-lg border border-rose-300 bg-white px-3 py-2 font-semibold text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500">Xóa</button></form>@else<span class="text-xs text-slate-400">Đang hoạt động</span>@endif</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())<div class="border-t border-slate-200 p-4">{{ $requests->links() }}</div>@endif
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-exports-title">
        <div class="border-b border-slate-200 p-5"><h2 id="recent-exports-title" class="text-lg font-bold text-slate-900">Tệp xuất gần đây</h2><p class="mt-1 text-sm text-slate-600">Theo dõi trạng thái xử lý, số dòng và thời hạn tải xuống của 10 yêu cầu gần nhất.</p></div>
        @if($exports->isEmpty())
            <div class="p-8 text-center text-sm text-slate-600">Bạn chưa tạo tệp xuất nào.</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($exports as $export)
                    @php
                        $isExpired = $export->status->value === 'ready' && $export->expires_at?->isPast();
                        $displayStatus = $isExpired ? 'expired' : $export->status->value;
                        $statusClass = match ($displayStatus) {
                            'ready' => 'bg-emerald-50 text-emerald-700',
                            'failed' => 'bg-rose-50 text-rose-700',
                            'expired' => 'bg-slate-100 text-slate-600',
                            'processing' => 'bg-sky-50 text-sky-700',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <article class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs text-slate-500">{{ $export->public_id }}</span><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ __('Request::exports.statuses.'.$displayStatus) }}</span></div><div class="mt-2 font-bold text-slate-900">Tệp {{ strtoupper($export->format) }} · {{ number_format($export->row_count ?? 0) }} dòng</div><div class="mt-1 text-xs text-slate-500">Tạo lúc {{ $export->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}@if($export->expires_at) · Hết hạn {{ $export->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}@endif</div>@if($displayStatus === 'failed')<p class="mt-2 text-sm text-rose-700">Không thể tạo tệp. Tác vụ có thể được xem xét tại Trung tâm phục hồi vận hành.</p>@elseif(in_array($displayStatus, ['pending', 'processing'], true))<p class="mt-2 text-sm text-sky-700">Hệ thống đang xử lý; tải lại trang để cập nhật trạng thái mới nhất.</p>@endif</div>
                        @if($displayStatus === 'ready' && $canExport)<a href="{{ route('request.exports.download', $export->public_id) }}" class="min-h-11 shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-semibold leading-6 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Tải xuống tệp</a>@else<span class="min-h-11 shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-center text-sm font-semibold leading-6 text-slate-500">Chưa thể tải xuống</span>@endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

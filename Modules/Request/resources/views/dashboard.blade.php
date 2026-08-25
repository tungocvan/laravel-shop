@extends('Admin::layouts.master')
@section('title', 'Bảng kiểm thử Đề nghị')
@section('content')
<div class="p-4 sm:p-6 space-y-6">
    @include('Request::partials.offline-runtime')
    @include('Request::partials.workspace-navigation')

    <div>
        <h1 class="text-2xl font-semibold">Bảng kiểm thử giao diện Đề nghị</h1>
        <p class="mt-1 text-sm text-gray-600">Trang tổng hợp để kiểm thử nhanh MR-08 từ UI-01 đến UI-07 mà không cần nhớ route.</p>
    </div>

    @php
        $countLabels = [
            'request_groups' => 'Nhóm đề nghị',
            'request_types' => 'Loại đề nghị',
            'request_instances' => 'Đề nghị',
            'request_tasks' => 'Tác vụ duyệt',
            'request_comments' => 'Bình luận',
            'request_attachments' => 'Tệp đính kèm',
        ];
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach ($counts as $table => $count)
            <div class="rounded-lg border bg-white p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ $countLabels[$table] ?? $table }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $count }}</div>
            </div>
        @endforeach
    </div>

    <section class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Dữ liệu DEMO đang có</h2>
                <p class="mt-1 text-sm text-slate-600">Các bản ghi dưới đây được tạo bởi RequestDemoSeeder và có thể chạy lại an toàn.</p>
            </div>
            <code class="rounded bg-white px-2 py-1 text-xs text-slate-600">REQUEST_UI_DEMO</code>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Loại đề nghị mẫu</div>
                @if ($demoType)
                    <div class="mt-2 font-semibold text-slate-900">{{ $demoType->name }}</div>
                    <div class="mt-1 text-sm text-slate-600">Mã: {{ $demoType->code }}</div>
                    <div class="mt-1 break-all text-xs text-slate-500">Public ID: {{ $demoType->public_id }}</div>
                @else
                    <div class="mt-2 text-sm text-amber-700">Chưa có dữ liệu loại đề nghị DEMO. Hãy chạy RequestDemoSeeder.</div>
                @endif
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đề nghị mẫu</div>
                @if ($draftRequest)
                    <div class="mt-2 font-semibold text-slate-900">{{ $draftRequest->title_snapshot }}</div>
                    <div class="mt-1 text-sm text-slate-600">Số đề nghị: {{ $draftRequest->request_number }} · Trạng thái: {{ __('Request::request.statuses.'.$draftRequest->status) }}</div>
                    <div class="mt-1 break-all text-xs text-slate-500">Public ID: {{ $draftRequest->public_id }}</div>
                @else
                    <div class="mt-2 text-sm text-amber-700">Chưa có đề nghị DEMO. Hãy chạy RequestDemoSeeder.</div>
                @endif
            </div>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.catalog') }}">
            <div class="font-semibold">UI-01 · Danh mục / Tạo đề nghị</div>
            <div class="mt-1 text-sm text-gray-600">Kiểm thử danh mục và luồng tạo đề nghị trên màn hình di động.</div>
        </a>
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.mine') }}">
            <div class="font-semibold">UI-01 / UI-05 · Đề nghị của tôi</div>
            <div class="mt-1 text-sm text-gray-600">Tiếp tục bản nháp và kiểm thử khôi phục bản nháp cục bộ.</div>
        </a>
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.inbox') }}">
            <div class="font-semibold">UI-02 · Hộp thư / Quyết định</div>
            <div class="mt-1 text-sm text-gray-600">Kiểm thử bàn phím, focus và luồng quyết định.</div>
        </a>
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.admin.types') }}">
            <div class="font-semibold">UI-03 · Quản lý loại đề nghị</div>
            <div class="mt-1 text-sm text-gray-600">Mở trình thiết kế và xem lịch sử phiên bản.</div>
        </a>
        @if ($demoType)
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.admin.types.designer', $demoType->public_id) }}">
                <div class="font-semibold">UI-03 · Trình thiết kế DEMO</div>
                <div class="mt-1 text-sm text-gray-600">Đi thẳng tới loại đề nghị mẫu đã seed.</div>
            </a>
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.admin.types.versions', $demoType->public_id) }}">
                <div class="font-semibold">UI-03 · Phiên bản DEMO</div>
                <div class="mt-1 text-sm text-gray-600">Xem so sánh và chi tiết các phiên bản.</div>
            </a>
        @endif
        @if ($draftRequest)
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.show', $draftRequest->public_id) }}">
                <div class="font-semibold">UI-04..06 · Bản nháp DEMO</div>
                <div class="mt-1 text-sm text-gray-600">Kiểm thử offline, bản nháp cục bộ và dữ liệu bảo mật.</div>
            </a>
        @endif
        @if ($pendingRequest)
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.show', $pendingRequest->public_id) }}">
                <div class="font-semibold">UI-02 / UI-04 · Đề nghị chờ duyệt DEMO</div>
                <div class="mt-1 text-sm text-gray-600">Kiểm thử chi tiết, quyết định và khóa thao tác khi offline.</div>
            </a>
        @endif
    </div>

    <div class="rounded-lg border bg-amber-50 p-4 text-sm">
        <strong>UI-07:</strong> tạo dữ liệu Request cục bộ, đăng xuất, sau đó kiểm tra <code>Clear-Site-Data</code> và IndexedDB đã được dọn sạch.
    </div>
</div>
@endsection

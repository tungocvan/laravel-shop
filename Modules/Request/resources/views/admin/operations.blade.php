@extends('Admin::layouts.master')
@section('title', __('Request::operations.title'))
@section('content')
<div class="mx-auto w-full max-w-6xl space-y-6 p-3 sm:p-4 lg:p-6">
    @include('Request::partials.workspace-navigation')

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Vận hành & phục hồi</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Trung tâm phục hồi vận hành</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi các sự cố có trạng thái thất bại và thực hiện lại đúng tác vụ đã được hệ thống cho phép, không thay đổi trực tiếp dữ liệu nghiệp vụ.</p>

        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phạm vi</div><div class="mt-1 font-bold text-slate-900">Sự cố có thể thử lại</div></div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Kiểm soát</div><div class="mt-1 font-bold text-emerald-950">Danh sách cho phép + idempotency</div></div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-sky-700">Truy vết</div><div class="mt-1 font-bold text-sky-950">Hành động phục hồi được ghi nhận audit</div></div>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
            <strong>Nguyên tắc an toàn:</strong> Chỉ các tác vụ trong danh sách cho phép mới có thể chạy lại. Không chạy lệnh tùy ý và không cung cấp giao diện thực thi câu lệnh hệ thống.
        </div>
    </header>

    @if(session('request_success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('request_success') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="retryable-failures-title">
        <div class="border-b border-slate-200 p-5">
            <h2 id="retryable-failures-title" class="text-lg font-bold text-slate-900">Sự cố có thể thử lại</h2>
            <p class="mt-1 text-sm text-slate-600">Danh sách chỉ bao gồm các failure thuộc capability phục hồi đã được backend cho phép.</p>
        </div>

        @if($failures->isEmpty())
            <div class="p-8 text-center text-sm text-slate-600">{{ __('Request::operations.empty') }}</div>
        @else
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($failures as $failure)
                    @php($kindLabel = match ($failure['kind']) { 'stage_activation' => 'Kích hoạt bước xử lý', 'outbox_dispatch' => 'Phân phối outbox', 'export_generation' => 'Tạo tệp xuất', default => $failure['kind'] })
                    <article class="space-y-4 p-4">
                        <div class="flex items-start justify-between gap-3"><div><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Loại sự cố</div><div class="mt-1 font-bold text-slate-900">{{ $kindLabel }}</div></div><code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $failure['error_code'] ?: '—' }}</code></div>
                        <div class="break-all text-sm text-slate-700">{{ $failure['label'] }}</div>
                        <div class="grid grid-cols-2 gap-3 text-sm"><div><span class="block text-xs text-slate-500">Số lần thử</span><strong>{{ $failure['attempt_count'] }}</strong></div><div><span class="block text-xs text-slate-500">Cập nhật gần nhất</span><strong>{{ $failure['updated_at']?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?: '—' }}</strong></div></div>
                        <form method="POST" action="{{ route('request.admin.operations.retry') }}">@csrf<input type="hidden" name="kind" value="{{ $failure['kind'] }}"><input type="hidden" name="public_id" value="{{ $failure['public_id'] }}"><input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}"><button type="submit" class="min-h-11 w-full rounded-lg border border-indigo-300 bg-white px-4 py-2 font-semibold text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">Thử lại tác vụ</button></form>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Loại sự cố</th><th class="px-4 py-3">Đối tượng</th><th class="px-4 py-3">Mã lỗi</th><th class="px-4 py-3">Số lần thử</th><th class="px-4 py-3">Cập nhật gần nhất</th><th class="px-4 py-3"><span class="sr-only">Hành động</span></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($failures as $failure)
                            @php($kindLabel = match ($failure['kind']) { 'stage_activation' => 'Kích hoạt bước xử lý', 'outbox_dispatch' => 'Phân phối outbox', 'export_generation' => 'Tạo tệp xuất', default => $failure['kind'] })
                            <tr><td class="px-4 py-3 font-semibold text-slate-900">{{ $kindLabel }}</td><td class="px-4 py-3 text-slate-700"><span class="break-all">{{ $failure['label'] }}</span></td><td class="px-4 py-3"><code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $failure['error_code'] ?: '—' }}</code></td><td class="px-4 py-3 text-slate-700">{{ $failure['attempt_count'] }}</td><td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $failure['updated_at']?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?: '—' }}</td><td class="px-4 py-3 text-right"><form method="POST" action="{{ route('request.admin.operations.retry') }}">@csrf<input type="hidden" name="kind" value="{{ $failure['kind'] }}"><input type="hidden" name="public_id" value="{{ $failure['public_id'] }}"><input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}"><button type="submit" class="min-h-11 whitespace-nowrap rounded-lg border border-indigo-300 bg-white px-4 py-2 font-semibold text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">Thử lại tác vụ</button></form></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection

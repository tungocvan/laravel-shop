@extends('ClientPortal::layouts.application')

@section('title', 'Đồng bộ hóa đơn')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Đồng bộ có kiểm soát')
@section('app-dashboard-route', route('client.invoices.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $tokenReady = (bool) ($syncReadiness['token_ready'] ?? false);
    $purchase = $syncReadiness['purchase'];
    $sold = $syncReadiness['sold'];
    // The completed task is the source of truth after redirect/refresh.
    // Never keep a stale preview active after a completed POS sync.
    $completedFilters = ($syncStatus['source'] ?? null) === 'pos'
        && isset($syncStatus['start'], $syncStatus['end'], $syncStatus['direction'])
        ? ['start' => $syncStatus['start'], 'end' => $syncStatus['end'], 'direction' => $syncStatus['direction']]
        : [];
    $currentFilters = $posFilters ?? ($posPreview['filters'] ?? ($completedFilters ?: []));
    $selectedDirection = old('direction', $currentFilters['direction'] ?? 'purchase');
    $selectedSource = old('source', ($posFilters || $posPreview || $completedFilters) ? 'pos' : 'regular');
    $selected = $selectedDirection === 'sold' ? $sold : $purchase;
    $selectedStart = old('start', $currentFilters['start'] ?? $selected['suggested_start']);
    $selectedEnd = old('end', $currentFilters['end'] ?? $syncReadiness['today']);
@endphp
<div class="mx-auto max-w-5xl space-y-4 pb-24 sm:space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('client.invoices.dashboard') }}" class="flex min-h-11 min-w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white font-bold text-slate-800 active:scale-[.97]" aria-label="Quay lại tổng quan">←</a>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $syncPresentation['eyebrow'] ?? 'Hóa đơn / Đồng bộ' }}</p>
            <h1 class="text-xl font-black text-slate-950 sm:text-2xl">{{ $syncPresentation['page_title'] ?? 'Đồng bộ hóa đơn' }}</h1>
        </div>
        <span @class(['shrink-0 rounded-full px-3 py-2 text-xs font-bold', 'bg-emerald-50 text-emerald-800' => $tokenReady, 'bg-amber-50 text-amber-800' => ! $tokenReady])>{{ $tokenReady ? 'GDT đã kết nối' : 'Cần kết nối GDT' }}</span>
    </div>

    @if(session('status')) <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">{{ session('status') }}</div> @endif
    @if(session('error')) <div role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900">{{ session('error') }}</div> @endif
    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    @unless($tokenReady)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
            <h2 class="font-black text-slate-950">Kết nối GDT</h2>
            <p class="mt-1 text-sm text-slate-600">Thông tin đăng nhập được giữ trên server. Nhập CAPTCHA để làm mới phiên.</p>
            <form method="POST" action="{{ route('client.invoices.sync.captcha') }}" class="mt-3">@csrf
                <button class="min-h-11 rounded-xl bg-slate-950 px-5 font-bold text-white active:scale-[.98]">{{ $captchaSvg ? 'Tải CAPTCHA mới' : 'Kết nối GDT' }}</button>
            </form>
            @if($captchaSvg)
                <form method="POST" action="{{ route('client.invoices.sync.authenticate') }}" class="mt-3 flex flex-col gap-3 rounded-2xl bg-white p-4 sm:flex-row sm:items-end">@csrf
                    <img src="data:image/svg+xml;base64,{{ base64_encode($captchaSvg) }}" alt="CAPTCHA đăng nhập GDT" class="max-h-20 max-w-52 rounded-xl border border-slate-200 p-2">
                    <label class="min-w-0 flex-1 text-sm font-bold text-slate-700">Mã CAPTCHA
                        <input name="cvalue" autocomplete="off" maxlength="20" required class="mt-1 block min-h-12 w-full rounded-xl border border-slate-300 px-3 text-base">
                    </label>
                    <button class="min-h-12 rounded-xl bg-emerald-700 px-5 font-bold text-white active:scale-[.98]">Xác thực</button>
                </form>
            @endif
        </section>
    @endunless

    <section class="grid grid-cols-2 gap-3">
        @foreach(['purchase' => ['Mua vào', $purchase], 'sold' => ['Bán ra', $sold]] as $key => [$label, $info])
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4">
                <div class="text-sm font-black text-slate-950">{{ $label }} <span class="text-xs font-medium text-slate-400">{{ $syncReadiness['year'] }}</span></div>
                <p class="mt-2 text-xl font-black tabular-nums text-slate-950">{{ number_format($info['invoice_count']) }}</p>
                <p class="text-xs text-slate-500">Hóa đơn trong năm</p>
                <p class="mt-2 text-xs text-slate-600">Mới nhất: {{ $info['latest_invoice_date'] ? \Carbon\Carbon::parse($info['latest_invoice_date'])->format('d/m/Y') : 'Chưa có' }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid items-start gap-4 lg:grid-cols-[1.1fr_.9fr]">
        <section class="min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-5">
                <h2 class="text-lg font-black text-slate-950">Thiết lập đồng bộ</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $syncPresentation['page_description'] ?? 'Chọn nguồn hóa đơn và thời gian. POS cần xem trước và CAPTCHA riêng.' }}</p>
            </div>
            <form id="invoice-sync-form" method="POST" action="{{ route('client.invoices.sync.start') }}" class="space-y-5">@csrf
                <fieldset>
                    <legend class="text-sm font-bold text-slate-800">1. Loại hóa đơn</legend>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach(['purchase' => 'Mua vào', 'sold' => 'Bán ra'] as $value => $label)
                            <label class="flex min-h-12 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                                <input type="radio" name="direction" value="{{ $value }}" class="h-5 w-5" @checked($selectedDirection === $value)>
                                <span class="text-sm font-bold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="text-sm font-bold text-slate-800">2. Nguồn dữ liệu</legend>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label class="flex min-h-16 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                            <input type="radio" name="source" value="regular" class="h-5 w-5" @checked($selectedSource === 'regular')>
                            <span><strong class="block text-sm">Thông thường</strong><small class="text-slate-500">Đồng bộ nền</small></span>
                        </label>
                        <label class="flex min-h-16 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                            <input type="radio" name="source" value="pos" class="h-5 w-5" @checked($selectedSource === 'pos')>
                            <span><strong class="block text-sm">Máy POS</strong><small class="text-slate-500">CAPTCHA trực tiếp</small></span>
                        </label>
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="text-sm font-bold text-slate-800">3. Khoảng thời gian</legend>
                    <div class="mt-2 grid min-w-0 grid-cols-2 gap-2">
                        <x-pwa-date name="start" label="Từ ngày" :value="$selectedStart" required id="sync-start" :error="$errors->first('start')" />
                        <x-pwa-date name="end" label="Đến ngày" :value="$selectedEnd" :max="$syncReadiness['today']" required :error="$errors->first('end')" />
                    </div>
                </fieldset>
                <div id="regular-help" class="rounded-2xl bg-sky-50 p-3 text-xs leading-5 text-sky-900">Nguồn thông thường được xếp hàng trên server. Bạn có thể rời màn hình và quay lại theo dõi.</div>
                <div id="pos-help" class="hidden rounded-2xl bg-amber-50 p-3 text-xs leading-5 text-amber-900">POS chỉ hỗ trợ ngày bắt đầu và kết thúc trong cùng tháng. Xem trước không ghi dữ liệu; đồng bộ cần CAPTCHA mới và xử lý trực tiếp, không qua queue.</div>
                <button id="regular-submit" type="submit" @disabled(! $tokenReady) class="min-h-12 w-full rounded-2xl bg-slate-950 px-4 text-sm font-black text-white disabled:cursor-not-allowed disabled:bg-slate-300 active:scale-[.98]">Bắt đầu đồng bộ thông thường</button>
                <div id="pos-actions" class="hidden space-y-2">
                    <button type="submit" name="purpose" value="preview" formaction="{{ route('client.invoices.sync.pos.captcha') }}" @disabled(! $tokenReady) class="min-h-12 w-full rounded-2xl bg-slate-950 px-4 text-sm font-black text-white disabled:bg-slate-300 active:scale-[.98]">Tra cứu POS · CAPTCHA</button>
                    @if(($posPreview['count'] ?? 0) > 0)
                        <p class="rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800">Đã xem trước {{ number_format($posPreview['count']) }} hóa đơn. Chỉ đồng bộ khi bộ lọc không đổi.</p>
                        <button type="submit" name="purpose" value="sync" formaction="{{ route('client.invoices.sync.pos.captcha') }}" @disabled(! $tokenReady) class="min-h-12 w-full rounded-2xl bg-emerald-700 px-4 text-sm font-black text-white disabled:bg-slate-300 active:scale-[.98]">Đồng bộ POS · CAPTCHA mới</button>
                    @endif
                </div>
            </form>
            @if($posCaptchaImage)
                <form method="POST" action="{{ route('client.invoices.sync.pos.submit') }}" class="mt-4 space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">@csrf
                    <p class="text-sm font-black text-slate-900">Xác minh POS · {{ ($posFilters['purpose'] ?? '') === 'sync' ? 'Đồng bộ' : 'Xem trước' }}</p>
                    <img src="{{ $posCaptchaImage }}" alt="CAPTCHA POS" class="max-h-24 max-w-56 rounded-xl border border-slate-200 bg-white p-2">
                    <label class="block text-sm font-bold">Nhập mã CAPTCHA POS
                        <input name="cvalue" autocomplete="off" maxlength="20" required class="mt-1 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 text-base">
                    </label>
                    <button class="min-h-12 w-full rounded-xl bg-slate-950 px-4 font-bold text-white active:scale-[.98]">Xác nhận {{ ($posFilters['purpose'] ?? '') === 'sync' ? 'đồng bộ POS' : 'tra cứu POS' }}</button>
                </form>
            @endif
        </section>
        <section class="min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-lg font-black text-slate-950">Tiến trình & kết quả</h2>
                @if($syncId)<a href="{{ route('client.invoices.sync', ['sync_id' => $syncId]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold">Làm mới</a>@endif
            </div>
            @if($syncStatus)
                @php($state = $syncStatus['state'] ?? 'queued')
                <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase text-slate-500">Trạng thái</p>
                    <p class="mt-1 font-black text-slate-950">{{ ['queued' => 'Đang chờ', 'processing' => 'Đang đồng bộ', 'completed' => 'Hoàn tất', 'failed' => 'Thất bại'][$state] ?? $state }}</p>
                    @if(($syncStatus['source'] ?? null) === 'pos')
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-700" aria-label="Thông tin tác vụ đồng bộ">
                            <span class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-indigo-800">Nguồn: Máy POS</span>
                            <span class="rounded-lg bg-white px-2.5 py-1.5">{{ ($syncStatus['direction'] ?? '') === 'sold' ? 'Bán ra' : 'Mua vào' }}</span>
                            @if(!empty($syncStatus['start']) && !empty($syncStatus['end']))
                                <span class="rounded-lg bg-white px-2.5 py-1.5">Từ {{ \Carbon\Carbon::parse($syncStatus['start'])->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($syncStatus['end'])->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    @endif
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $syncStatus['message'] ?? 'Đang cập nhật.' }}</p>
                </div>
                @if(!empty($syncStatus['logs']))
                    <div class="mt-3 max-h-64 overflow-auto rounded-2xl bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-200" aria-label="Nhật ký đồng bộ">
                        @foreach(array_slice($syncStatus['logs'], -20) as $log)<div>{{ $log }}</div>@endforeach
                    </div>
                @endif
                @if(in_array($state, ['queued', 'processing'], true))
                    <p class="mt-3 text-sm text-sky-800">Tác vụ đang chạy trên server. Bạn có thể quay lại sau.</p>
                @endif
            @else
                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 p-6 text-center">
                    <p class="font-bold text-slate-800">Chưa có tác vụ đang theo dõi</p>
                    <p class="mt-2 text-sm text-slate-500">Kết quả đồng bộ sẽ hiển thị tại đây.</p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('application-scripts')
<script>
(() => {
    const form = document.getElementById('invoice-sync-form');
    if (!form) return;
    const update = () => {
        const pos = form.querySelector('input[name="source"]:checked')?.value === 'pos';
        document.getElementById('regular-help').classList.toggle('hidden', pos);
        document.getElementById('regular-submit').classList.toggle('hidden', pos);
        document.getElementById('pos-help').classList.toggle('hidden', !pos);
        document.getElementById('pos-actions').classList.toggle('hidden', !pos);
    };
    form.querySelectorAll('input[name="source"]').forEach(el => el.addEventListener('change', update));
    form.querySelectorAll('input[name="direction"]').forEach(el => el.addEventListener('change', () => {
        const starts = @json(['purchase' => $purchase['suggested_start'], 'sold' => $sold['suggested_start']]);
        const start = document.getElementById('sync-start');
        if (start && starts[el.value]) start.value = starts[el.value];
    }));
    update();
})();
@if($syncStatus && in_array($syncStatus['state'] ?? 'queued', ['queued', 'processing'], true))
window.setTimeout(() => window.location.reload(), 8000);
@endif
</script>
@endpush

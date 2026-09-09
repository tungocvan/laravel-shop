@extends('ClientPortal::layouts.application')

@section('title', 'Đồng bộ hóa đơn')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Đồng bộ thông minh có kiểm soát')
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
@php
    $tokenReady = (bool) ($syncReadiness['token_ready'] ?? false);
    $purchase = $syncReadiness['purchase'];
    $sold = $syncReadiness['sold'];
    $selectedDirection = old('direction', 'purchase');
    $selected = $selectedDirection === 'sold' ? $sold : $purchase;
@endphp
<div class="mx-auto max-w-5xl space-y-5">
    <section class="rounded-[2rem] bg-slate-950 p-5 text-white shadow-sm sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">GDT Smart Sync</p>
                <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Đồng bộ hóa đơn</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Hệ thống tự đề xuất mốc từ dữ liệu mới nhất, kiểm tra Local → Google Drive → GDT và chỉ giữ thông tin xác thực ở server.</p>
                <p class="mt-2 max-w-2xl text-xs font-semibold leading-5 text-slate-400">Token, mật khẩu và thông tin xác thực GDT không được đưa xuống trình duyệt.</p>
            </div>
            <span @class(['rounded-full px-3 py-2 text-xs font-black', 'bg-emerald-400/15 text-emerald-300' => $tokenReady, 'bg-amber-400/15 text-amber-200' => ! $tokenReady])>
                {{ $tokenReady ? '● GDT đã kết nối' : '● GDT cần xác thực' }}
            </span>
        </div>
    </section>

    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900">{{ session('error') }}</div>
    @endif

    @unless($tokenReady)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-amber-700">Kết nối GDT</p>
                    <h2 class="mt-1 text-lg font-black text-slate-950">Phiên GDT đã hết hạn hoặc chưa được kết nối</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Tài khoản và mật khẩu GDT vẫn ở server. PWA chỉ dùng CAPTCHA để cấp lại phiên.</p>
                </div>
                <form method="POST" action="{{ route('client.invoices.sync.captcha') }}">
                    @csrf
                    <button class="min-h-11 rounded-2xl bg-slate-950 px-4 text-sm font-black text-white">{{ $captchaSvg ? '↻ CAPTCHA mới' : 'Kết nối GDT' }}</button>
                </form>
            </div>
            @if($captchaSvg)
                <form method="POST" action="{{ route('client.invoices.sync.authenticate') }}" class="mt-5 grid gap-4 rounded-2xl bg-white p-4 sm:grid-cols-[auto_1fr_auto] sm:items-end">
                    @csrf
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">CAPTCHA</p>
                        <img src="data:image/svg+xml;base64,{{ base64_encode($captchaSvg) }}" alt="CAPTCHA GDT" class="min-h-14 max-w-52 rounded-xl border border-slate-200 bg-white p-2">
                    </div>
                    <label class="text-sm font-bold text-slate-700">Nhập mã CAPTCHA
                        <input name="cvalue" value="{{ old('cvalue') }}" autocomplete="off" maxlength="20" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3 text-base uppercase outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200" required>
                        @error('cvalue')
                            <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <button class="min-h-12 rounded-2xl bg-emerald-600 px-5 text-sm font-black text-white">Xác thực & kết nối</button>
                </form>
            @endif
        </section>
    @endunless

    <section class="grid gap-3 sm:grid-cols-2">
        @foreach(['purchase' => ['Mua vào', $purchase], 'sold' => ['Bán ra', $sold]] as $key => [$label, $info])
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-black text-slate-950">{{ $label }}</h2>
                    <span class="text-xs font-bold text-slate-400">{{ $syncReadiness['year'] }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">HĐ mới nhất</p>
                        <p class="mt-1 font-black text-slate-900">{{ $info['latest_invoice_date'] ? \Carbon\Carbon::parse($info['latest_invoice_date'])->format('d/m/Y') : 'Chưa có' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Số HĐ năm</p>
                        <p class="mt-1 font-black text-slate-900">{{ number_format($info['invoice_count']) }}</p>
                    </div>
                </div>
                <p class="mt-4 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">Đề xuất: {{ \Carbon\Carbon::parse($info['suggested_start'])->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($info['suggested_end'])->format('d/m/Y') }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-black text-slate-950">Tạo tác vụ</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Chọn loại hóa đơn; ngày bắt đầu tự đổi theo hóa đơn mới nhất của loại đó. Bạn vẫn có thể sửa thủ công.</p>
            <form method="POST" action="{{ route('client.invoices.sync.start') }}" class="mt-5 space-y-4" id="smart-sync-form">
                @csrf
                <fieldset>
                    <legend class="text-sm font-bold text-slate-700">Loại hóa đơn</legend>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        @foreach(['purchase' => 'Mua vào', 'sold' => 'Bán ra'] as $value => $label)
                            <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4 has-[:checked]:border-slate-950 has-[:checked]:bg-slate-50">
                                <input type="radio" name="direction" value="{{ $value }}" class="h-5 w-5" @checked($selectedDirection === $value)>
                                <span class="font-bold text-slate-900">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-bold text-slate-700">Từ ngày
                        <input id="sync-start" type="date" name="start" value="{{ old('start', $selected['suggested_start']) }}" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3 text-base outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200" required>
                        @error('start')
                            <span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="text-sm font-bold text-slate-700">Đến ngày
                        <input type="date" name="end" value="{{ old('end', $syncReadiness['today']) }}" max="{{ $syncReadiness['today'] }}" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3 text-base outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200" required>
                        @error('end')
                            <span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
                <div class="rounded-2xl bg-sky-50 p-3 text-xs font-semibold leading-5 text-sky-900">Khi chạy, server ưu tiên kiểm tra file Local, sau đó Google Drive; chỉ gọi GDT khi cần. Dữ liệu cùng ngày có thể được kiểm tra lại để không bỏ sót hóa đơn phát sinh sau lần trước.</div>
                <button type="submit" @disabled(! $tokenReady) @class(['min-h-12 w-full rounded-2xl px-5 text-sm font-black text-white shadow-sm transition', 'bg-slate-950 hover:bg-slate-800' => $tokenReady, 'cursor-not-allowed bg-slate-300' => ! $tokenReady])>{{ $tokenReady ? 'Bắt đầu đồng bộ' : 'Kết nối GDT để đồng bộ' }}</button>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Trạng thái gần nhất</h2>
                    <p class="mt-1 text-sm text-slate-500">Local → Drive → GDT được ghi trong nhật ký tác vụ.</p>
                </div>
                @if($syncId)
                    <a href="{{ route('client.invoices.sync', ['sync_id' => $syncId]) }}" class="min-h-10 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Làm mới</a>
                @endif
            </div>
            @if($syncStatus)
                @php($state = $syncStatus['state'] ?? 'queued')
                <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Trạng thái</p>
                            <p class="mt-1 text-base font-black text-slate-950">{{ ['queued' => 'Đang chờ', 'processing' => 'Đang đồng bộ', 'completed' => 'Hoàn tất', 'failed' => 'Thất bại'][$state] ?? $state }}</p>
                        </div>
                        <span @class(['h-3 w-3 rounded-full', 'bg-amber-500' => $state === 'queued', 'bg-sky-500' => $state === 'processing', 'bg-emerald-500' => $state === 'completed', 'bg-rose-500' => $state === 'failed'])></span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $syncStatus['message'] ?? 'Đang cập nhật trạng thái.' }}</p>
                </div>
                @if(! empty($syncStatus['logs']))
                    <div class="mt-4 max-h-72 overflow-y-auto rounded-2xl bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-300" aria-label="Nhật ký đồng bộ">
                        @foreach(array_slice($syncStatus['logs'], -20) as $log)
                            <div>{{ $log }}</div>
                        @endforeach
                    </div>
                @endif
                @if(in_array($state, ['queued', 'processing'], true))
                    <p class="mt-4 rounded-2xl bg-sky-50 p-4 text-sm leading-6 text-sky-900">Tác vụ đang chạy trên server. Bạn có thể rời màn hình này và quay lại sau.</p>
                @endif
            @else
                <div class="mt-5 rounded-2xl border border-dashed border-slate-300 p-7 text-center">
                    <h3 class="font-black text-slate-900">Chưa có tác vụ đang theo dõi</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Sau khi gửi, queue chạy nền và trạng thái được lưu tạm trên server.</p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('application-scripts')
<script>
document.querySelectorAll('#smart-sync-form input[name="direction"]').forEach((radio) => {
    radio.addEventListener('change', (event) => {
        const starts = @json(['purchase' => $purchase['suggested_start'], 'sold' => $sold['suggested_start']]);
        const input = document.getElementById('sync-start');
        if (input && starts[event.target.value]) input.value = starts[event.target.value];
    });
});
@if($syncStatus && in_array($syncStatus['state'] ?? 'queued', ['queued', 'processing'], true))
window.setTimeout(() => window.location.reload(), 8000);
@endif
</script>
@endpush

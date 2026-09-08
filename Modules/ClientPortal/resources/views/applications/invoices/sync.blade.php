@extends('ClientPortal::layouts.application')

@section('title', 'Đồng bộ hóa đơn')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Đồng bộ có kiểm soát')
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <section class="rounded-[2rem] bg-slate-950 p-5 text-white shadow-sm sm:p-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tác vụ nền</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Đồng bộ hóa đơn GDT</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">PWA chỉ gửi khoảng thời gian và loại hóa đơn lên server. Token, mật khẩu và thông tin xác thực GDT không được đưa xuống trình duyệt hoặc lưu trong bộ nhớ offline.</p>
    </section>

    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">{{ session('status') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-black text-slate-950">Tạo tác vụ</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Nên đồng bộ theo khoảng thời gian vừa phải. Tác vụ chạy trong queue nên bạn có thể rời màn hình sau khi gửi.</p>

            <form method="POST" action="{{ route('client.invoices.sync.start') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-bold text-slate-700">Từ ngày
                        <input type="date" name="start" value="{{ old('start', now()->startOfMonth()->toDateString()) }}" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3 text-base outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200" required>
                        @error('start')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm font-bold text-slate-700">Đến ngày
                        <input type="date" name="end" value="{{ old('end', now()->endOfMonth()->toDateString()) }}" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3 text-base outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200" required>
                        @error('end')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                </div>

                <fieldset>
                    <legend class="text-sm font-bold text-slate-700">Loại hóa đơn</legend>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4 has-[:checked]:border-slate-950 has-[:checked]:bg-slate-50">
                            <input type="radio" name="direction" value="purchase" class="h-5 w-5" @checked(old('direction', 'purchase') === 'purchase')>
                            <span><span class="block font-bold text-slate-900">Mua vào</span><span class="mt-0.5 block text-xs text-slate-500">VAT in</span></span>
                        </label>
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-4 has-[:checked]:border-slate-950 has-[:checked]:bg-slate-50">
                            <input type="radio" name="direction" value="sold" class="h-5 w-5" @checked(old('direction') === 'sold')>
                            <span><span class="block font-bold text-slate-900">Bán ra</span><span class="mt-0.5 block text-xs text-slate-500">VAT out</span></span>
                        </label>
                    </div>
                    @error('direction')<span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                </fieldset>

                <button type="submit" class="min-h-12 w-full rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-sm transition hover:bg-slate-800">Bắt đầu đồng bộ</button>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div><h2 class="text-lg font-black text-slate-950">Trạng thái gần nhất</h2><p class="mt-1 text-sm text-slate-500">Theo dõi tiến trình mà không giữ màn hình bị khóa.</p></div>
                @if($syncId)<a href="{{ route('client.invoices.sync', ['sync_id' => $syncId]) }}" class="min-h-10 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Làm mới</a>@endif
            </div>

            @if($syncStatus)
                @php($state = $syncStatus['state'] ?? 'queued')
                <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Trạng thái</p><p class="mt-1 text-base font-black text-slate-950">{{ ['queued' => 'Đang chờ', 'processing' => 'Đang đồng bộ', 'completed' => 'Hoàn tất', 'failed' => 'Thất bại'][$state] ?? $state }}</p></div>
                        <span @class(['h-3 w-3 rounded-full', 'bg-amber-500' => $state === 'queued', 'bg-sky-500' => $state === 'processing', 'bg-emerald-500' => $state === 'completed', 'bg-rose-500' => $state === 'failed'])></span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $syncStatus['message'] ?? 'Đang cập nhật trạng thái.' }}</p>
                </div>

                @if(!empty($syncStatus['logs']))
                    <div class="mt-4 max-h-72 overflow-y-auto rounded-2xl bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-300" aria-label="Nhật ký đồng bộ">
                        @foreach(array_slice($syncStatus['logs'], -20) as $log)<div>{{ $log }}</div>@endforeach
                    </div>
                @endif

                @if(in_array($state, ['queued', 'processing'], true))
                    <p class="mt-4 rounded-2xl bg-sky-50 p-4 text-sm leading-6 text-sky-900">Tác vụ đang chạy trên server. Bạn có thể chuyển sang màn hình khác; quay lại liên kết trạng thái này để kiểm tra sau.</p>
                    @push('application-scripts')
                        <script>window.setTimeout(() => window.location.reload(), 8000);</script>
                    @endpush
                @endif
            @else
                <div class="mt-5 rounded-2xl border border-dashed border-slate-300 p-7 text-center"><h3 class="font-black text-slate-900">Chưa có tác vụ đang theo dõi</h3><p class="mt-2 text-sm leading-6 text-slate-500">Tạo tác vụ mới ở bên trái. Trạng thái được lưu tạm thời trên server, không đưa token GDT vào PWA.</p></div>
            @endif
        </section>
    </div>
</div>
@endsection

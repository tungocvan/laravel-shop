<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>@yield('title', 'Ứng dụng') · INAFO</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @if(request()->routeIs('client.muasamcong.price-list*'))
        @include('ClientPortal::applications.muasamcong.partials.price-list-workspace-polish')
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('client.apps.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg shadow-sm" aria-label="Ứng dụng của tôi">←</a>
            <div class="min-w-0"><div class="truncate font-bold">@yield('app-name', 'INAFO')</div><div class="truncate text-xs text-slate-500">INAFO Client Application</div></div>
        </div>
        <div class="flex items-center gap-2">
            @hasSection('app-dashboard-route')<a href="@yield('app-dashboard-route')" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block">Tổng quan</a>@endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Đăng xuất</button></form>
        </div>
    </div>
</header>
@php
    $applicationContext = app(\Modules\ClientPortal\Support\ApplicationContext::class)->current();
    $portalNavigation = $applicationContext
        ? app(\Modules\ClientPortal\Services\PortalNavigationResolver::class)->forApplication($applicationContext, auth('web')->user())
        : collect();
    $primaryNavigation = $portalNavigation->where('placement', 'primary')->values();
    $moreNavigation = $portalNavigation->where('placement', 'more')->values();
    $syncRequestId = session('sync_request_id');
    $syncStatusUrl = $syncRequestId && \Illuminate\Support\Facades\Route::has('client.muasamcong.drug-pricing.sync-status')
        ? route('client.muasamcong.drug-pricing.sync-status', ['syncRequest' => $syncRequestId])
        : null;
@endphp
<div class="mx-auto flex min-h-[calc(100dvh-65px)] max-w-7xl">
    @include('ClientPortal::partials.adaptive-navigation', [
        'primaryNavigation' => $primaryNavigation,
        'moreNavigation' => $moreNavigation,
    ])
    <main class="min-w-0 flex-1 px-4 py-6 pb-24 sm:px-6 sm:py-8 sm:pb-10 lg:px-8">@yield('content')</main>
</div>
@if($syncStatusUrl)
<div id="queue-status" class="fixed bottom-20 right-4 z-50 w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:bottom-5" data-status-url="{{ $syncStatusUrl }}" role="status" aria-live="polite">
    <div class="flex items-start gap-3">
        <span id="queue-status-icon" class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700">○</span>
        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2"><strong id="queue-status-title" class="text-sm">Đang chờ đồng bộ</strong><button id="queue-status-close" type="button" class="hidden rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-slate-100" aria-label="Đóng">✕</button></div>
            <p id="queue-status-message" class="mt-1 text-xs leading-5 text-slate-500">Yêu cầu đã vào hàng đợi. Bạn có thể tiếp tục sử dụng ứng dụng.</p>
        </div>
    </div>
</div>
@endif
<script>
if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
(() => {
    const box = document.getElementById('queue-status');
    if (!box) return;
    const url = box.dataset.statusUrl;
    const icon = document.getElementById('queue-status-icon');
    const title = document.getElementById('queue-status-title');
    const message = document.getElementById('queue-status-message');
    const close = document.getElementById('queue-status-close');
    let attempts = 0;
    let timer = null;

    const render = (data) => {
        const states = {
            queued: ['○', 'Đang chờ đồng bộ', 'Yêu cầu đang nằm trong hàng đợi.', 'bg-amber-50 text-amber-700'],
            processing: ['◔', 'Đang đồng bộ', `Đang xử lý ${data.selected ?? 0} bản ghi từ Mua sắm công.`, 'bg-sky-50 text-sky-700'],
            completed: ['✓', 'Đồng bộ hoàn thành', `Mới: ${data.inserted ?? 0} · Đã có: ${data.duplicates ?? 0} · Thiếu: ${data.missing ?? 0}`, 'bg-emerald-50 text-emerald-700'],
            failed: ['!', 'Đồng bộ thất bại', data.error || 'Không thể hoàn thành yêu cầu đồng bộ.', 'bg-red-50 text-red-700'],
        };
        const state = states[data.status] || states.queued;
        icon.textContent = state[0];
        icon.className = `mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${state[3]}`;
        title.textContent = state[1];
        message.textContent = state[2];
        if (data.status === 'completed' || data.status === 'failed') {
            if (timer) clearTimeout(timer);
            close.classList.remove('hidden');
        }
    };

    const poll = async () => {
        attempts++;
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin', cache: 'no-store'});
            if (!response.ok) throw new Error('status-request-failed');
            const data = await response.json();
            render(data);
            if (data.status === 'completed' || data.status === 'failed') return;
        } catch (error) {
            if (attempts >= 3) {
                icon.textContent = '…';
                title.textContent = 'Chưa đọc được trạng thái';
                message.textContent = 'Ứng dụng sẽ tiếp tục kiểm tra khi kết nối ổn định.';
            }
        }
        if (attempts < 90) timer = setTimeout(poll, 2000);
    };

    close?.addEventListener('click', () => box.remove());
    poll();
})();
</script>
</body>
</html>
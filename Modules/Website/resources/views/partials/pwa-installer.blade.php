@php
    $pwaBrand = trim((string) ($pwaBrandName ?? 'FlexBiz')) ?: 'FlexBiz';
    $pwaTitle = trim((string) ($pwaInstallTitle ?? '')) ?: 'Cài ứng dụng '.$pwaBrand;
    $pwaSubtitle = trim((string) ($pwaInstallSubtitle ?? '')) ?: 'Truy cập nhanh từ màn hình chính · Không cần App Store';
    $cardBackground = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($pwaCardBackground ?? '')) ? $pwaCardBackground : '#1e293b';
    $cardText = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($pwaCardText ?? '')) ? $pwaCardText : '#ffffff';
    $cardMuted = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($pwaCardMuted ?? '')) ? $pwaCardMuted : '#cbd5e1';
@endphp

<div id="pwa-installer" class="mb-8" data-pwa-installer
    data-brand="{{ $pwaBrand }}"
    data-default-title="{{ $pwaTitle }}"
    data-default-subtitle="{{ $pwaSubtitle }}">
    <button type="button" data-pwa-install-button
        class="group w-full rounded-2xl border border-white/10 p-4 text-left transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-blue-500/50 {{ ($pwaCardShadow ?? true) ? 'shadow-xl shadow-black/25' : '' }}" style="background-color: {{ $cardBackground }}; color: {{ $cardText }};">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400 ring-1 ring-blue-400/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M9 18h6"/><path d="M12 6v7m0 0 3-3m-3 3-3-3"/></svg>
            </span>
            <span class="min-w-0 flex-1">
                <strong data-pwa-install-title class="block text-sm font-bold" style="color: {{ $cardText }};">{{ $pwaTitle }}</strong>
                <span data-pwa-install-subtitle class="mt-1 block text-xs leading-5" style="color: {{ $cardMuted }};">{{ $pwaSubtitle }}</span>
            </span>
            <svg class="h-5 w-5 shrink-0 text-gray-500 transition group-hover:translate-x-0.5 group-hover:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </div>
    </button>

    <button type="button" data-pwa-guide-open class="mt-2 inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold underline underline-offset-4 hover:opacity-80 focus:ring-2 focus:ring-blue-400" style="color: var(--footer-foreground, #ffffff);">{{ $pwaGuideLabel ?? 'Hướng dẫn cài đặt' }} <span aria-hidden="true">↗</span></button>

    <div data-pwa-installed class="hidden rounded-2xl border border-emerald-700/40 bg-emerald-500/10 p-4">
        <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">✓</span><div><strong class="block text-sm text-white">Ứng dụng đã được cài đặt</strong><span class="text-xs text-gray-400">Mở {{ $pwaBrand }} từ màn hình chính của thiết bị.</span></div></div>
    </div>
</div>

<div data-pwa-install-modal class="fixed inset-0 z-[100] hidden items-end justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="pwa-install-heading">
    <div data-pwa-install-panel class="w-full max-w-md rounded-t-[2rem] bg-white p-5 text-slate-900 shadow-2xl sm:rounded-[2rem] sm:p-6">
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-slate-200 sm:hidden"></div>
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Progressive Web App</p><h2 id="pwa-install-heading" data-pwa-modal-title class="mt-1 text-2xl font-black tracking-tight">{{ $pwaGuideTitle ?? ('Cài '.$pwaBrand) }}</h2></div>
            <button type="button" data-pwa-install-close class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500" aria-label="Đóng">✕</button>
        </div>

        <div data-pwa-ios-guide class="mt-5 hidden">
            <p class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ $pwaGuideIos ?? 'Safari: Chia sẻ → Thêm vào Màn hình chính → Thêm.' }}</p>
            <div class="mt-5 space-y-3">
                <div class="flex gap-3 rounded-2xl bg-slate-50 p-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">1</span><div><strong class="text-sm">Nhấn Chia sẻ</strong><p class="mt-1 text-xs leading-5 text-slate-500">Trong Safari, nhấn biểu tượng <span class="font-bold text-blue-600">□↑</span> trên thanh công cụ.</p></div></div>
                <div class="flex gap-3 rounded-2xl bg-slate-50 p-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">2</span><div><strong class="text-sm">Chọn “Thêm vào Màn hình chính”</strong><p class="mt-1 text-xs leading-5 text-slate-500">Cuộn danh sách thao tác nếu mục này chưa xuất hiện.</p></div></div>
                <div class="flex gap-3 rounded-2xl bg-slate-50 p-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">3</span><div><strong class="text-sm">Nhấn “Thêm”</strong><p class="mt-1 text-xs leading-5 text-slate-500">{{ $pwaBrand }} sẽ xuất hiện như một ứng dụng trên màn hình chính.</p></div></div>
            </div>
        </div>

        <div data-pwa-ios-browser-guide class="mt-5 hidden">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4"><strong class="text-sm text-amber-900">Hãy mở trang này bằng Safari</strong><p class="mt-1 text-xs leading-5 text-amber-800">iPhone/iPad chỉ cho thêm PWA vào Màn hình chính từ Safari. Mở menu của trình duyệt hiện tại → chọn <strong>Mở trong Safari</strong>, sau đó bấm lại “Cài ứng dụng”.</p></div>
        </div>

        <div data-pwa-android-guide class="mt-5 hidden"><p class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ $pwaGuideAndroid ?? 'Chrome: Menu → Cài đặt ứng dụng hoặc Thêm vào màn hình chính.' }}</p></div>

        <div data-pwa-generic-guide class="mt-5 hidden">
            <p class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ $pwaGuideOther ?? 'Mở menu trình duyệt và chọn Cài đặt ứng dụng hoặc Thêm vào màn hình chính.' }}</p>
        </div>

        <button type="button" data-pwa-install-close class="mt-6 h-12 w-full rounded-2xl bg-slate-950 text-sm font-bold text-white">Đã hiểu</button>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('[data-pwa-installer]');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    const button = root.querySelector('[data-pwa-install-button]');
    const installed = root.querySelector('[data-pwa-installed]');
    const title = root.querySelector('[data-pwa-install-title]');
    const subtitle = root.querySelector('[data-pwa-install-subtitle]');
    const modal = document.querySelector('[data-pwa-install-modal]');
    const iosGuide = modal?.querySelector('[data-pwa-ios-guide]');
    const iosBrowserGuide = modal?.querySelector('[data-pwa-ios-browser-guide]');
    const genericGuide = modal?.querySelector('[data-pwa-generic-guide]');
    const androidGuide = modal?.querySelector('[data-pwa-android-guide]');
    const guideButton = root.querySelector('[data-pwa-guide-open]');
    const brand = root.dataset.brand || 'FlexBiz';
    const defaultTitle = root.dataset.defaultTitle || `Cài ứng dụng ${brand}`;
    const defaultSubtitle = root.dataset.defaultSubtitle || 'Truy cập nhanh từ màn hình chính · Không cần App Store';
    let deferredPrompt = null;

    const ua = navigator.userAgent || '';
    const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isSafari = isIOS && /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(ua);
    const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const showInstalled = () => {
        button?.classList.add('hidden');
        installed?.classList.remove('hidden');
    };

    const showModal = (mode) => {
        if (!modal) return;
        iosGuide?.classList.toggle('hidden', mode !== 'ios');
        iosBrowserGuide?.classList.toggle('hidden', mode !== 'ios-browser');
        genericGuide?.classList.toggle('hidden', mode !== 'generic');
        androidGuide?.classList.toggle('hidden', mode !== 'android');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.documentElement.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
        document.documentElement.classList.remove('overflow-hidden');
    };

    guideButton?.addEventListener('click', () => {
        if (isIOS) return showModal(isSafari ? 'ios' : 'ios-browser');
        showModal(/Android/i.test(ua) ? 'android' : 'generic');
    });

    if (isStandalone()) {
        showInstalled();
        return;
    }

    if (isIOS) {
        title.textContent = `Cài ${brand} trên iPhone/iPad`;
        subtitle.textContent = isSafari ? '3 bước nhanh trong Safari' : 'Mở bằng Safari để cài PWA';
    } else {
        title.textContent = defaultTitle;
        subtitle.textContent = defaultSubtitle;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        subtitle.textContent = 'Sẵn sàng cài đặt trên thiết bị này';
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        closeModal();
        showInstalled();
    });

    button?.addEventListener('click', async () => {
        if (isStandalone()) return showInstalled();
        if (deferredPrompt) {
            deferredPrompt.prompt();
            await deferredPrompt.userChoice.catch(() => null);
            deferredPrompt = null;
            return;
        }
        if (isIOS && isSafari) return showModal('ios');
        if (isIOS) return showModal('ios-browser');
        showModal('generic');
    });

    modal?.querySelectorAll('[data-pwa-install-close]').forEach((el) => el.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
})();
</script>

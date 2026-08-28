<button
    id="install-app"
    type="button"
    hidden
    data-pwa-install-button
    class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2"
>
    {{ $launcher['install_button_text'] }}
</button>

<div
    data-pwa-install-modal
    class="fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/70 backdrop-blur-sm sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="client-pwa-install-heading"
>
    <div class="w-full max-w-md rounded-t-3xl bg-white p-5 text-slate-900 shadow-2xl sm:rounded-3xl sm:p-6">
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-slate-200 sm:hidden" aria-hidden="true"></div>

        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Progressive Web App</p>
                <h2 id="client-pwa-install-heading" class="mt-1 text-2xl font-bold tracking-tight">
                    {{ $launcher['install_ios_heading'] }}
                </h2>
            </div>
            <button type="button" data-pwa-install-close class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500" aria-label="{{ $launcher['install_close_text'] }}">✕</button>
        </div>

        <div data-pwa-ios-guide class="mt-5 hidden">
            <p class="text-sm leading-6 text-slate-600">{{ $launcher['install_ios_description'] }}</p>
            <ol class="mt-5 space-y-3">
                <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">1</span>
                    <div><strong class="text-sm">Nhấn Chia sẻ</strong><p class="mt-1 text-xs leading-5 text-slate-500">Trong Safari, nhấn biểu tượng Chia sẻ trên thanh công cụ.</p></div>
                </li>
                <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">2</span>
                    <div><strong class="text-sm">Chọn “Thêm vào Màn hình chính”</strong><p class="mt-1 text-xs leading-5 text-slate-500">Cuộn danh sách thao tác nếu mục này chưa xuất hiện.</p></div>
                </li>
                <li class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">3</span>
                    <div><strong class="text-sm">Nhấn “Thêm”</strong><p class="mt-1 text-xs leading-5 text-slate-500">Ứng dụng sẽ xuất hiện trên Màn hình chính của thiết bị.</p></div>
                </li>
            </ol>
        </div>

        <div data-pwa-ios-browser-guide class="mt-5 hidden">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <strong class="text-sm text-amber-900">{{ $launcher['install_ios_browser_heading'] }}</strong>
                <p class="mt-1 text-xs leading-5 text-amber-800">{{ $launcher['install_ios_browser_description'] }}</p>
            </div>
        </div>

        <button type="button" data-pwa-install-close class="mt-6 h-12 w-full rounded-2xl bg-slate-900 text-sm font-bold text-white">
            {{ $launcher['install_close_text'] }}
        </button>
    </div>
</div>

<script>
(() => {
    const button = document.querySelector('[data-pwa-install-button]');
    const modal = document.querySelector('[data-pwa-install-modal]');
    if (!button || !modal || button.dataset.ready === '1') return;
    button.dataset.ready = '1';

    const iosGuide = modal.querySelector('[data-pwa-ios-guide]');
    const iosBrowserGuide = modal.querySelector('[data-pwa-ios-browser-guide]');
    const displayMode = window.matchMedia('(display-mode: standalone)');
    const ua = navigator.userAgent || '';
    const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isSafari = isIOS && /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|OPiOS/.test(ua);
    const isStandalone = () => displayMode.matches || window.navigator.standalone === true;
    let deferredPrompt = null;

    const syncVisibility = () => {
        if (isStandalone()) {
            button.hidden = true;
            return;
        }
        button.hidden = !(isIOS || deferredPrompt);
    };

    const showModal = (mode) => {
        iosGuide.classList.toggle('hidden', mode !== 'ios');
        iosBrowserGuide.classList.toggle('hidden', mode !== 'ios-browser');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.documentElement.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.documentElement.classList.remove('overflow-hidden');
    };

    syncVisibility();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        syncVisibility();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        closeModal();
        syncVisibility();
    });

    button.addEventListener('click', async () => {
        if (isStandalone()) return syncVisibility();

        if (deferredPrompt) {
            const prompt = deferredPrompt;
            deferredPrompt = null;
            await prompt.prompt();
            await prompt.userChoice.catch(() => null);
            syncVisibility();
            return;
        }

        if (isIOS && isSafari) return showModal('ios');
        if (isIOS) return showModal('ios-browser');
        syncVisibility();
    });

    modal.querySelectorAll('[data-pwa-install-close]').forEach((element) => element.addEventListener('click', closeModal));
    modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
    displayMode.addEventListener?.('change', syncVisibility);
})();
</script>

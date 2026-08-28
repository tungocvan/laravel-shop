@stack('scripts')
<script>
(() => {
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (!standalone) return;

    document.querySelectorAll('[data-pwa-auth-target]').forEach((link) => {
        const target = link.getAttribute('data-pwa-auth-target');
        if (target) link.setAttribute('href', target);
    });
})();
</script>
@if(data_get($websiteAppearance ?? [], 'service_worker_enabled', true))
<script>
(() => {
    if (!('serviceWorker' in navigator)) return;

    const versionUrl = '/website-pwa-version.json';
    const storageKey = 'website-pwa-version';
    let checking = false;

    const showUpdateNotice = (registration) => {
        if (document.getElementById('website-pwa-update-notice')) return;

        const notice = document.createElement('div');
        notice.id = 'website-pwa-update-notice';
        notice.setAttribute('role', 'status');
        notice.className = 'fixed bottom-4 left-1/2 z-[10000] flex -translate-x-1/2 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-lg';
        notice.innerHTML = '<span>Website vừa được cập nhật.</span><button type="button" class="rounded-lg bg-slate-900 px-3 py-2 font-semibold text-white">Cập nhật ngay</button>';
        notice.querySelector('button').addEventListener('click', async () => {
            registration.waiting?.postMessage({ type: 'SKIP_WAITING' });
            registration.active?.postMessage({ type: 'REFRESH_PWA_ASSETS' });
            await registration.update().catch(() => {});
            window.location.reload();
        });
        document.body.appendChild(notice);
    };

    const checkVersion = async (registration) => {
        if (checking) return;
        checking = true;
        try {
            const response = await fetch(versionUrl, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            const current = localStorage.getItem(storageKey);
            if (!current) {
                localStorage.setItem(storageKey, payload.version);
                return;
            }
            if (current !== payload.version) {
                localStorage.setItem(storageKey, payload.version);
                registration.active?.postMessage({ type: 'REFRESH_PWA_ASSETS' });
                await registration.update().catch(() => {});
                showUpdateNotice(registration);
            }
        } catch (_) {
            // Offline or transient network errors must not affect storefront runtime.
        } finally {
            checking = false;
        }
    };

    window.addEventListener('load', async () => {
        const registration = await navigator.serviceWorker.register('/service-worker.js').catch(() => null);
        if (!registration) return;

        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;
            worker?.addEventListener('statechange', () => {
                if (worker.state === 'installed' && navigator.serviceWorker.controller) showUpdateNotice(registration);
            });
        });

        navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload());
        await checkVersion(registration);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') checkVersion(registration);
        });
        window.addEventListener('focus', () => checkVersion(registration));
    });
})();
</script>
@endif

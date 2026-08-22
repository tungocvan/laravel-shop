<div>
    <h3 class="font-bold text-lg mb-6" style="color: var(--footer-heading);">{{ $footerSettings['app_title'] ?? 'Tải Ứng Dụng' }}</h3>
    <p class="text-xs mb-4" style="color: var(--footer-muted);">{{ $footerSettings['app_description'] ?? 'Cài ứng dụng để truy cập nhanh trên thiết bị của bạn.' }}</p>

    @include('Website::partials.pwa-installer', [
        'pwaInstallTitle' => $footerSettings['app_button_title'] ?? null,
        'pwaInstallSubtitle' => $footerSettings['app_button_subtitle'] ?? null,
        'pwaBrandName' => $footerSettings['brand_name'] ?? null,
    ])

    @if(!empty($footerSettings['appstore']) || !empty($footerSettings['playstore']))
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
            @if(!empty($footerSettings['appstore']))
                <a href="{{ $footerSettings['appstore'] }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border px-3 py-2 transition hover:-translate-y-0.5" style="border-color: var(--footer-border); color: var(--footer-foreground);">App Store</a>
            @endif
            @if(!empty($footerSettings['playstore']))
                <a href="{{ $footerSettings['playstore'] }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border px-3 py-2 transition hover:-translate-y-0.5" style="border-color: var(--footer-border); color: var(--footer-foreground);">Google Play</a>
            @endif
        </div>
    @endif
</div>

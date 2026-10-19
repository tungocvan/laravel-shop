<div>
    <h3 class="font-bold text-base mb-3" style="color: var(--footer-heading);">{{ $footerSettings['app_title'] ?? 'Tải Ứng Dụng' }}</h3>
    <p class="text-xs leading-5 mb-3" style="color: var(--footer-muted);">{{ $footerSettings['app_description'] ?? 'Cài ứng dụng để truy cập nhanh trên thiết bị của bạn.' }}</p>

    @include('Website::partials.pwa-installer', [
        'pwaInstallTitle' => $footerSettings['app_button_title'] ?? null,
        'pwaInstallSubtitle' => $footerSettings['app_button_subtitle'] ?? null,
        'pwaBrandName' => $footerSettings['brand_name'] ?? null,
        'pwaCardBackground' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_card_background', '#1e293b'),
        'pwaCardText' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_card_text', '#ffffff'),
        'pwaCardMuted' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_card_muted', '#cbd5e1'),
        'pwaCardShadow' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_card_shadow', true),
        'pwaGuideLabel' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_guide_label', 'Hướng dẫn cài đặt'),
        'pwaGuideTitle' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_guide_title', 'Hướng dẫn cài đặt ứng dụng'),
        'pwaGuideIos' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_guide_ios', 'Mở Safari, nhấn Chia sẻ, chọn Thêm vào Màn hình chính rồi nhấn Thêm.'),
        'pwaGuideAndroid' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_guide_android', 'Mở Chrome, nhấn menu ba chấm và chọn Cài đặt ứng dụng hoặc Thêm vào màn hình chính.'),
        'pwaGuideOther' => app(\Modules\System\Services\SettingsService::class)->get('footer.app_guide_other', 'Mở menu trình duyệt và tìm mục Cài đặt ứng dụng hoặc Thêm vào màn hình chính.'),
    ])

    @if(!empty($footerSettings['appstore']) || !empty($footerSettings['playstore']))
        <div class="mt-4 flex flex-wrap items-stretch gap-2.5" aria-label="Liên kết cửa hàng ứng dụng">
            @if(!empty($footerSettings['appstore']))
                <a href="{{ $footerSettings['appstore'] }}" target="_blank" rel="noopener noreferrer" aria-label="Mở App Store"
                   class="inline-flex min-h-12 min-w-[142px] items-center gap-2.5 rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.985] motion-reduce:transform-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    <svg aria-hidden="true" class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 12.54c.02 2.16 1.9 2.88 1.92 2.89-.02.05-.3 1.04-.99 2.06-.6.89-1.23 1.78-2.22 1.8-.97.02-1.28-.58-2.39-.58-1.11 0-1.45.56-2.37.6-.97.04-1.7-.97-2.3-1.85-1.25-1.82-2.2-5.15-.92-7.38a3.57 3.57 0 0 1 3.02-1.84c.95-.02 1.85.65 2.43.65.57 0 1.65-.81 2.78-.69.48.02 1.84.2 2.72 1.47-.07.05-1.62.95-1.6 2.87ZM15.33 6.97c.5-.61.84-1.46.75-2.31-.73.03-1.62.49-2.14 1.1-.47.54-.88 1.4-.77 2.23.82.06 1.66-.42 2.16-1.02Z"/></svg>
                    <span class="flex flex-col leading-tight"><span class="text-[10px]">Download on the</span><span class="text-sm font-semibold">App Store</span></span>
                </a>
            @endif
            @if(!empty($footerSettings['playstore']))
                <a href="{{ $footerSettings['playstore'] }}" target="_blank" rel="noopener noreferrer" aria-label="Mở Google Play"
                   class="inline-flex min-h-12 min-w-[142px] items-center gap-2.5 rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-white shadow-sm transition hover:bg-slate-800 active:scale-[0.985] motion-reduce:transform-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                    <svg aria-hidden="true" class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M3.5 2.7a1.5 1.5 0 0 0-.5 1.14v16.32c0 .44.18.83.48 1.1l9.34-9.33L3.5 2.7Zm10.3 10.2 2.75 2.76-10.44 5.9 7.69-8.66Zm3.93 2.09 3.4-1.92c1.15-.65 1.15-1.58 0-2.23l-3.4-1.92-3.14 3.03 3.14 3.04ZM6.13 2.43l10.42 5.9-2.75 2.75-7.67-8.65Z"/></svg>
                    <span class="flex flex-col leading-tight"><span class="text-[10px] uppercase tracking-wide">Get it on</span><span class="text-sm font-semibold">Google Play</span></span>
                </a>
            @endif
        </div>
    @endif
</div>

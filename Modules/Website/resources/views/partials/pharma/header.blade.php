@php
    $settings = app(\Modules\System\Services\SettingsService::class);
    $companyName = (string) ($settings->get('header.brand_name') ?: $settings->get('site_name', 'Dược phẩm'));
    $brandLogo = $settings->get('header.brand_logo') ?: $settings->get('site_logo');
    $brandLogoUrl = is_string($brandLogo) && trim($brandLogo) !== ''
        ? (preg_match('~^https?://~i', $brandLogo) ? $brandLogo : asset('storage/'.ltrim($brandLogo, '/')))
        : null;
    $savedLayout = $settings->get('website.pharma_header.layout');
    $rawLayout = is_array($savedLayout) ? $savedLayout : (array) config('website.header.pharma_layout', []);
    $headerLayout = app(\Modules\Website\Services\HeaderLayoutService::class)->resolvedLayout($rawLayout);
    $savedPresentation = $settings->get('website.pharma_header.presentation');
    $presentation = app(\Modules\Website\Services\HeaderPresentationService::class)->resolve(is_array($savedPresentation) ? $savedPresentation : null);
    $colors = $presentation['colors'];
    $accent = ($presentation['inherit_colors'] ?? true) ? '#047857' : $colors['accent'];
    $background = ($presentation['inherit_colors'] ?? true) ? '#ffffff' : $colors['background'];
    $foreground = ($presentation['inherit_colors'] ?? true) ? '#0f172a' : $colors['foreground'];
    $topbarBackground = $colors['topbar_background'];
    $topbarForeground = $colors['topbar_foreground'];
    $shadow = match($presentation['shadow'] ?? 'soft') { 'none' => 'none', 'medium' => '0 8px 24px rgba(15,23,42,.15)', default => '0 1px 3px rgba(15,23,42,.08)' };
    $headerWidth = $presentation['container_width'] ?? null;
    $searchWidth = (int) ($presentation['custom']['search_max_width'] ?? 560);
    $brandHeight = (int) ($presentation['custom']['logo_max_height'] ?? 48);
    $accountConfig = $settings->get('website.pharma_header.account', []);
    $topbarConfig = $settings->get('website.pharma_header.topbar', []);
    $topbarConfig = is_array($topbarConfig) ? $topbarConfig : [];
    $sharedHotline = (string) $settings->get('header.topbar.hotline', '');
    $sharedEmail = (string) $settings->get('header.topbar.email', '');
    $helpUrl = (string) $settings->get('header.topbar.help_url', '');
    $orderTrackingUrl = (string) $settings->get('header.topbar.order_tracking_url', '');
    $safeTopbarLink = static function (string $url): ?string {
        $url = trim($url);
        if ($url === '' || preg_match('~^(?:javascript|data|vbscript):~i', $url)) return null;
        if (preg_match('~^https?://~i', $url)) return $url;
        if (str_starts_with($url, '//') || str_contains($url, ':')) return null;
        return url('/'.ltrim($url, '/'));
    };
    $topbarHeight = (int) ($presentation['custom']['topbar_height'] ?? 32);
    $menuService = app(\Modules\Website\Services\HeaderMenuService::class);
    $desktopMenu = $menuService->getMenuTreeByLocation('pharma_primary');
    $mobileMenu = $menuService->getMenuTreeByLocation('pharma_mobile');
    if ($mobileMenu->isEmpty()) $mobileMenu = $desktopMenu;
    $visibilityClass = static fn (string $visibility): string => match ($visibility) {
        'both' => 'flex',
        'desktop' => 'hidden lg:flex',
        'mobile' => 'flex lg:hidden',
        default => 'hidden',
    };
    $topbarVisibleDesktop = (bool) ($topbarConfig['show_desktop'] ?? true);
    $topbarVisibleMobile = (bool) ($topbarConfig['show_mobile'] ?? false);
    $topbarVisibility = $topbarVisibleDesktop && $topbarVisibleMobile ? 'both' : ($topbarVisibleDesktop ? 'desktop' : ($topbarVisibleMobile ? 'mobile' : 'hidden'));
    $fallbackMenu = [['title' => 'Trang chủ', 'url' => route('home')], ['title' => 'Danh mục dược phẩm', 'url' => route('product.list')]];
@endphp
<div style="display: contents; --pharma-header-accent: {{ $accent }}; --pharma-header-bg: {{ $background }}; --pharma-header-text: {{ $foreground }};">
    @if(($topbarConfig['enabled'] ?? true))
        @foreach($headerLayout['desktop.topbar'] ?? [] as $component)
            @if($component['type'] === 'topbar')
                <div class="{{ $topbarVisibility === 'both' ? 'block' : ($topbarVisibility === 'mobile' ? 'block lg:hidden' : ($topbarVisibility === 'desktop' ? 'hidden lg:block' : 'hidden')) }} border-b border-slate-800/20" style="background: {{ $topbarBackground }}; color: {{ $topbarForeground }};">
                    <div class="mx-auto flex flex-wrap items-center justify-between gap-x-6 gap-y-2 px-5 py-2 text-xs sm:px-8" style="max-width: {{ $headerWidth ? $headerWidth.'px' : '1280px' }}; min-height: {{ $topbarHeight }}px">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5">
                            @if(trim((string) ($topbarConfig['left_text'] ?? 'THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO')) !== '')
                                <span>{{ $topbarConfig['left_text'] ?? 'THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO' }}</span>
                            @endif
                            @if(($topbarConfig['show_hotline'] ?? false) && filled($sharedHotline))
                                <a href="tel:{{ preg_replace('/[^+0-9]/', '', $sharedHotline) }}" class="inline-flex items-center border-l border-current/20 pl-4 font-semibold hover:underline">{{ $sharedHotline }}</a>
                            @endif
                            @if(($topbarConfig['show_email'] ?? false) && filter_var($sharedEmail, FILTER_VALIDATE_EMAIL))
                                <a href="mailto:{{ $sharedEmail }}" class="inline-flex items-center border-l border-current/20 pl-4 hover:underline">{{ $sharedEmail }}</a>
                            @endif
                        </div>
                        <nav aria-label="Liên kết Topbar" class="flex flex-wrap items-center gap-4">
                            @if($safeTopbarLink($orderTrackingUrl))
                                <a href="{{ $safeTopbarLink($orderTrackingUrl) }}" class="font-semibold hover:underline">Theo dõi đơn hàng</a>
                            @endif
                            @if($safeTopbarLink($helpUrl))
                                <a href="{{ $safeTopbarLink($helpUrl) }}" class="font-semibold hover:underline">Trợ giúp</a>
                            @endif
                        </nav>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
    <header class="{{ ($presentation['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-40 border-b border-slate-200" style="background:var(--pharma-header-bg);color:var(--pharma-header-text);box-shadow:{{ $shadow }}" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
        <div class="mx-auto flex w-full min-w-0 items-center gap-2 px-3 py-3 sm:gap-3 sm:px-6 lg:gap-6 lg:px-8" style="max-width:{{ $headerWidth ? $headerWidth.'px' : '1280px' }};min-height:{{ (int)($presentation['heights']['desktop'] ?? 80) }}px">
            @foreach(['desktop.main.left','desktop.main.center','desktop.main.right'] as $slot)
                <div class="{{ $slot === 'desktop.main.left' ? 'flex min-w-0 flex-1 items-center lg:flex-none' : ($slot === 'desktop.main.center' ? 'flex min-w-0 flex-1 items-center justify-center gap-5' : 'ml-auto flex min-w-0 items-center justify-end gap-4') }}">
                @foreach($headerLayout[$slot] ?? [] as $component)
                    @if($component['type'] === 'brand')
                        <a href="{{ route('home') }}" class="{{ $visibilityClass($component['visibility'] ?? 'desktop') }} min-w-0 max-w-full items-center gap-2 sm:gap-3" aria-label="Về trang chủ">
                            @if($brandLogoUrl)
                                <img src="{{ $brandLogoUrl }}" alt="{{ $companyName }}" class="max-w-40 shrink-0 object-contain" style="max-height:{{ $brandHeight }}px">
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-2xl font-bold text-white shadow-sm" style="background:var(--pharma-header-accent);max-height:{{ $brandHeight }}px" aria-hidden="true">{{ mb_substr($companyName, 0, 1) }}</span>
                            @endif
                            <span class="min-w-0"><span class="block truncate text-base font-extrabold sm:text-lg">{{ $companyName }}</span><span class="hidden text-xs font-medium sm:block" style="color:var(--pharma-header-accent)">Danh mục và thông tin dược phẩm</span></span>
                        </a>
                    @elseif($component['type'] === 'actions')
                        <nav aria-label="Điều hướng Pharma" class="{{ $visibilityClass($component['visibility'] ?? 'desktop') }} flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-semibold">
                            @foreach($desktopMenu->isNotEmpty() ? $desktopMenu : $fallbackMenu as $item)
                                @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                                @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                                <a href="{{ $itemUrl ?: '#' }}" class="hover:underline">{{ $itemTitle }}</a>
                            @endforeach
                        </nav>
                    @elseif($component['type'] === 'search')
                        <form action="{{ route('product.list') }}" method="GET" role="search" class="{{ $visibilityClass($component['visibility'] ?? 'desktop') }} min-w-0 items-center gap-2" style="max-width:{{ $searchWidth }}px">
                            <label class="sr-only" for="pharma-header-search-{{ str_replace('.', '-', $slot) }}">Tìm sản phẩm dược phẩm</label>
                            <input id="pharma-header-search-{{ str_replace('.', '-', $slot) }}" name="search" type="search" value="{{ request('search') }}" placeholder="Tên thuốc, hoạt chất..." class="w-32 min-w-0 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:outline-none xl:w-48 2xl:w-56">
                            <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold text-white" style="background:var(--pharma-header-accent)">Tra cứu →</button>
                        </form>
                    @endif
                @endforeach
                </div>
            @endforeach
            <div class="ml-auto hidden min-w-0 shrink-0 items-center justify-end lg:ml-0 lg:flex">
                @include('Website::partials.pharma.account-dropdown', ['accountConfig' => $accountConfig])
            </div>
            @if(!empty($headerLayout['mobile.drawer']))
                <button type="button" class="ml-auto inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-700 transition active:scale-[0.985] lg:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pharma-mobile-menu" aria-label="Mở menu điều hướng" title="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path x-show="!menuOpen" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="menuOpen" x-cloak d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
        @if(collect($headerLayout['mobile.drawer'] ?? [])->contains(fn ($component) => ($component['type'] ?? null) === 'mobile-menu'))
            <template x-teleport="body">
                <div x-show="menuOpen" x-cloak class="fixed inset-0 z-[9999] lg:hidden">
                    <div class="absolute inset-0 bg-slate-950/50" @click="menuOpen = false" aria-hidden="true"></div>
                    <aside id="pharma-mobile-menu" x-show="menuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" role="dialog" aria-modal="true" aria-label="Menu điều hướng Pharma" class="absolute inset-y-0 right-0 flex h-full w-[min(88vw,360px)] flex-col overflow-hidden bg-white text-slate-900 shadow-2xl">
                        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-4 py-3">
                            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2" aria-label="{{ $companyName }} - về trang chủ">
                                @if($brandLogoUrl)
                                    <img src="{{ $brandLogoUrl }}" alt="{{ $companyName }}" class="max-w-32 shrink-0 object-contain" style="max-height:{{ min($brandHeight, 40) }}px">
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xl font-bold text-white" style="background:var(--pharma-header-accent)" aria-hidden="true">{{ mb_substr($companyName, 0, 1) }}</span>
                                @endif
                                <span class="min-w-0"><span class="block truncate text-sm font-extrabold">{{ $companyName }}</span><span class="block truncate text-xs text-slate-500">Danh mục dược phẩm</span></span>
                            </a>
                            <button type="button" @click="menuOpen = false" aria-label="Đóng menu" class="flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-xl">×</button>
                        </div>
                        @include('Website::partials.pharma.mobile-account-links', ['accountConfig' => $accountConfig])
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                        <nav aria-label="Điều hướng Pharma trên di động" class="flex flex-col gap-1 px-3 py-3">
                            <span class="px-3 pb-1 text-[11px] font-bold uppercase tracking-widest text-slate-500">Khám phá</span>
                            @foreach($mobileMenu->isNotEmpty() ? $mobileMenu : $fallbackMenu as $item)
                                @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                                @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                                <a href="{{ $itemUrl ?: '#' }}" class="flex min-h-11 items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-emerald-50">{{ $itemTitle }}</a>
                            @endforeach
                        </nav>
                        </div>
                        <div class="shrink-0 border-t border-slate-200 bg-white px-4 pt-3 pb-[calc(12px+env(safe-area-inset-bottom,0px))]">
                            @if(auth()->check())
                                @if(\Illuminate\Support\Facades\Route::has('logout'))
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-xl px-3 text-left text-sm font-semibold text-red-600 hover:bg-red-50">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                            Đăng xuất
                                        </button>
                                    </form>
                                @endif
                            @else
                                <div class="grid grid-cols-2 gap-2">
                                    @if(\Illuminate\Support\Facades\Route::has('login'))
                                        <a href="{{ route('login') }}" class="flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-3 text-center text-sm font-semibold">Đăng nhập</a>
                                    @endif
                                    @if(\Illuminate\Support\Facades\Route::has('register'))
                                        <a href="{{ route('register') }}" class="flex min-h-11 items-center justify-center rounded-xl px-3 text-center text-sm font-semibold text-white" style="background:var(--pharma-header-accent)">Đăng ký</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </aside>
                </div>
            </template>
        @endif
    </header>
</div>

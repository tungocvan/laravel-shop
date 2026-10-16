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
    // The Builder visibility is authoritative when explicitly configured.
    // Legacy layouts without a visibility setting continue using Topbar settings.
    $topbarFallbackVisibility = $topbarVisibleDesktop && $topbarVisibleMobile ? 'both' : ($topbarVisibleDesktop ? 'desktop' : ($topbarVisibleMobile ? 'mobile' : 'hidden'));
    $drawerVisibility = collect($headerLayout['mobile.drawer'] ?? [])->firstWhere('type', 'mobile-menu')['visibility'] ?? 'hidden';
    $drawerEnabled = $drawerVisibility !== 'hidden';
    $mobileSlotVisible = [];
    foreach (['desktop.main.left', 'desktop.main.center', 'desktop.main.right'] as $mobileSlot) {
        $mobileSlotVisible[$mobileSlot] = collect($headerLayout[$mobileSlot] ?? [])->contains(
            static fn ($item) => in_array($item['visibility'] ?? 'desktop', ['mobile', 'both'], true)
        );
    }
    $fallbackMenu = [['title' => 'Trang chủ', 'url' => route('home')], ['title' => 'Danh mục dược phẩm', 'url' => route('product.list')]];
@endphp
<div style="display: contents; --pharma-header-accent: {{ $accent }}; --pharma-header-bg: {{ $background }}; --pharma-header-text: {{ $foreground }};">
    @if(($topbarConfig['enabled'] ?? true))
        @foreach($headerLayout['desktop.topbar'] ?? [] as $component)
            @if($component['type'] === 'topbar')
                @php($topbarVisibility = $component['config']['visibility'] ?? $topbarFallbackVisibility)
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
    <header class="{{ ($presentation['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-40 border-b border-slate-200" style="background:var(--pharma-header-bg);color:var(--pharma-header-text);box-shadow:{{ $shadow }}" x-data="{ menuOpen: false, quickOrderLoginPrompt: false }" @keydown.escape.window="menuOpen = false; quickOrderLoginPrompt = false" @click.capture="@guest const link = $event.target.closest('a[href]'); if (link && new URL(link.href, window.location.origin).pathname.replace(/\\/$/, '') === '/quick-order') { $event.preventDefault(); $event.stopPropagation(); menuOpen = false; quickOrderLoginPrompt = true; } @endguest">
        <div class="mx-auto flex w-full min-w-0 items-center gap-2 px-3 py-3 sm:gap-3 sm:px-6 lg:gap-6 lg:px-8" style="max-width:{{ $headerWidth ? $headerWidth.'px' : '1280px' }};min-height:{{ (int)($presentation['heights']['desktop'] ?? 80) }}px">
            @foreach(['desktop.main.left','desktop.main.center','desktop.main.right'] as $slot)
                <div class="{{ $mobileSlotVisible[$slot] ? 'flex' : 'hidden lg:flex' }} {{ $slot === 'desktop.main.left' ? 'min-w-0 flex-1 items-center lg:flex-none' : ($slot === 'desktop.main.center' ? 'min-w-0 items-center justify-center gap-5 lg:flex-1' : 'min-w-0 items-center justify-end gap-4 lg:ml-auto') }}">
                @foreach($headerLayout[$slot] ?? [] as $component)
                    @if($component['type'] === 'brand')
                        <a href="{{ route('home') }}" class="{{ $visibilityClass($component['visibility'] ?? 'desktop') }} min-w-0 max-w-full items-center gap-2 sm:gap-3" aria-label="Về trang chủ">
                            @if($brandLogoUrl)
                                <img src="{{ $brandLogoUrl }}" alt="{{ $companyName }}" class="w-auto max-w-[96px] shrink-0 object-contain sm:max-w-40" style="max-height:{{ $brandHeight }}px">
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-2xl font-bold text-white shadow-sm" style="background:var(--pharma-header-accent);max-height:{{ $brandHeight }}px" aria-hidden="true">{{ mb_substr($companyName, 0, 1) }}</span>
                            @endif
                            <span class="min-w-0 max-w-[130px] sm:max-w-none"><span class="block line-clamp-2 break-words text-[11px] font-extrabold leading-tight sm:truncate sm:text-lg">{{ $companyName }}</span><span class="hidden text-xs font-medium sm:block" style="color:var(--pharma-header-accent)">Danh mục và thông tin dược phẩm</span></span>
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
            @foreach($headerLayout['mobile.search'] ?? [] as $mobileSearch)
                @if(($mobileSearch['type'] ?? null) === 'search')
                    <form action="{{ route('product.list') }}" method="GET" role="search" class="{{ $visibilityClass($mobileSearch['visibility'] ?? 'mobile') }} min-w-0 items-center gap-2">
                        <label class="sr-only" for="pharma-mobile-search">Tìm sản phẩm dược phẩm</label>
                        <input id="pharma-mobile-search" name="search" type="search" value="{{ request('search') }}" placeholder="Tìm thuốc..." class="w-24 min-w-0 rounded-lg border border-slate-300 px-2 py-2 text-sm sm:w-40">
                        <button type="submit" class="rounded-lg px-2 py-2 text-sm font-semibold text-white" style="background:var(--pharma-header-accent)">Tìm</button>
                    </form>
                @endif
            @endforeach
            @if($drawerEnabled)
                <button type="button" class="ml-auto {{ $visibilityClass($drawerVisibility) }} h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-700 transition active:scale-[0.985]" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pharma-mobile-menu" aria-label="Mở menu điều hướng" title="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path x-show="!menuOpen" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="menuOpen" x-cloak d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
        @guest
            <template x-teleport="body">
                <div x-show="quickOrderLoginPrompt" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-950/60 px-4 backdrop-blur-[2px]" role="presentation" @click.self="quickOrderLoginPrompt = false">
                    <section role="dialog" aria-modal="true" aria-labelledby="pharma-quick-order-login-title" aria-describedby="pharma-quick-order-login-description" class="w-full max-w-sm rounded-2xl bg-white p-6 text-slate-900 shadow-2xl">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5M15 12H3M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>
                        </div>
                        <h2 id="pharma-quick-order-login-title" class="text-lg font-extrabold">Vui lòng đăng nhập để đặt hàng nhanh</h2>
                        <p id="pharma-quick-order-login-description" class="mt-2 text-sm leading-6 text-slate-600">Chức năng đặt hàng nhanh chỉ dành cho tài khoản đã đăng nhập và được cấp quyền sử dụng.</p>
                        <div class="mt-6 flex flex-col gap-2">
                            <a href="{{ route('login') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800">Đăng nhập</a>
                            <button type="button" @click="quickOrderLoginPrompt = false" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Để sau</button>
                        </div>
                    </section>
                </div>
            </template>
        @endguest
        @if($drawerEnabled)
            <template x-teleport="body">
                <div x-show="menuOpen" x-cloak class="fixed inset-0 z-[9999] {{ $drawerVisibility === 'mobile' ? 'lg:hidden' : ($drawerVisibility === 'desktop' ? 'hidden lg:block' : '') }}">
                    <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]" @click="menuOpen = false" aria-hidden="true"></div>
                    <aside id="pharma-mobile-menu" x-show="menuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" role="dialog" aria-modal="true" aria-label="Menu điều hướng Pharma" class="absolute inset-y-0 right-0 flex h-full w-[min(88vw,360px)] flex-col overflow-hidden bg-white text-slate-900 shadow-2xl" style="--pharma-header-accent: {{ $accent }};">
                        <div class="shrink-0 border-b border-slate-100 bg-white px-4 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center gap-3" aria-label="{{ $companyName }} - về trang chủ">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                                        @if($brandLogoUrl)
                                            <img src="{{ $brandLogoUrl }}" alt="" class="h-full w-full object-contain">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-xl font-bold text-white" style="background:var(--pharma-header-accent)" aria-hidden="true">{{ mb_substr($companyName, 0, 1) }}</span>
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block line-clamp-2 break-words text-[13px] font-extrabold leading-snug text-slate-900">{{ $companyName }}</span>
                                        <span class="mt-0.5 block text-[11px] font-medium text-slate-500">Dược phẩm &amp; chăm sóc sức khỏe</span>
                                    </span>
                                </a>
                                <button type="button" @click="menuOpen = false" aria-label="Đóng menu" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 transition hover:bg-slate-100 active:scale-[0.985] focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 motion-reduce:transform-none">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-white">
                            @include('Website::partials.pharma.mobile-account-links', ['accountConfig' => $accountConfig])
                            <nav aria-label="Điều hướng Pharma trên di động" class="px-4 pb-6 pt-4">
                                <div class="mb-2 px-2 text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Khám phá</div>
                                <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-100 bg-white">
                                    @foreach($mobileMenu->isNotEmpty() ? $mobileMenu : $fallbackMenu as $item)
                                        @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                                        @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                                        @php($isActiveMobileItem = $itemUrl && $itemUrl !== '#' && (url()->current() === $itemUrl || request()->fullUrl() === $itemUrl))
                                        <a href="{{ $itemUrl ?: '#' }}" @if($isActiveMobileItem) aria-current="page" @endif class="group flex min-h-12 items-center px-4 py-3.5 text-sm font-semibold leading-5 transition-colors active:scale-[0.985] motion-reduce:transform-none {{ $isActiveMobileItem ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-50 hover:text-emerald-800' }}">
                                            <span class="min-w-0 flex-1">{{ $itemTitle }}</span>
                                            @if($isActiveMobileItem)
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-600" aria-hidden="true"></span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </nav>
                        </div>
                        <div class="shrink-0 border-t border-slate-200 bg-white px-4 pt-4 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
                            @if(auth()->check())
                                @if(\Illuminate\Support\Facades\Route::has('logout'))
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-red-100 bg-red-50 px-4 text-center text-sm font-semibold text-red-700 transition hover:bg-red-100 active:scale-[0.985] motion-reduce:transform-none">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                            Đăng xuất
                                        </button>
                                    </form>
                                @endif
                            @else
                                <div class="flex flex-col items-center gap-3">
                                    @if(\Illuminate\Support\Facades\Route::has('login'))
                                        <a href="{{ route('login') }}" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-4 text-center text-sm font-bold text-white shadow-sm transition hover:brightness-95 active:scale-[0.985] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:transform-none" style="background-color: {{ $accent }};"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>Đăng nhập</a>
                                    @endif
                                    @if(\Illuminate\Support\Facades\Route::has('register'))
                                        <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center px-3 text-center text-xs font-medium text-slate-500 transition hover:text-emerald-800 active:scale-[0.985] motion-reduce:transform-none">Chưa có tài khoản? <span class="ml-1 font-bold text-emerald-800">Đăng ký</span></a>
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

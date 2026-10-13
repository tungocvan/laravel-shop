@php
    $settings = app(\Modules\System\Services\SettingsService::class);
    $companyName = (string) $settings->get('site_name', 'Dược phẩm');
    $savedLayout = $settings->get('website.pharma_header.layout');
    $rawLayout = is_array($savedLayout) ? $savedLayout : (array) config('website.header.pharma_layout', []);
    $headerLayout = app(\Modules\Website\Services\HeaderLayoutService::class)->resolvedLayout($rawLayout);
    $savedPresentation = $settings->get('website.pharma_header.presentation');
    $presentation = app(\Modules\Website\Services\HeaderPresentationService::class)->resolve(is_array($savedPresentation) ? $savedPresentation : null);
    $colors = $presentation['colors'];
    $accent = ($presentation['inherit_colors'] ?? true) ? '#047857' : $colors['accent'];
    $background = ($presentation['inherit_colors'] ?? true) ? '#ffffff' : $colors['background'];
    $foreground = ($presentation['inherit_colors'] ?? true) ? '#0f172a' : $colors['foreground'];
    $topbarBackground = ($presentation['inherit_colors'] ?? true) ? '#020617' : $colors['topbar_background'];
    $topbarForeground = ($presentation['inherit_colors'] ?? true) ? '#e2e8f0' : $colors['topbar_foreground'];
    $shadow = match($presentation['shadow'] ?? 'soft') { 'none' => 'none', 'medium' => '0 8px 24px rgba(15,23,42,.15)', default => '0 1px 3px rgba(15,23,42,.08)' };
    $headerWidth = $presentation['container_width'] ?? null;
    $menuService = app(\Modules\Website\Services\HeaderMenuService::class);
    $desktopMenu = $menuService->getMenuTreeByLocation('pharma_primary');
    $mobileMenu = $menuService->getMenuTreeByLocation('pharma_mobile');
    if ($mobileMenu->isEmpty()) $mobileMenu = $desktopMenu;
    $fallbackMenu = [['title' => 'Trang chủ', 'url' => route('home')], ['title' => 'Danh mục dược phẩm', 'url' => route('product.list')]];
@endphp
<div style="--pharma-header-accent: {{ $accent }}; --pharma-header-bg: {{ $background }}; --pharma-header-text: {{ $foreground }};">
    @foreach($headerLayout['desktop.topbar'] ?? [] as $component)
        @if($component['type'] === 'topbar')
            <div class="border-b border-teal-900" style="background: {{ $topbarBackground }}; color: {{ $topbarForeground }};">
                <div class="mx-auto flex flex-wrap items-center justify-between gap-2 px-5 py-2 text-xs sm:px-8" @if($headerWidth) style="max-width: {{ $headerWidth }}px" @else style="max-width:1280px" @endif>
                    <span>THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO</span>
                    <span class="hidden sm:inline">Thông tin không thay thế tư vấn chuyên môn y tế</span>
                </div>
            </div>
        @endif
    @endforeach
    <header class="{{ ($presentation['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-40 border-b border-slate-200" style="background:var(--pharma-header-bg);color:var(--pharma-header-text);box-shadow:{{ $shadow }}" x-data="{ menuOpen: false }">
        <div class="mx-auto flex flex-wrap items-center justify-between gap-5 px-5 sm:px-8" style="max-width:{{ $headerWidth ? $headerWidth.'px' : '1280px' }};min-height:{{ (int)($presentation['heights']['desktop'] ?? 80) }}px">
            @foreach(['desktop.main.left','desktop.main.center','desktop.main.right'] as $slot)
                @foreach($headerLayout[$slot] ?? [] as $component)
                    @if($component['type'] === 'brand')
                        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="Về trang chủ">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-2xl font-bold text-white" style="background:var(--pharma-header-accent)" aria-hidden="true">+</span>
                            <span class="min-w-0"><span class="block truncate text-base font-extrabold sm:text-lg">{{ $companyName }}</span><span class="block text-xs font-medium" style="color:var(--pharma-header-accent)">Danh mục và thông tin dược phẩm</span></span>
                        </a>
                    @elseif($component['type'] === 'actions')
                        <nav aria-label="Điều hướng Pharma" class="hidden items-center gap-6 text-sm font-semibold md:flex">
                            @foreach($desktopMenu->isNotEmpty() ? $desktopMenu : $fallbackMenu as $item)
                                @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                                @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                                <a href="{{ $itemUrl ?: '#' }}" class="hover:underline">{{ $itemTitle }}</a>
                            @endforeach
                        </nav>
                        <div class="hidden items-center gap-3 text-sm lg:flex">
                            @guest
                                <a href="{{ route('login') }}" class="font-semibold hover:underline">Đăng nhập</a>
                                <a href="{{ route('register') }}" class="rounded-xl border px-3 py-2 font-bold" style="border-color:var(--pharma-header-accent);color:var(--pharma-header-accent)">Đăng ký</a>
                            @else
                                <a href="{{ route('account.dashboard') }}" class="font-semibold" style="color:var(--pharma-header-accent)">Tài khoản</a>
                            @endguest
                        </div>
                    @elseif($component['type'] === 'search')
                        <form action="{{ route('product.list') }}" method="GET" role="search" class="hidden items-center gap-2 lg:flex">
                            <label class="sr-only" for="pharma-header-search-{{ str_replace('.', '-', $slot) }}">Tìm sản phẩm dược phẩm</label>
                            <input id="pharma-header-search-{{ str_replace('.', '-', $slot) }}" name="search" type="search" value="{{ request('search') }}" placeholder="Tên thuốc, hoạt chất..." class="w-48 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:outline-none xl:w-56">
                            <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold text-white" style="background:var(--pharma-header-accent)">Tra cứu →</button>
                        </form>
                    @endif
                @endforeach
            @endforeach
            @if(!empty($headerLayout['mobile.drawer']))
                <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold md:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pharma-mobile-menu">Menu</button>
            @endif
        </div>
        @foreach($headerLayout['mobile.drawer'] ?? [] as $component)
            @if($component['type'] === 'mobile-menu')
                <nav id="pharma-mobile-menu" x-show="menuOpen" x-cloak aria-label="Điều hướng Pharma trên di động" class="border-t border-slate-200 px-5 py-4 md:hidden">
                    <div class="flex flex-col gap-4 text-sm font-semibold">
                        @foreach($mobileMenu->isNotEmpty() ? $mobileMenu : $fallbackMenu as $item)
                            @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                            @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                            <a href="{{ $itemUrl ?: '#' }}">{{ $itemTitle }}</a>
                        @endforeach
                        @guest
                            <a href="{{ route('login') }}">Đăng nhập</a>
                            <a href="{{ route('register') }}">Đăng ký</a>
                        @else
                            <a href="{{ route('account.dashboard') }}">Tài khoản của tôi</a>
                        @endguest
                        @if(!empty($headerLayout['mobile.search']))
                            <form action="{{ route('product.list') }}" method="GET" role="search" class="flex gap-2">
                                <input name="search" type="search" aria-label="Tìm sản phẩm" placeholder="Tìm sản phẩm..." class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2">
                                <button type="submit" class="rounded-lg px-4 py-2 text-white" style="background:var(--pharma-header-accent)">Tìm</button>
                            </form>
                        @endif
                    </div>
                </nav>
            @endif
        @endforeach
    </header>
</div>

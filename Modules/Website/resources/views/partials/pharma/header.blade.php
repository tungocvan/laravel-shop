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
    $searchWidth = (int) ($presentation['custom']['search_max_width'] ?? 560);
    $brandHeight = (int) ($presentation['custom']['logo_max_height'] ?? 48);
    $accountConfig = $settings->get('website.pharma_header.account', []);
    $topbarConfig = $settings->get('website.pharma_header.topbar', []);
    $topbarConfig = is_array($topbarConfig) ? $topbarConfig : [];
    $topbarHeight = (int) ($presentation['custom']['topbar_height'] ?? 32);
    $menuService = app(\Modules\Website\Services\HeaderMenuService::class);
    $desktopMenu = $menuService->getMenuTreeByLocation('pharma_primary');
    $mobileMenu = $menuService->getMenuTreeByLocation('pharma_mobile');
    if ($mobileMenu->isEmpty()) $mobileMenu = $desktopMenu;
    $fallbackMenu = [['title' => 'Trang chủ', 'url' => route('home')], ['title' => 'Danh mục dược phẩm', 'url' => route('product.list')]];
@endphp
<div style="--pharma-header-accent: {{ $accent }}; --pharma-header-bg: {{ $background }}; --pharma-header-text: {{ $foreground }};">
    @if(($topbarConfig['enabled'] ?? true))
        @foreach($headerLayout['desktop.topbar'] ?? [] as $component)
            @if($component['type'] === 'topbar')
                <div class="border-b border-slate-800/20" style="background: {{ $topbarBackground }}; color: {{ $topbarForeground }};">
                    <div class="mx-auto flex flex-wrap items-center justify-between gap-x-5 gap-y-1 px-5 py-1.5 text-xs sm:px-8" style="max-width: {{ $headerWidth ? $headerWidth.'px' : '1280px' }}; min-height: {{ $topbarHeight }}px">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            @if(trim((string) ($topbarConfig['left_text'] ?? 'THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO')) !== '')
                                <span>{{ $topbarConfig['left_text'] ?? 'THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO' }}</span>
                            @endif
                            @if(($topbarConfig['show_hotline'] ?? false) && filled($topbarConfig['hotline'] ?? null))
                                <a href="tel:{{ preg_replace('/[^+0-9]/', '', (string) $topbarConfig['hotline']) }}" class="font-semibold hover:underline">{{ $topbarConfig['hotline'] }}</a>
                            @endif
                            @if(($topbarConfig['show_email'] ?? false) && filter_var($topbarConfig['email'] ?? null, FILTER_VALIDATE_EMAIL))
                                <a href="mailto:{{ $topbarConfig['email'] }}" class="hover:underline">{{ $topbarConfig['email'] }}</a>
                            @endif
                        </div>
                        @if(trim((string) ($topbarConfig['right_text'] ?? 'Thông tin không thay thế tư vấn chuyên môn y tế')) !== '')
                            <span class="hidden sm:inline">{{ $topbarConfig['right_text'] ?? 'Thông tin không thay thế tư vấn chuyên môn y tế' }}</span>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    @endif
    <header class="{{ ($presentation['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-40 border-b border-slate-200" style="background:var(--pharma-header-bg);color:var(--pharma-header-text);box-shadow:{{ $shadow }}" x-data="{ menuOpen: false }">
        <div class="mx-auto flex items-center gap-4 px-4 py-3 sm:px-6 lg:gap-6 lg:px-8" style="max-width:{{ $headerWidth ? $headerWidth.'px' : '1280px' }};min-height:{{ (int)($presentation['heights']['desktop'] ?? 80) }}px">
            @foreach(['desktop.main.left','desktop.main.center','desktop.main.right'] as $slot)
                <div class="{{ $slot === 'desktop.main.left' ? 'flex min-w-0 shrink-0 items-center' : ($slot === 'desktop.main.center' ? 'hidden min-w-0 flex-1 items-center justify-center gap-5 lg:flex' : 'ml-auto hidden min-w-0 items-center justify-end gap-4 lg:flex') }}">
                @foreach($headerLayout[$slot] ?? [] as $component)
                    @if($component['type'] === 'brand')
                        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="Về trang chủ">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-2xl font-bold text-white shadow-sm" style="background:var(--pharma-header-accent);max-height:{{ $brandHeight }}px" aria-hidden="true">+</span>
                            <span class="min-w-0"><span class="block truncate text-base font-extrabold sm:text-lg">{{ $companyName }}</span><span class="block text-xs font-medium" style="color:var(--pharma-header-accent)">Danh mục và thông tin dược phẩm</span></span>
                        </a>
                    @elseif($component['type'] === 'actions')
                        <nav aria-label="Điều hướng Pharma" class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-semibold">
                            @foreach($desktopMenu->isNotEmpty() ? $desktopMenu : $fallbackMenu as $item)
                                @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                                @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                                <a href="{{ $itemUrl ?: '#' }}" class="hover:underline">{{ $itemTitle }}</a>
                            @endforeach
                        </nav>
                    @elseif($component['type'] === 'search')
                        <form action="{{ route('product.list') }}" method="GET" role="search" class="flex min-w-0 items-center gap-2" style="max-width:{{ $searchWidth }}px">
                            <label class="sr-only" for="pharma-header-search-{{ str_replace('.', '-', $slot) }}">Tìm sản phẩm dược phẩm</label>
                            <input id="pharma-header-search-{{ str_replace('.', '-', $slot) }}" name="search" type="search" value="{{ request('search') }}" placeholder="Tên thuốc, hoạt chất..." class="w-32 min-w-0 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:outline-none xl:w-48 2xl:w-56">
                            <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold text-white" style="background:var(--pharma-header-accent)">Tra cứu →</button>
                        </form>
                    @endif
                @endforeach
                </div>
            @endforeach
            <div class="ml-auto flex shrink-0 items-center justify-end lg:ml-0">
                @include('Website::partials.pharma.account-dropdown', ['accountConfig' => $accountConfig])
            </div>
            @if(!empty($headerLayout['mobile.drawer']))
                <button type="button" class="ml-auto rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold lg:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pharma-mobile-menu">Menu</button>
            @endif
        </div>
        @foreach($headerLayout['mobile.drawer'] ?? [] as $component)
            @if($component['type'] === 'mobile-menu')
                <nav id="pharma-mobile-menu" x-show="menuOpen" x-cloak aria-label="Điều hướng Pharma trên di động" class="border-t border-slate-200 px-5 py-4 lg:hidden">
                    <div class="flex flex-col gap-4 text-sm font-semibold">
                        @foreach($mobileMenu->isNotEmpty() ? $mobileMenu : $fallbackMenu as $item)
                            @php($itemUrl = is_array($item) ? $item['url'] : $item->url)
                            @php($itemTitle = is_array($item) ? $item['title'] : $item->title)
                            <a href="{{ $itemUrl ?: '#' }}">{{ $itemTitle }}</a>
                        @endforeach
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

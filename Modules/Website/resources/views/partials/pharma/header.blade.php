@php
    $settings = app(\Modules\System\Services\SettingsService::class);
    $companyName = (string) $settings->get('site_name', 'Dược phẩm');
@endphp
<div class="border-b border-teal-900 bg-slate-950 text-slate-200">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-5 py-2 text-xs sm:px-8">
        <span>THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO</span>
        <span class="hidden sm:inline">Thông tin không thay thế tư vấn chuyên môn y tế</span>
    </div>
</div>
<header class="relative z-40 border-b border-slate-200 bg-white shadow-sm" x-data="{ menuOpen: false }">
    <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-5 px-5 sm:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3" aria-label="Về trang chủ">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-700 text-2xl font-bold text-white" aria-hidden="true">+</span>
            <span class="min-w-0">
                <span class="block truncate text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">{{ $companyName }}</span>
                <span class="block text-xs font-medium tracking-wide text-teal-700">Danh mục và thông tin dược phẩm</span>
            </span>
        </a>
        <nav aria-label="Điều hướng Pharma" class="hidden items-center gap-8 text-sm font-semibold text-slate-700 md:flex">
            <a href="{{ route('home') }}" class="hover:text-teal-700 {{ request()->routeIs('home') ? 'text-teal-700' : '' }}">Trang chủ</a>
            <a href="{{ route('product.list') }}" class="hover:text-teal-700 {{ request()->routeIs('product.*') ? 'text-teal-700' : '' }}">Danh mục dược phẩm</a>
        </nav>
        <div class="hidden items-center gap-3 lg:flex">
            <form id="pharma-desktop-search" action="{{ route('product.list') }}" method="GET" role="search">
                <label class="sr-only" for="pharma-header-search">Tìm sản phẩm dược phẩm</label>
                <input id="pharma-header-search" name="search" type="search" value="{{ request('search') }}" placeholder="Tên thuốc, hoạt chất..." class="w-56 rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100 xl:w-64">
            </form>
            <button form="pharma-desktop-search" type="submit" class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-800">Tra cứu →</button>
            @guest
                <a href="{{ route('login') }}" class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-teal-700">Đăng nhập</a>
                <a href="{{ route('register') }}" class="whitespace-nowrap rounded-xl border border-teal-700 px-3 py-2 text-sm font-bold text-teal-800 hover:bg-teal-50">Đăng ký</a>
            @else
                <a href="{{ route('account.dashboard') }}" class="whitespace-nowrap text-sm font-semibold text-teal-800">Tài khoản</a>
            @endguest
        </div>
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 md:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pharma-mobile-menu">Menu</button>
    </div>
    <nav id="pharma-mobile-menu" x-show="menuOpen" x-cloak aria-label="Điều hướng Pharma trên di động" class="border-t border-slate-200 bg-white px-5 py-4 md:hidden">
        <div class="flex flex-col gap-4 text-sm font-semibold">
            <a href="{{ route('home') }}">Trang chủ</a>
            <a href="{{ route('product.list') }}">Danh mục dược phẩm</a>
            @guest
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-white">Đăng ký</a>
                </div>
            @else
                <a href="{{ route('account.dashboard') }}">Tài khoản của tôi</a>
            @endguest
            <form action="{{ route('product.list') }}" method="GET" role="search" class="flex gap-2">
                <label class="sr-only" for="pharma-mobile-search">Tìm sản phẩm</label>
                <input id="pharma-mobile-search" name="search" type="search" placeholder="Tìm sản phẩm..." class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2">
                <button class="rounded-lg bg-teal-700 px-4 py-2 text-white" type="submit">Tìm</button>
            </form>
        </div>
    </nav>
</header>

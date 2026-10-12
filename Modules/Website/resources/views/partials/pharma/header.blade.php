<header class="relative z-30 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur" x-data="{ menuOpen: false }">
    <div class="border-b border-slate-100 bg-emerald-950 py-2 text-center text-xs font-medium text-white">Thông tin sản phẩm dược phẩm</div>
    <div class="mx-auto flex min-h-20 max-w-[1440px] items-center justify-between gap-5 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="inline-flex min-w-0 items-center gap-3 text-lg font-extrabold tracking-tight text-emerald-900 sm:text-xl" aria-label="Trang chủ">
            <span aria-hidden="true" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-xl font-bold text-white">+</span>
            <span class="truncate">{{ app(\Modules\System\Services\SettingsService::class)->get('site_name', 'Pharma') }}</span>
        </a>
        <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-700 md:flex" aria-label="Điều hướng chính">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Trang chủ</a>
            <a href="{{ route('product.list') }}" class="hover:text-emerald-700">Sản phẩm</a>
        </nav>
        <form method="GET" action="{{ route('product.list') }}" role="search" class="hidden w-full max-w-sm lg:block">
            <label for="pharma-search" class="sr-only">Tìm sản phẩm</label>
            <input id="pharma-search" name="search" value="{{ request('search') }}" placeholder="Tìm sản phẩm..." class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
        </form>
        <button type="button" class="rounded-lg border border-slate-200 p-3 text-slate-800 md:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="pharma-mobile-menu" aria-label="Mở hoặc đóng menu">☰</button>
    </div>
    <div id="pharma-mobile-menu" x-cloak x-show="menuOpen" class="border-t border-slate-200 px-4 py-4 md:hidden">
        <nav class="flex flex-col gap-3 text-sm font-semibold" aria-label="Điều hướng di động">
            <a href="{{ route('home') }}">Trang chủ</a>
            <a href="{{ route('product.list') }}">Sản phẩm</a>
        </nav>
        <form method="GET" action="{{ route('product.list') }}" role="search" class="mt-4">
            <label for="pharma-mobile-search" class="sr-only">Tìm sản phẩm</label>
            <input id="pharma-mobile-search" name="search" value="{{ request('search') }}" placeholder="Tìm sản phẩm..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
        </form>
    </div>
</header>

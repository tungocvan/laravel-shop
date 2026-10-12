@php($pharmaSettings = app(\Modules\System\Services\SettingsService::class))
<footer class="border-t border-emerald-900 bg-slate-950 text-slate-200">
    <div class="mx-auto grid w-full max-w-[1440px] gap-10 px-5 py-16 sm:grid-cols-2 sm:px-8 lg:grid-cols-4 lg:gap-12 lg:px-12">
        <div class="lg:col-span-2">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xl font-extrabold text-white"><span aria-hidden="true" class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-700 text-white"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>{{ $pharmaSettings->get('site_name', 'Pharma') }}</a>
            <p class="mt-4 max-w-sm text-sm leading-7 text-slate-400">Tra cứu thông tin sản phẩm dược phẩm và danh mục đã được cập nhật.</p>
            <p class="mt-4 max-w-sm text-xs leading-6 text-slate-500">Thông tin trên Website mang tính tham khảo, không thay thế tư vấn của nhân viên y tế.</p>
        </div>
        <nav aria-label="Liên kết Website">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Liên kết</h2>
            <a href="{{ route('home') }}" class="block py-2 text-sm transition hover:text-emerald-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-300">Trang chủ</a>
            <a href="{{ route('product.list') }}" class="block py-2 text-sm hover:text-emerald-300">Sản phẩm dược phẩm</a>
        </nav>
        <div>
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Thông tin dược phẩm</h2>
            <p class="text-sm leading-7 text-slate-400">Khám phá danh mục sản phẩm và thông tin chi tiết được công bố trên Website.</p>
            <a href="{{ route('product.list') }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-300 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-300">Xem danh mục →</a>
        </div>
    </div>
    <div class="border-t border-slate-800 px-4 py-5 text-center text-xs text-slate-500">© {{ date('Y') }} {{ $pharmaSettings->get('site_name', 'Pharma') }}. All rights reserved.</div>
</footer>

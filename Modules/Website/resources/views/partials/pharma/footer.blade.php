<footer class="border-t border-slate-800 bg-slate-950 text-slate-200">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-2 lg:px-8">
        <div>
            <h2 class="text-xl font-bold text-white">{{ app(\Modules\System\Services\SettingsService::class)->get('site_name', 'Pharma') }}</h2>
            <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">Thông tin sản phẩm dược phẩm và danh mục sản phẩm.</p>
        </div>
        <div class="md:text-right">
            <h3 class="font-semibold text-white">Điều hướng</h3>
            <a href="{{ route('home') }}" class="mt-3 block text-sm hover:text-teal-300">Trang chủ</a>
            <a href="{{ route('product.list') }}" class="mt-2 block text-sm hover:text-teal-300">Sản phẩm</a>
        </div>
    </div>
    <div class="border-t border-slate-800 px-4 py-5 text-center text-xs text-slate-400">© {{ date('Y') }} {{ app(\Modules\System\Services\SettingsService::class)->get('site_name', 'Pharma') }}.</div>
</footer>

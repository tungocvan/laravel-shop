@php($companyName = (string) app(\Modules\System\Services\SettingsService::class)->get('site_name', 'Dược phẩm'))
<footer class="bg-slate-950 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-8 md:grid-cols-3">
        <div>
            <a href="{{ route('home') }}" class="text-xl font-extrabold text-white">{{ $companyName }}</a>
            <p class="mt-4 max-w-sm text-sm leading-7">Cung cấp thông tin sản phẩm và danh mục dược phẩm được công bố để hỗ trợ tra cứu, tìm hiểu và tham khảo.</p>
        </div>
        <div>
            <h2 class="font-bold uppercase tracking-wider text-white">Khám phá</h2>
            <nav aria-label="Liên kết cuối trang" class="mt-4 flex flex-col gap-3 text-sm">
                <a class="hover:text-white" href="{{ route('home') }}">Trang chủ</a>
                <a class="hover:text-white" href="{{ route('product.list') }}">Danh mục dược phẩm</a>
            </nav>
        </div>
        <div>
            <h2 class="font-bold uppercase tracking-wider text-white">Lưu ý y khoa</h2>
            <p class="mt-4 text-sm leading-7">Thông tin trên website mang tính tham khảo, không thay thế chẩn đoán, chỉ định hoặc tư vấn của nhân viên y tế. Sử dụng thuốc theo hướng dẫn của người có chuyên môn.</p>
        </div>
    </div>
    <div class="border-t border-slate-800 px-5 py-5 text-center text-xs text-slate-400">© {{ date('Y') }} {{ $companyName }}. Bảo lưu quyền.</div>
</footer>

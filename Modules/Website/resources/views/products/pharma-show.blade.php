<div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Breadcrumb" class="mb-6 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-emerald-700">Trang chủ</a><span class="mx-2">›</span><a href="{{ route('product.list') }}" class="hover:text-emerald-700">Sản phẩm</a><span class="mx-2">›</span><span class="font-semibold text-slate-700">{{ $product->title }}</span></nav>
        <div class="grid gap-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] md:p-8">
            <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-gradient-to-br from-slate-50 to-emerald-50/50 p-6">
                @if($product->image)
                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="h-full w-full object-contain">
                @else
                    <div class="flex flex-col items-center gap-4 text-slate-400"><svg aria-hidden="true" viewBox="0 0 80 80" fill="none" class="h-24 w-24 text-emerald-200"><rect x="17" y="9" width="46" height="62" rx="8" stroke="currentColor" stroke-width="3"/><path d="M40 24v25M27.5 36.5h25" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg><span class="text-sm">Chưa có ảnh sản phẩm</span></div>
                @endif
            </div>
            <div>
                <div class="mb-3 flex flex-wrap gap-2">
                    @foreach($product->categories->where('type', 'medicine') as $category)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">{{ $category->name }}</span>
                    @endforeach
                </div>
                <h1 class="text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">{{ $product->title }}</h1>
                @if(filled($product->short_description))
                    <p class="mt-5 text-sm leading-7 text-slate-600">{{ strip_tags($product->short_description) }}</p>
                @endif
                <div class="mt-7 rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">
                    <p class="text-sm font-bold text-slate-800">Thông tin sản phẩm mang tính tham khảo</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Việc sử dụng thuốc cần tuân thủ hướng dẫn của nhân viên y tế. Thông tin trên Website không thay thế tư vấn chuyên môn.</p>
                </div>
                <a href="{{ route('product.list') }}" class="mt-7 inline-flex items-center rounded-xl border border-emerald-600 px-5 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">← Trở về danh mục sản phẩm</a>
            </div>
        </div>
        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <h2 class="border-b border-emerald-200 pb-4 text-xl font-extrabold">Thông tin chi tiết</h2>
                <dl class="mt-5 divide-y divide-slate-100 text-sm">
                    <div class="grid gap-2 py-3 sm:grid-cols-[180px_1fr]"><dt class="font-semibold text-slate-500">Tên sản phẩm</dt><dd class="font-medium text-slate-900">{{ $product->title }}</dd></div>
                    @if($product->categories->where('type', 'medicine')->isNotEmpty())
                        <div class="grid gap-2 py-3 sm:grid-cols-[180px_1fr]"><dt class="font-semibold text-slate-500">Danh mục</dt><dd class="text-slate-900">{{ $product->categories->where('type', 'medicine')->pluck('name')->join(', ') }}</dd></div>
                    @endif
                </dl>
                @if(filled($product->description))
                    <h3 class="mt-7 text-lg font-bold">Mô tả sản phẩm</h3>
                    <div class="mt-3 whitespace-pre-line text-sm leading-8 text-slate-600">{{ strip_tags($product->description) }}</div>
                @endif
            </section>
            <aside class="self-start rounded-2xl border border-emerald-100 bg-emerald-50/70 p-6">
                <h2 class="text-lg font-extrabold">Tra cứu dược phẩm</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">Khám phá các sản phẩm cùng danh mục trong hệ thống.</p>
                <a href="{{ route('product.list') }}" class="mt-5 inline-flex text-sm font-bold text-emerald-700 hover:underline">Xem tất cả sản phẩm →</a>
            </aside>
        </div>
    </div>
</div>

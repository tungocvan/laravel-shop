<div class="bg-slate-50">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="mb-6 text-sm text-slate-500"><a href="{{ route('home') }}">Trang chủ</a> / <a href="{{ route('product.list') }}">Sản phẩm</a> / {{ $product->title }}</nav>
        <div class="grid gap-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2 md:p-10">
            <div class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl bg-slate-100">
                @if($product->image)
                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="h-full w-full object-contain">
                @else
                    <span class="text-slate-400">Chưa có hình ảnh</span>
                @endif
            </div>
            <div>
                <p class="text-sm font-bold uppercase tracking-wider text-teal-700">Thông tin dược phẩm</p>
                <h1 class="mt-3 text-3xl font-extrabold text-slate-900">{{ $product->title }}</h1>
                <p class="mt-5 text-sm text-slate-600">{{ strip_tags($product->short_description ?? '') }}</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach($product->categories->where('type', 'medicine') as $category)
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800">{{ $category->name }}</span>
                    @endforeach
                </div>
                <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">Nội dung chỉ nhằm cung cấp thông tin sản phẩm. Việc sử dụng thuốc cần tuân thủ hướng dẫn của nhân viên y tế.</div>
            </div>
        </div>
        @if(filled($product->description))
            <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 md:p-10">
                <h2 class="mb-4 text-xl font-bold text-slate-900">Thông tin chi tiết</h2>
                <div class="whitespace-pre-line text-sm leading-7 text-slate-700">{{ strip_tags($product->description) }}</div>
            </section>
        @endif
    </div>
</div>

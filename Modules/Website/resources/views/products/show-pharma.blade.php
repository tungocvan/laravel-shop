@extends('Website::layouts.frontend')
@section('title', $product->title)
@section('content')
<div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <nav class="mb-7 flex flex-wrap gap-2 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-teal-700">Trang chủ</a><span>/</span>
            <a href="{{ route('product.list') }}" class="hover:text-teal-700">Danh mục dược phẩm</a><span>/</span>
            <span class="text-slate-800">{{ $product->title }}</span>
        </nav>
        <div class="grid gap-8 {{ count($galleryImages) > 0 ? 'lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]' : 'lg:grid-cols-1' }}">
            @if(count($galleryImages) > 0)
                <div x-data="{ images: @js($galleryImages), active: 0, lightbox: false }" @keydown.escape.window="lightbox = false" class="min-w-0">
                    <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <button type="button" @click="lightbox = true" class="flex h-full w-full items-center justify-center" aria-label="Phóng to ảnh sản phẩm">
                            <img :src="images[active]" alt="{{ $product->title }}" class="h-full w-full object-contain p-6 sm:p-8">
                        </button>
                        <template x-if="images.length > 1">
                            <div class="pointer-events-none absolute inset-x-3 bottom-3 flex justify-between">
                                <button type="button" @click="active = (active + images.length - 1) % images.length" class="pointer-events-auto rounded-full bg-white/95 px-3 py-2 text-slate-800 shadow" aria-label="Ảnh trước">‹</button>
                                <button type="button" @click="active = (active + 1) % images.length" class="pointer-events-auto rounded-full bg-white/95 px-3 py-2 text-slate-800 shadow" aria-label="Ảnh tiếp theo">›</button>
                            </div>
                        </template>
                    </div>
                    @if(count($galleryImages) > 1)
                        <div class="mt-3 flex gap-3 overflow-x-auto pb-2" aria-label="Thư viện ảnh sản phẩm">
                            @foreach($galleryImages as $index => $image)
                                <button type="button" @click="active = {{ $index }}" :aria-pressed="(active === {{ $index }}).toString()" :class="active === {{ $index }} ? 'border-teal-600 ring-2 ring-teal-100' : 'border-slate-200'" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border bg-white p-1.5 transition active:scale-[0.985]" aria-label="Xem ảnh {{ $index + 1 }}">
                                    <img src="{{ $image }}" alt="{{ $product->title }} - ảnh {{ $index + 1 }}" loading="lazy" class="h-full w-full object-contain">
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div x-show="lightbox" x-cloak x-trap.noscroll="lightbox" @click.self="lightbox = false" role="dialog" aria-modal="true" aria-label="Xem ảnh sản phẩm" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/90 p-4">
                        <button type="button" @click="lightbox = false" class="absolute right-4 top-4 rounded-full bg-white px-4 py-2 text-slate-900" aria-label="Đóng ảnh">✕</button>
                        <img :src="images[active]" alt="{{ $product->title }}" class="max-h-[85vh] max-w-full object-contain">
                    </div>
                </div>
            @endif
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9">
                <p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $product->categories->first()?->name ?? 'Dược phẩm' }}</p>
                <h1 class="mt-3 text-2xl font-extrabold leading-tight sm:text-3xl">{{ $product->title }}</h1>
                <p class="mt-3 text-sm text-slate-500">Hồ sơ thông tin dược phẩm · Tra cứu và tham khảo</p>
                @if($authenticated)
                    <div class="mt-6 border-t border-slate-100 pt-6">
                        <p class="text-sm font-semibold text-slate-500">Giá tham khảo</p>
                        <p class="mt-1 text-2xl font-bold text-teal-800">{{ number_format((float) ($product->sale_price > 0 && $product->sale_price < $product->regular_price ? $product->sale_price : $product->regular_price), 0, ',', '.') }}đ</p>
                    </div>
                @endif
                @if($product->pharmaLink?->medicine)
                    @php($medicine = $product->pharmaLink->medicine)
                    <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-6 sm:grid-cols-2">
                        @foreach([
                            'Hoạt chất' => $medicine->active_ingredients,
                            'Hàm lượng' => $medicine->concentration,
                            'Dạng bào chế' => $medicine->dosage_form,
                            'Quy cách đóng gói' => $medicine->packaging_specification,
                            'Số đăng ký' => $medicine->registration_number,
                            'Công ty sản xuất' => $medicine->manufacturing_company,
                            'Nước sản xuất' => $medicine->manufacturing_country,
                        ] as $label => $value)
                            @if(filled($value))
                                <div><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 text-sm font-medium text-slate-900">{{ $value }}</dd></div>
                            @endif
                        @endforeach
                    </dl>
                @endif
                <p class="mt-7 rounded-xl bg-teal-50 p-4 text-sm leading-6 text-teal-900">Thông tin sản phẩm chỉ phục vụ tra cứu và tham khảo, không thay thế tư vấn, chẩn đoán hoặc chỉ định của nhân viên y tế.</p>
            </section>
        </div>
        @if(filled($product->short_description) || filled($product->description))
            <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9">
                <h2 class="text-xl font-bold">Thông tin chi tiết</h2>
                @if(filled($product->short_description))
                    <div class="prose prose-slate mt-5 max-w-none text-sm leading-7">{!! \Illuminate\Support\Str::markdown(strip_tags($product->short_description)) !!}</div>
                @endif
                @if(filled($product->description))
                    <div class="prose prose-slate mt-5 max-w-none text-sm leading-7">{!! \Illuminate\Support\Str::markdown(strip_tags($product->description)) !!}</div>
                @endif
            </section>
        @endif
        @if($related->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-5 text-xl font-bold">Dược phẩm cùng nhóm</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($related as $item)
                        <a href="{{ route('product.detail', $item->slug) }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                            <div class="flex aspect-[4/3] items-center justify-center bg-teal-50/60">
                                @if($item->image)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy" class="h-full w-full object-contain p-4" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                                    <span hidden class="text-xs text-slate-500">Chưa có ảnh sản phẩm</span>
                                @else
                                    <span class="text-xs text-slate-500">Chưa có ảnh sản phẩm</span>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="line-clamp-2 text-sm font-bold">{{ $item->title }}</h3>
                                @if($authenticated)
                                    <p class="mt-2 font-semibold text-teal-800">{{ number_format((float) ($item->sale_price > 0 && $item->sale_price < $item->regular_price ? $item->sale_price : $item->regular_price), 0, ',', '.') }}đ</p>
                                @endif
                                <p class="mt-3 text-xs font-semibold text-teal-700">Xem hồ sơ →</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

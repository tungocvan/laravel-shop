@extends('Website::layouts.frontend')

@section('title', 'Đặt hàng nhanh Pharma')

@section('content')
<main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6" aria-labelledby="quick-order-title">
    <div class="mb-6">
        <p class="text-sm font-semibold text-emerald-700">Pharma</p>
        <h1 id="quick-order-title" class="mt-1 text-2xl font-bold text-gray-900">Đặt hàng nhanh</h1>
        <p class="mt-2 text-sm text-gray-600">Đặt hàng theo bảng giá được doanh nghiệp phê duyệt.</p>
    </div>
    @unless($customerLinked)
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-5" role="status">
            <h2 class="text-base font-semibold text-amber-900">Chưa xác định khách hàng được phép đặt hàng</h2>
            <p class="mt-2 text-sm text-amber-800">Tài khoản của bạn chưa được xác nhận liên kết với khách hàng Pharma. Vui lòng liên hệ quản trị viên để được cấp quyền và thiết lập phạm vi đặt hàng.</p>
        </section>
    @else
        <section aria-label="Danh sách thuốc" class="space-y-3">
            @forelse($items as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-4">{{ $item->name }}</div>
            @empty
                <p class="rounded-xl border border-gray-200 bg-white p-5 text-gray-600">Không có sản phẩm phù hợp.</p>
            @endforelse
        </section>
    @endunless
</main>
@endsection

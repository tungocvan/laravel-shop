@extends('Website::layouts.account')
@section('content-account')
<div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div><h1 class="text-xl font-bold tracking-tight text-slate-900">Đơn hàng của tôi</h1><p class="mt-1 text-sm text-slate-500">Theo dõi yêu cầu và tiến độ xử lý.</p></div>
        <a href="{{ route('website.pharma.quick-order') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">+ Đặt hàng nhanh</a>
    </header>
    <nav class="flex gap-5 border-b border-slate-200" aria-label="Loại đơn hàng">
        <a href="{{ route('account.orders', ['tab' => 'pharma']) }}" class="border-b-2 px-1 pb-3 text-sm font-semibold {{ request('tab', 'pharma') === 'pharma' ? 'border-emerald-700 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">Yêu cầu Pharma <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $pharmaRequests->total() }}</span></a>
        <a href="{{ route('account.orders', ['tab' => 'regular']) }}" class="border-b-2 px-1 pb-3 text-sm font-semibold {{ request('tab') === 'regular' ? 'border-emerald-700 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">Đơn hàng thông thường</a>
    </nav>
    @if(request('tab', 'pharma') === 'pharma')
        <p class="text-xs text-slate-500">Yêu cầu được xét duyệt trước khi xử lý xuất kho. Chấp nhận chưa đồng nghĩa đã xuất hàng.</p>
        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($pharmaRequests as $item)
                    <a href="{{ route('account.pharma-requests.show', $item->id) }}" class="block px-4 py-4 transition hover:bg-slate-50 sm:px-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><div class="font-semibold text-slate-900">Yêu cầu #{{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}</div><div class="mt-1 text-xs text-slate-500">{{ $item->submitted_at?->format('d/m/Y H:i') }}</div></div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->status === 'accepted' ? 'bg-emerald-50 text-emerald-700' : ($item->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$item->status] ?? $item->status }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3"><span class="text-sm text-slate-600">{{ count($item->requested_items ?? []) }} sản phẩm</span><span class="text-sm font-semibold text-emerald-700">Xem chi tiết →</span></div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-sm text-slate-500">Bạn chưa gửi yêu cầu đặt hàng Pharma nào.</div>
                @endforelse
            </div>
        </section>
        {{ $pharmaRequests->links() }}
    @else
        @livewire('website.account.order-list')
    @endif
</div>
@endsection

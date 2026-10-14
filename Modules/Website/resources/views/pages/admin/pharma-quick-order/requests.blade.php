@extends('Admin::layouts.master')
@section('title', 'Yêu cầu đặt hàng Pharma')
@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Pharma / Đặt hàng nhanh</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Yêu cầu đặt hàng</h1><p class="mt-1 text-sm text-slate-500">Tiếp nhận, kiểm tra điều kiện mua dược phẩm và xét duyệt yêu cầu.</p></div>
        <a href="{{ route('admin.website.pharma-quick-order.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Thiết lập đặt hàng nhanh ↗</a>
    </header>
    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <nav class="grid grid-cols-3 gap-2 sm:gap-3" aria-label="Thống kê trạng thái">
        @foreach(['pending_review' => ['Chờ xét duyệt','amber'], 'accepted' => ['Đã chấp nhận','emerald'], 'rejected' => ['Đã từ chối','rose']] as $key => $meta)
            <a href="{{ route('admin.website.pharma-quick-order.requests.index', ['status' => $key]) }}" class="rounded-xl border border-slate-100 bg-white px-3 py-4 shadow-sm transition hover:border-emerald-200 sm:px-5 {{ $status === $key ? 'ring-1 ring-emerald-500' : '' }}">
                <div class="text-2xl font-bold tabular-nums text-slate-900">{{ $counts[$key] ?? 0 }}</div><div class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">{{ $meta[0] }}</div>
            </a>
        @endforeach
    </nav>
    <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
        <div class="space-y-4 border-b border-slate-100 p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.website.pharma-quick-order.requests.index') }}" class="flex flex-wrap gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <label for="pharma-request-search" class="sr-only">Tìm kiếm yêu cầu</label>
                <input id="pharma-request-search" type="search" name="search" value="{{ $search }}" placeholder="Mã yêu cầu, khách hàng, SĐT, mã số thuế..." class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:bg-white">
                <button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Tìm kiếm</button>
            </form>
            <nav class="flex flex-wrap gap-2" aria-label="Lọc yêu cầu">
                @foreach(['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối', 'all' => 'Tất cả'] as $key => $label)
                    <a href="{{ route('admin.website.pharma-quick-order.requests.index', array_filter(['status' => $key, 'search' => $search])) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $status === $key ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-semibold">Mã yêu cầu</th><th class="px-5 py-3 font-semibold">Người mua / Liên hệ</th><th class="px-5 py-3 font-semibold">Ngày gửi</th><th class="px-5 py-3 text-center font-semibold">Sản phẩm</th><th class="px-5 py-3 font-semibold">Trạng thái</th><th class="px-5 py-3 text-right font-semibold">Chi tiết</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $item)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4"><a class="font-semibold text-emerald-700 hover:underline" href="{{ route('admin.website.pharma-quick-order.requests.show', $item) }}">YC-{{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                            <td class="px-5 py-4"><div class="font-medium text-slate-900">{{ $item->company_name ?: ($item->recipient_name ?: 'Chưa cung cấp') }}</div><div class="mt-1 text-xs text-slate-500">{{ $item->phone ?: 'Chưa có SĐT' }}</div></td>
                            <td class="px-5 py-4 whitespace-nowrap text-slate-600">{{ $item->submitted_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-center font-semibold tabular-nums text-slate-700">{{ count($item->requested_items ?? []) }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->status === 'accepted' ? 'bg-emerald-50 text-emerald-700' : ($item->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$item->status] ?? $item->status }}</span></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.website.pharma-quick-order.requests.show', $item) }}" class="font-semibold text-emerald-700 hover:underline">Xem / Thao tác →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Không có yêu cầu phù hợp với bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div>{{ $requests->links() }}</div>
</div>
@endsection

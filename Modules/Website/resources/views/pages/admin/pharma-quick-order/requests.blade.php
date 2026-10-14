@extends('Admin::layouts.master')

@section('title', 'Yêu cầu đặt hàng nhanh Pharma')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Yêu cầu đặt hàng nhanh Pharma</h1>
        <a class="text-sm text-emerald-700 underline" href="{{ route('admin.website.pharma-quick-order.index') }}">Thiết lập đặt hàng nhanh</a>
    </div>
    <p class="mt-2 text-sm text-gray-600">Chấp nhận yêu cầu chỉ là xác nhận xét duyệt người mua; chưa tạo phiếu xuất kho, chưa ghi sổ và chưa trừ tồn.</p>
    <div class="mt-5 flex flex-wrap gap-2">
        @foreach(['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối', 'all' => 'Tất cả'] as $key => $label)
            <a href="{{ route('admin.website.pharma-quick-order.requests.index', ['status' => $key]) }}"
               class="rounded-lg border px-3 py-2 text-sm {{ $status === $key ? 'border-emerald-700 bg-emerald-50 font-semibold text-emerald-800' : 'border-gray-200 text-gray-700' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Mã</th><th class="px-4 py-3 text-left">Ngày gửi</th><th class="px-4 py-3 text-left">Người mua</th><th class="px-4 py-3 text-left">Số mặt hàng</th><th class="px-4 py-3 text-left">Trạng thái</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $item)
                    <tr>
                        <td class="px-4 py-3"><a class="font-semibold text-emerald-700 underline" href="{{ route('admin.website.pharma-quick-order.requests.show', $item) }}">#{{ $item->id }}</a></td>
                        <td class="px-4 py-3">{{ $item->submitted_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $item->company_name ?: ($item->recipient_name ?: 'Chưa cung cấp') }}</td>
                        <td class="px-4 py-3">{{ count($item->requested_items ?? []) }}</td>
                        <td class="px-4 py-3">{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$item->status] ?? $item->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Chưa có yêu cầu trong nhóm này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection

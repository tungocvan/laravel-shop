@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="min-w-0 space-y-4">
    <section class="rounded-3xl bg-slate-950 p-5 text-white">
        <a href="{{ route('client.pharma.kqlcnt-awards.index') }}" class="inline-flex min-h-11 items-center text-sm text-slate-200">← Theo dõi kết quả trúng thầu</a>
        <h1 class="mt-2 break-words text-xl font-black">{{ $item->medicine_name }}</h1>
        <p class="mt-1 text-sm text-slate-300">TBMT {{ $item->notify_no }}</p>
    </section>
    <section class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
        @foreach(['notify_no' => 'Mã TBMT', 'contractor_code' => 'Mã nhà thầu', 'contractor_name' => 'Nhà thầu', 'lot_no' => 'Mã lô', 'lot_name' => 'Tên lô', 'medicine_code' => 'Mã thuốc', 'active_ingredient' => 'Hoạt chất', 'concentration' => 'Hàm lượng', 'dosage_form' => 'Dạng bào chế', 'packaging_spec' => 'Quy cách', 'unit' => 'ĐVT', 'quantity' => 'Số lượng', 'winning_price' => 'Giá trúng thầu', 'amount' => 'Thành tiền', 'manufacturer' => 'Nhà sản xuất', 'country' => 'Nước sản xuất', 'decision_no' => 'Số quyết định', 'investor_name' => 'Chủ đầu tư', 'contract_no' => 'Số hợp đồng'] as $key => $label)
            <div class="min-w-0 border-b border-slate-100 py-2">
                <dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt>
                <dd class="mt-1 break-words text-sm text-slate-900">{{ $item->{$key} ?? '—' }}</dd>
            </div>
        @endforeach
    </section>
</div>
@endsection

@extends('ClientPortal::layouts.application')
@section('title', 'Xuất - Nhập - Tồn')
@section('content')
<div class="mx-auto max-w-6xl space-y-5 p-4">
    <a href="{{ route('client.pharma.inventory') }}" class="text-sm font-bold text-indigo-700">← Tồn kho</a>
    <h1 class="text-2xl font-black">Xuất – Nhập – Tồn</h1>
    <form method="GET" action="{{ route('client.pharma.inventory.movements') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border bg-white p-4">
        <label class="text-sm font-semibold">Từ ngày <input type="date" name="from" required value="{{ $from->toDateString() }}" class="block rounded-xl border p-2"></label>
        <label class="text-sm font-semibold">Đến ngày <input type="date" name="to" required value="{{ $to->toDateString() }}" class="block rounded-xl border p-2"></label>
        <button class="rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white">Xem báo cáo</button>
        <a href="{{ route('client.pharma.inventory.movements.export', ['from'=>$from->toDateString(),'to'=>$to->toDateString()]) }}" class="rounded-xl border border-emerald-300 px-4 py-2 font-bold text-emerald-700">↓ Xuất Excel XLSX</a>
    </form>
    @if($errors->any()) <div class="rounded-xl bg-red-50 p-3 text-red-700">{{ $errors->first() }}</div> @endif
    <div class="overflow-x-auto rounded-2xl border bg-white">
        <table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-100"><tr>@foreach(['Mã thuốc','Tên thuốc','Số lô','Hạn dùng','Tồn đầu','Nhập','Xuất','Tồn cuối'] as $h)<th class="p-3">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>@forelse($report['rows'] as $r)<tr class="border-t"><td class="p-3">{{ $r->medicine_code }}</td><td class="p-3">{{ $r->name }}</td><td class="p-3">{{ $r->batch_number }}</td><td class="p-3">{{ \Carbon\Carbon::parse($r->expiry_date)->format('d/m/Y') }}</td><td class="p-3">{{ number_format($r->period_opening, 2, ',', '.') }}</td><td class="p-3">{{ number_format($r->period_in, 2, ',', '.') }}</td><td class="p-3">{{ number_format($r->period_out, 2, ',', '.') }}</td><td class="p-3 font-bold">{{ number_format($r->period_closing, 2, ',', '.') }}</td></tr>@empty<tr><td colspan="8" class="p-6 text-center text-slate-500">Không có dữ liệu</td></tr>@endforelse</tbody>
        </table>
    </div>
</div>
@endsection
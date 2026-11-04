@extends('ClientPortal::layouts.application')
@section('title', 'Điều kiện nhà cung cấp')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Điều kiện nhà cung cấp')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <header class="rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Pharma · Nhà cung cấp</p>
        <h1 class="mt-2 text-2xl font-black">Điều kiện nhà cung cấp</h1>
        <p class="mt-2 text-sm text-slate-200">{{ $medicine->name }} · {{ $medicine->medicine_code }}</p>
    </header>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-4"><h2 class="font-extrabold">Các điều kiện đã ghi nhận</h2><p class="mt-1 text-xs text-slate-500">Chỉ tài khoản có quyền xem giá nhà cung cấp mới truy cập được.</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Nhà cung cấp</th><th class="p-3">Phạm vi</th><th class="p-3 text-right">Giá nhập</th><th class="p-3 text-right">Giá bán</th><th class="p-3">Hiệu lực</th><th class="p-3">Trạng thái</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($trackings as $tracking)
                    <tr>
                        <td class="p-3 font-bold">{{ $tracking->supplier_name ?: '—' }}</td>
                        <td class="p-3">{{ $tracking->distribution_scope ?: '—' }}</td>
                        <td class="p-3 text-right tabular-nums">{{ $tracking->import_price !== null ? number_format((float) $tracking->import_price, 0, ',', '.') : '—' }}</td>
                        <td class="p-3 text-right tabular-nums">{{ $tracking->selling_price !== null ? number_format((float) $tracking->selling_price, 0, ',', '.') : '—' }}</td>
                        <td class="p-3">{{ $tracking->start_date?->format('d/m/Y') ?? '—' }} – {{ $tracking->end_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-3">{{ $tracking->status ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-500">Chưa có điều kiện nhà cung cấp cho thuốc này.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $trackings->links() }}</div>
    </section>
</div>
@endsection

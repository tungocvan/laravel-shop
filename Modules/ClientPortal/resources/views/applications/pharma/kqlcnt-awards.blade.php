@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="min-w-0 space-y-4">
    <section class="rounded-3xl bg-slate-950 px-5 py-5 text-white sm:px-7">
        <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex min-h-11 items-center text-sm text-slate-200">← Pharma</a>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">{{ $presentation['eyebrow'] }}</p>
        <h1 class="mt-1 text-xl font-black sm:text-2xl">{{ $presentation['page_title'] }}</h1>
        <p class="mt-1 text-sm text-slate-300">{{ $presentation['page_description'] }}</p>
    </section>
    <form method="GET" action="{{ route('client.pharma.kqlcnt-awards.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['notify_no' => 'Mã TBMT', 'contractor' => 'Nhà thầu', 'medicine' => 'Tên thuốc'] as $field => $label)
            <label class="block text-xs font-semibold text-slate-700">{{ $label }}
                <input type="search" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900" placeholder="{{ $label }}">
            </label>
        @endforeach
        <label class="block text-xs font-semibold text-slate-700">Trạng thái
            <select name="active" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                <option value="">Tất cả</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>Đang hoạt động</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>Không hoạt động</option>
            </select>
        </label>
        <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4">
            <button type="submit" class="min-h-11 rounded-xl bg-slate-950 px-5 text-sm font-bold text-white">Lọc dữ liệu</button>
            <a href="{{ route('client.pharma.kqlcnt-awards.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold">Xóa lọc</a>
        </div>
    </form>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 p-4"><h2 class="font-bold text-slate-900">Danh mục KQLCNT đã lưu</h2><p class="text-xs text-slate-500">{{ $items->total() }} dòng thuốc</p></div>
        <div class="divide-y divide-slate-100">
            @forelse($items as $item)
                <a href="{{ route('client.pharma.kqlcnt-awards.show', $item->id) }}" class="block p-4 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-500">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-bold text-slate-900">{{ $item->medicine_name ?: 'Chưa có tên thuốc' }}</p>
                            <p class="mt-1 break-words text-xs text-slate-600">TBMT: {{ $item->notify_no }} · {{ $item->contractor_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">Mã lô: {{ $item->lot_no ?: '—' }} · Giá trúng: {{ is_numeric($item->winning_price) ? number_format((float) $item->winning_price, 0, ',', '.') : '—' }}</p>
                        </div><span aria-hidden="true" class="text-slate-400">›</span>
                    </div>
                </a>
            @empty
                <p class="p-5 text-sm text-slate-600">Chưa có dữ liệu phù hợp.</p>
            @endforelse
        </div>
    </section>
    {{ $items->links() }}
</div>
@endsection

@extends('ClientPortal::layouts.application')

@section('title', 'Cấu hình Excel danh mục thuốc')
@section('app-name', 'Pharma')
@section('app-subtitle', 'Excel Designer V4')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-mobile-navigation', true)

@push('application-head')
    @livewireStyles
@endpush

@section('content')
    <div class="mx-auto max-w-5xl space-y-3">
        <a href="{{ route('client.pharma.products') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-indigo-700">← Quay về danh mục thuốc</a>
        <form method="POST" action="{{ route('client.pharma.products.excel-config.import-json') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-3">
            @csrf
            <label class="flex-1 text-sm font-semibold">Nhập mẫu Excel từ JSON
                <input type="file" name="file" accept=".json,application/json" required class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <button type="submit" class="min-h-11 rounded-xl bg-indigo-600 px-4 font-bold text-white">Nhập mẫu JSON</button>
        </form>
        <h1 class="text-xl font-extrabold text-slate-900">Thiết kế mẫu xuất Excel</h1>
        <p class="text-sm text-slate-600">Dùng chung Excel Designer V4 với Pharma Admin. Mẫu được lưu riêng cho tài khoản ClientPortal.</p>
        @livewire('pharma.medicine.excel-configurator', ['ownerGuard' => 'web', 'openOnMount' => true])
    </div>
@endsection

@push('application-scripts')
    @livewireScripts
@endpush

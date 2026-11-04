@extends('ClientPortal::layouts.application')
@section('title', 'Hồ sơ sản phẩm')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Hồ sơ sản phẩm')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <header class="rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Pharma · HSSP</p>
        <h1 class="mt-2 text-2xl font-black">Hồ sơ sản phẩm</h1>
        <p class="mt-2 text-sm text-slate-200">{{ $medicine->name }} · {{ $medicine->medicine_code }}</p>
    </header>
    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    @if($medicine->currentProfile)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-extrabold">HSSP hiện hành · Phiên bản {{ $medicine->currentProfile->profile_version }}</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Trạng thái</dt><dd class="font-bold">{{ $medicine->currentProfile->profile_status }}</dd></div>
                <div><dt class="text-slate-500">Hiệu lực đến</dt><dd class="font-bold">{{ $medicine->currentProfile->effective_to?->format('d/m/Y') ?? 'Chưa có' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Ghi chú</dt><dd>{{ $medicine->currentProfile->notes ?: '—' }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-amber-700">Chức năng cập nhật hồ sơ và tải tài liệu đầy đủ sẽ được triển khai riêng; không sử dụng quyền Admin Pharma tại Client Portal.</p>
        </section>
    @elseif($canCreateHssp)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold">Tạo HSSP nháp</h2>
            <p class="mt-2 text-sm text-slate-600">Tạo bộ hồ sơ theo template Pharma chuẩn gồm GMP, giấy đăng ký lưu hành, hướng dẫn sử dụng, nhãn và thay đổi. Trạng thái ban đầu: chưa đầy đủ, chưa xác minh.</p>
            <form method="POST" action="{{ route('client.pharma.products.hssp.store', $medicine->id) }}" class="mt-5 grid gap-4 sm:grid-cols-2" onsubmit="return confirm('Tạo HSSP nháp cho thuốc này?');">
                @csrf
                <label class="block text-sm font-bold">Hiệu lực GMP đến <input required type="date" name="gmp_effective_to" value="{{ old('gmp_effective_to') }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="block text-sm font-bold">Hiệu lực GPLH đến <input required type="date" name="registration_effective_to" value="{{ old('registration_effective_to') }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="block text-sm font-bold sm:col-span-2">Ghi chú <textarea name="notes" rows="3" class="mt-2 block w-full rounded-xl border border-slate-300 p-3">{{ old('notes') }}</textarea></label>
                <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-5 font-bold text-white sm:col-span-2">Tạo HSSP nháp</button>
            </form>
        </section>
    @else
        <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Chưa có HSSP. Tài khoản chưa được cấp quyền tạo hồ sơ.</div>
    @endif
</div>
@endsection

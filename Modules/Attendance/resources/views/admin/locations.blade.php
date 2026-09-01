@extends('Admin::layouts.master')

@section('title', 'Địa điểm chấm công')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Địa điểm chấm công</h1><p class="mt-2 text-sm text-slate-600">Quản lý tọa độ, bán kính và ngưỡng độ chính xác GPS.</p></div>
        <a href="{{ route('admin.attendance.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Về Dashboard chấm công</a>
    </header>

    @if(session('attendance_success'))<div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('attendance_success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-slate-950">Thêm địa điểm</h2>
        <form method="POST" action="{{ route('admin.attendance.locations.store') }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">@csrf
            <input name="name" value="{{ old('name') }}" required placeholder="Tên địa điểm" class="rounded-xl border-slate-300">
            <input name="code" value="{{ old('code') }}" required placeholder="Mã" class="rounded-xl border-slate-300">
            <input name="latitude" value="{{ old('latitude') }}" required placeholder="Latitude" class="rounded-xl border-slate-300">
            <input name="longitude" value="{{ old('longitude') }}" required placeholder="Longitude" class="rounded-xl border-slate-300">
            <input name="radius_meters" type="number" value="{{ old('radius_meters', 150) }}" required placeholder="Bán kính (m)" class="rounded-xl border-slate-300">
            <input name="maximum_accuracy_meters" type="number" value="{{ old('maximum_accuracy_meters', 100) }}" required placeholder="Accuracy tối đa (m)" class="rounded-xl border-slate-300">
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" checked> Hoạt động</label><label class="text-sm"><input type="checkbox" name="check_in_enabled" value="1" checked> Cho check-in</label><label class="text-sm"><input type="checkbox" name="check_out_enabled" value="1" checked> Cho check-out</label>
            <button class="md:col-span-2 xl:col-span-3 min-h-11 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tạo địa điểm</button>
        </form>
    </section>

    <section class="space-y-4">
        @forelse($locations as $location)
        <form method="POST" action="{{ route('admin.attendance.locations.update', $location) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">@csrf @method('PUT')
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <input name="name" value="{{ $location->name }}" required class="rounded-xl border-slate-300"><input name="code" value="{{ $location->code }}" required class="rounded-xl border-slate-300">
                <input name="latitude" value="{{ $location->latitude }}" required class="rounded-xl border-slate-300"><input name="longitude" value="{{ $location->longitude }}" required class="rounded-xl border-slate-300">
                <input name="radius_meters" type="number" value="{{ $location->radius_meters }}" required class="rounded-xl border-slate-300"><input name="maximum_accuracy_meters" type="number" value="{{ $location->maximum_accuracy_meters }}" required class="rounded-xl border-slate-300">
                <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($location->is_active)> Hoạt động</label><label class="text-sm"><input type="checkbox" name="check_in_enabled" value="1" @checked($location->check_in_enabled)> Check-in</label><label class="text-sm"><input type="checkbox" name="check_out_enabled" value="1" @checked($location->check_out_enabled)> Check-out</label>
                <button class="md:col-span-2 xl:col-span-3 min-h-11 rounded-xl border border-indigo-300 px-4 py-2 text-sm font-semibold text-indigo-700">Lưu thay đổi</button>
            </div>
        </form>
        @empty<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Chưa có địa điểm.</div>@endforelse
        {{ $locations->links() }}
    </section>
</div>
@endsection

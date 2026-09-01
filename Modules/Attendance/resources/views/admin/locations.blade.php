@extends('Admin::layouts.master')

@section('title', 'Địa điểm chấm công')

@section('content')
@php
    $inputClass = 'mt-2 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Địa điểm chấm công</h1>
            <p class="mt-2 text-sm text-slate-600">Quản lý tọa độ, vùng geofence và ngưỡng độ chính xác GPS cho check-in/check-out.</p>
        </div>
        <a href="{{ route('admin.attendance.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Về Dashboard chấm công</a>
    </header>

    @if(session('attendance_success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('attendance_success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="border-b border-slate-100 pb-4">
            <h2 class="font-bold text-slate-950">Thêm địa điểm</h2>
            <p class="mt-1 text-sm text-slate-500">Khai báo một địa điểm được phép chấm công. Tọa độ chỉ dùng để kiểm tra geofence.</p>
        </div>
        <form method="POST" action="{{ route('admin.attendance.locations.store') }}" class="mt-6 space-y-6">@csrf
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Thông tin địa điểm</h3>
                <div class="mt-4 grid gap-5 lg:grid-cols-2">
                    <label class="{{ $labelClass }}">Tên địa điểm<input name="name" value="{{ old('name') }}" required placeholder="Ví dụ: Văn phòng chính" class="{{ $inputClass }}"></label>
                    <label class="{{ $labelClass }}">Mã địa điểm<input name="code" value="{{ old('code') }}" required placeholder="Ví dụ: HQ" class="{{ $inputClass }}"></label>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Geofence & GPS</h3>
                <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <label class="{{ $labelClass }}">Latitude<input name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude') }}" required placeholder="10.7488963" class="{{ $inputClass }}"><span class="mt-1.5 block text-xs font-normal text-slate-500">Giá trị từ -90 đến 90.</span></label>
                    <label class="{{ $labelClass }}">Longitude<input name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude') }}" required placeholder="106.6458740" class="{{ $inputClass }}"><span class="mt-1.5 block text-xs font-normal text-slate-500">Giá trị từ -180 đến 180.</span></label>
                    <label class="{{ $labelClass }}">Bán kính cho phép (m)<input name="radius_meters" type="number" min="1" value="{{ old('radius_meters', 150) }}" required class="{{ $inputClass }}"><span class="mt-1.5 block text-xs font-normal text-slate-500">Khoảng cách tối đa tới địa điểm.</span></label>
                    <label class="{{ $labelClass }}">Độ chính xác GPS tối đa (m)<input name="maximum_accuracy_meters" type="number" min="1" value="{{ old('maximum_accuracy_meters', 100) }}" required class="{{ $inputClass }}"><span class="mt-1.5 block text-xs font-normal text-slate-500">Số càng nhỏ yêu cầu GPS càng chính xác.</span></label>
                </div>
            </div>
            <fieldset class="border-t border-slate-100 pt-6">
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">Trạng thái</legend>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Hoạt động</label>
                    <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="check_in_enabled" value="1" checked class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Cho phép check-in</label>
                    <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="check_out_enabled" value="1" checked class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Cho phép check-out</label>
                </div>
            </fieldset>
            <div class="flex justify-end border-t border-slate-100 pt-5"><button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto">Tạo địa điểm</button></div>
        </form>
    </section>

    <section class="space-y-4">
        <div><h2 class="text-lg font-bold text-slate-950">Địa điểm hiện có</h2><p class="mt-1 text-sm text-slate-500">Chỉnh sửa cấu hình mà không làm thay đổi lịch sử chấm công đã lưu.</p></div>
        @forelse($locations as $location)
        <form method="POST" action="{{ route('admin.attendance.locations.update', $location) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">@csrf @method('PUT')
            <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-bold text-slate-950">{{ $location->name }}</h3><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $location->code }}</p></div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $location->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $location->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span>
            </div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <label class="{{ $labelClass }}">Tên địa điểm<input name="name" value="{{ $location->name }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Mã địa điểm<input name="code" value="{{ $location->code }}" required class="{{ $inputClass }}"></label>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="{{ $labelClass }}">Latitude<input name="latitude" type="number" step="any" min="-90" max="90" value="{{ $location->latitude }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Longitude<input name="longitude" type="number" step="any" min="-180" max="180" value="{{ $location->longitude }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Bán kính (m)<input name="radius_meters" type="number" min="1" value="{{ $location->radius_meters }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Accuracy tối đa (m)<input name="maximum_accuracy_meters" type="number" min="1" value="{{ $location->maximum_accuracy_meters }}" required class="{{ $inputClass }}"></label>
            </div>
            <div class="mt-6 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" @checked($location->is_active) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Hoạt động</label>
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="check_in_enabled" value="1" @checked($location->check_in_enabled) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Cho phép check-in</label>
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="check_out_enabled" value="1" @checked($location->check_out_enabled) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Cho phép check-out</label>
            </div>
            <div class="mt-5 flex justify-end"><button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-indigo-300 bg-white px-6 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 sm:w-auto">Lưu thay đổi</button></div>
        </form>
        @empty<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Chưa có địa điểm.</div>@endforelse
        {{ $locations->links() }}
    </section>
</div>
@endsection

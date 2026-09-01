@extends('Admin::layouts.master')

@section('title', 'Ca làm việc')

@section('content')
@php
    $inputClass = 'mt-2 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Ca làm việc</h1>
            <p class="mt-2 text-sm text-slate-600">Quản lý khung giờ làm việc và thời gian ân hạn khi đi trễ hoặc về sớm.</p>
        </div>
        <a href="{{ route('admin.attendance.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Về Dashboard chấm công</a>
    </header>

    @if(session('attendance_success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('attendance_success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="border-b border-slate-100 pb-4"><h2 class="font-bold text-slate-950">Thêm ca làm việc</h2><p class="mt-1 text-sm text-slate-500">Tạo khung giờ mới. Thiết kế dữ liệu vẫn hỗ trợ ca qua đêm.</p></div>
        <form method="POST" action="{{ route('admin.attendance.shifts.store') }}" class="mt-6 space-y-6">@csrf
            <div class="grid gap-5 lg:grid-cols-2">
                <label class="{{ $labelClass }}">Tên ca<input name="name" value="{{ old('name') }}" required placeholder="Ví dụ: Ca hành chính" class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Mã ca<input name="code" value="{{ old('code') }}" required placeholder="Ví dụ: DAY" class="{{ $inputClass }}"></label>
            </div>
            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Khung giờ & ân hạn</h3>
                <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <label class="{{ $labelClass }}">Bắt đầu ca<input name="start_time" type="time" value="{{ old('start_time', '08:00') }}" required class="{{ $inputClass }}"></label>
                    <label class="{{ $labelClass }}">Kết thúc ca<input name="end_time" type="time" value="{{ old('end_time', '17:00') }}" required class="{{ $inputClass }}"></label>
                    <label class="{{ $labelClass }}">Ân hạn đi trễ (phút)<input name="late_grace_minutes" type="number" min="0" value="{{ old('late_grace_minutes', 5) }}" required class="{{ $inputClass }}"><span class="mt-1.5 block text-xs font-normal text-slate-500">Trong khoảng này chưa tính là đi trễ.</span></label>
                    <label class="{{ $labelClass }}">Ân hạn về sớm (phút)<input name="early_leave_grace_minutes" type="number" min="0" value="{{ old('early_leave_grace_minutes', 5) }}" required class="{{ $inputClass }}"><span class="mt-1.5 block text-xs font-normal text-slate-500">Trong khoảng này chưa tính là về sớm.</span></label>
                </div>
            </div>
            <fieldset class="border-t border-slate-100 pt-6">
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">Trạng thái</legend>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Hoạt động</label>
                    <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Đặt làm ca mặc định</label>
                </div>
            </fieldset>
            <div class="flex justify-end border-t border-slate-100 pt-5"><button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto">Tạo ca</button></div>
        </form>
    </section>

    <section class="space-y-4">
        <div><h2 class="text-lg font-bold text-slate-950">Ca làm việc hiện có</h2><p class="mt-1 text-sm text-slate-500">Thay đổi cấu hình hiện tại không làm mất snapshot ca trên bản ghi lịch sử.</p></div>
        @forelse($shifts as $shift)
        <form method="POST" action="{{ route('admin.attendance.shifts.update', $shift) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">@csrf @method('PUT')
            <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-bold text-slate-950">{{ $shift->name }}</h3><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $shift->code }}</p></div>
                <div class="flex flex-wrap gap-2">@if($shift->is_default)<span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">Ca mặc định</span>@endif<span class="rounded-full px-3 py-1 text-xs font-semibold {{ $shift->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $shift->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span></div>
            </div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <label class="{{ $labelClass }}">Tên ca<input name="name" value="{{ $shift->name }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Mã ca<input name="code" value="{{ $shift->code }}" required class="{{ $inputClass }}"></label>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="{{ $labelClass }}">Bắt đầu ca<input name="start_time" type="time" value="{{ substr($shift->start_time, 0, 5) }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Kết thúc ca<input name="end_time" type="time" value="{{ substr($shift->end_time, 0, 5) }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Ân hạn đi trễ (phút)<input name="late_grace_minutes" type="number" min="0" value="{{ $shift->late_grace_minutes }}" required class="{{ $inputClass }}"></label>
                <label class="{{ $labelClass }}">Ân hạn về sớm (phút)<input name="early_leave_grace_minutes" type="number" min="0" value="{{ $shift->early_leave_grace_minutes }}" required class="{{ $inputClass }}"></label>
            </div>
            <div class="mt-6 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2">
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" @checked($shift->is_active) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Hoạt động</label>
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="checkbox" name="is_default" value="1" @checked($shift->is_default) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Ca mặc định</label>
            </div>
            <div class="mt-5 flex justify-end"><button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-indigo-300 bg-white px-6 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 sm:w-auto">Lưu thay đổi</button></div>
        </form>
        @empty<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Chưa có ca làm việc.</div>@endforelse
        {{ $shifts->links() }}
    </section>
</div>
@endsection

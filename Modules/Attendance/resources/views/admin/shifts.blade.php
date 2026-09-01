@extends('Admin::layouts.master')

@section('title', 'Ca làm việc')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Ca làm việc</h1><p class="mt-2 text-sm text-slate-600">Quản lý giờ ca và grace đi trễ/về sớm.</p></div>
        <a href="{{ route('admin.attendance.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Về Dashboard chấm công</a>
    </header>

    @if(session('attendance_success'))<div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('attendance_success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-slate-950">Thêm ca</h2>
        <form method="POST" action="{{ route('admin.attendance.shifts.store') }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">@csrf
            <input name="name" required placeholder="Tên ca" class="rounded-xl border-slate-300"><input name="code" required placeholder="Mã ca" class="rounded-xl border-slate-300">
            <input name="start_time" type="time" value="08:00" required class="rounded-xl border-slate-300"><input name="end_time" type="time" value="17:00" required class="rounded-xl border-slate-300">
            <input name="late_grace_minutes" type="number" value="5" required class="rounded-xl border-slate-300"><input name="early_leave_grace_minutes" type="number" value="5" required class="rounded-xl border-slate-300">
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" checked> Hoạt động</label><label class="text-sm"><input type="checkbox" name="is_default" value="1"> Ca mặc định</label>
            <button class="md:col-span-2 xl:col-span-3 min-h-11 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tạo ca</button>
        </form>
    </section>

    <section class="space-y-4">
        @forelse($shifts as $shift)
        <form method="POST" action="{{ route('admin.attendance.shifts.update', $shift) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">@csrf @method('PUT')
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <input name="name" value="{{ $shift->name }}" required class="rounded-xl border-slate-300"><input name="code" value="{{ $shift->code }}" required class="rounded-xl border-slate-300">
                <input name="start_time" type="time" value="{{ substr($shift->start_time, 0, 5) }}" required class="rounded-xl border-slate-300"><input name="end_time" type="time" value="{{ substr($shift->end_time, 0, 5) }}" required class="rounded-xl border-slate-300">
                <input name="late_grace_minutes" type="number" value="{{ $shift->late_grace_minutes }}" required class="rounded-xl border-slate-300"><input name="early_leave_grace_minutes" type="number" value="{{ $shift->early_leave_grace_minutes }}" required class="rounded-xl border-slate-300">
                <label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($shift->is_active)> Hoạt động</label><label class="text-sm"><input type="checkbox" name="is_default" value="1" @checked($shift->is_default)> Ca mặc định</label>
                <button class="md:col-span-2 xl:col-span-3 min-h-11 rounded-xl border border-indigo-300 px-4 py-2 text-sm font-semibold text-indigo-700">Lưu thay đổi</button>
            </div>
        </form>
        @empty<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Chưa có ca làm việc.</div>@endforelse
        {{ $shifts->links() }}
    </section>
</div>
@endsection

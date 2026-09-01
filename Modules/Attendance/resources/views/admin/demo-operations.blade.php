@extends('Admin::layouts.master')

@section('title', 'Attendance Demo Operations')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance · Local/Testing</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Demo Operations</h1><p class="mt-2 text-sm text-slate-600">Tạo hoặc xóa dữ liệu demo Attendance mà không xóa User hay EmployeeProfile.</p></div>
        <a href="{{ route('admin.attendance.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Về Dashboard chấm công</a>
    </header>

    @if(session('attendance_success'))<div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('attendance_success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Bản ghi demo</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $demoRecords }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Yêu cầu điều chỉnh demo</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $demoAdjustments }}</p></div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-slate-950">Tạo dữ liệu demo</h2><p class="mt-2 text-sm text-slate-600">Seeder là idempotent và không ghi đè tọa độ DEMO-HQ nếu location đã tồn tại.</p>
        <form method="POST" action="{{ route('admin.attendance.demo.seed') }}" class="mt-4">@csrf<button class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Seed Attendance demo</button></form>
    </section>

    <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
        <h2 class="font-bold text-rose-950">Xóa dữ liệu demo</h2><p class="mt-2 text-sm text-rose-800">Chỉ xóa Attendance records có session key demo và adjustment/audit liên quan. Không xóa User, EmployeeProfile, shift hay location.</p>
        <form method="POST" action="{{ route('admin.attendance.demo.reset') }}" class="mt-4" onsubmit="return confirm('Xóa dữ liệu Attendance demo? User và EmployeeProfile sẽ được giữ nguyên.');">@csrf @method('DELETE')<button class="min-h-11 rounded-xl bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white">Reset dữ liệu demo</button></form>
    </section>
</div>
@endsection

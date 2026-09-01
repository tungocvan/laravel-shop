@extends('Admin::layouts.master')

@section('title', 'Dashboard chấm công')

@section('content')
    @php
        $cards = [
            ['label' => 'Đang trong ca', 'value' => $dashboard['checked_in'], 'meta' => 'Đã check-in, chưa check-out', 'class' => 'text-sky-700'],
            ['label' => 'Hoàn tất', 'value' => $dashboard['completed'], 'meta' => 'Đã check-out hôm nay', 'class' => 'text-emerald-700'],
            ['label' => 'Đi trễ', 'value' => $dashboard['late'], 'meta' => 'Có late_minutes > 0', 'class' => 'text-amber-700'],
            ['label' => 'Về sớm', 'value' => $dashboard['early_leave'], 'meta' => 'Có early_leave_minutes > 0', 'class' => 'text-orange-700'],
            ['label' => 'Thiếu check-out', 'value' => $dashboard['missing_checkout'], 'meta' => 'Cần rà soát phiên mở', 'class' => 'text-red-700'],
            ['label' => 'Chờ điều chỉnh', 'value' => $dashboard['pending_adjustments'], 'meta' => 'Yêu cầu đang pending', 'class' => 'text-indigo-700'],
        ];
        $statusLabels = ['checked_in' => 'Đã vào ca', 'completed' => 'Hoàn tất', 'voided' => 'Đã vô hiệu'];
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard chấm công</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi tình trạng chấm công trong ngày và mở workspace quản trị bản ghi.</p>
                <p class="mt-2 text-xs text-slate-500">Ngày nghiệp vụ: {{ \Illuminate\Support\Carbon::parse($dashboard['date'])->format('d/m/Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Về Admin Dashboard</a>
                <a href="{{ route('admin.attendance.records') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Mở danh sách chấm công</a>
            </div>
        </header>

        <section aria-label="Chỉ số chấm công hôm nay" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($cards as $card)
                <a href="{{ route('admin.attendance.records') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold {{ $card['class'] }}">{{ number_format($card['value']) }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                </a>
            @endforeach
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="xl:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
                    <div>
                        <h2 class="font-bold text-slate-950">Hoạt động gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">5 bản ghi cập nhật gần nhất.</p>
                    </div>
                    <a href="{{ route('admin.attendance.records') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-800">Xem tất cả</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($dashboard['recent'] as $record)
                        <div class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $record->user?->name ?? $record->employeeProfile?->employee_code ?? 'Nhân viên' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $record->work_date?->format('d/m/Y') }} · {{ $record->shift_name_snapshot }}</p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $statusLabels[$record->status->value] ?? $record->status->value }}</span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">Chưa có dữ liệu chấm công.</div>
                    @endforelse
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-950">Không gian quản trị</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">MR-5 tập trung dashboard và records. Cấu hình ca/vị trí dùng domain service đã có và sẽ được nối thành màn hình riêng khi cần.</p>
                <div class="mt-5 space-y-3">
                    <a href="{{ route('admin.attendance.records') }}" class="block rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm font-semibold text-indigo-800">Records, điều chỉnh và void</a>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Export được dành cho MR-6.</div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">ClientPortal/PWA được dành cho MR-7.</div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@extends('Admin::layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">Database Workspace</h1>
                    <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">MODULE FIRST</span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Chọn Module để Backup / Restore an toàn; chi tiết bảng chỉ hiển thị khi cần.</p>
            </div>
            @include('System::partials.dashboard-return-link')
        </div>

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 px-5 py-4 text-sm text-indigo-900">
            <p class="font-semibold">Một workspace duy nhất cho Database Manager.</p>
            <p class="mt-1 text-indigo-700">Backup/Restore theo Module là luồng chính. Full Database và thao tác từng bảng được giữ riêng để tránh nhầm phạm vi.</p>
        </div>

        @livewire('system.database.table-list')
    </div>
@endsection

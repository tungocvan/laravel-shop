@extends('Admin::layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Database Manager</h1>
                <p class="text-sm text-gray-500">Quản lý, sao lưu và phục hồi dữ liệu hệ thống</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @include('System::partials.dashboard-return-link')
                <a href="{{ route('admin.system.database.backup-restore') }}"
                   class="inline-flex min-h-11 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Backup / Restore
                </a>
            </div>
        </div>

        @livewire('system.database.table-list')
    </div>
@endsection

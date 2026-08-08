@extends('Admin::layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Database Manager</h1>
                <p class="text-sm text-gray-500">Quản lý, sao lưu và phục hồi dữ liệu hệ thống</p>
            </div>
            <a href="{{ route('admin.system.database.backup-restore') }}"
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Backup / Restore
            </a>
        </div>

        @livewire('system.database.table-list')
    </div>
@endsection

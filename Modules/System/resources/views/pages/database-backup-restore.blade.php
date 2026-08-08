@extends('Admin::layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Backup / Restore Database</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý các file sao lưu SQL đã tạo.</p>
                <code class="mt-2 inline-block rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">{{ route('admin.system.database.backup-restore', absolute: false) }}</code>
            </div>

            <a href="{{ route('admin.system.database.index') }}"
               class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Quản lý Database
            </a>
        </div>

        @livewire('system.database.backup-manager')
    </div>
@endsection

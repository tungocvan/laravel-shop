@extends('Admin::layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Database Manager</h1>
                <p class="text-sm text-gray-500">Quản lý, sao lưu và phục hồi dữ liệu hệ thống</p>
            </div>

        </div>

        @livewire('admin.database.table-list')
    </div>
@endsection

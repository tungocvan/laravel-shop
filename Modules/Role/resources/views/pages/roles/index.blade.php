@extends('Admin::layouts.master')

@section('title', 'Quản lý Phân quyền (Roles)')

@section('content')
    <div class="space-y-6">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8">
            @livewire('shared.import-export.panel', [
                'serviceClass' => \Modules\Role\Services\ImportExport::class,
                'title' => 'Import / Export Vai trò',
                'description' => 'Import vai trò từ Excel hoặc export danh sách vai trò và quyền hiện tại.',
            ])
        </div>

        @livewire('role.role-table')
    </div>
@endsection

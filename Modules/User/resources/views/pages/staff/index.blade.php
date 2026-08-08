@extends('Admin::layouts.master')
@section('title', 'Quản lý Nhân sự')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\User\Services\ImportExport::class,
            'title' => 'Import / Export Nhân sự',
            'description' => 'Import nhân sự từ Excel theo email hoặc export danh sách nhân sự hiện tại.',
        ])

        @livewire('user.user-table')
    </div>
@endsection

@extends('Admin::layouts.master')

@section('title', 'Quản lý Danh mục')

@section('content')
    <div class="space-y-6">
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Category\Services\ImportExport::class,
            'title' => 'Import / Export Category',
            'description' => 'Import danh muc bang file Excel/CSV hoac export danh muc hien tai.',
        ])

        @livewire('category.categories.category-table')
    </div>
@endsection

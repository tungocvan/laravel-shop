@extends('Admin::layouts.master')

@section('title', 'Tra cứu hồ sơ mời thầu')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tra cứu hồ sơ mời thầu</h1>
            <p class="mt-1 text-sm text-gray-500">Tìm thông báo mời thầu theo từ khóa, khoảng ngày và xuất các dòng đã chọn.</p>
        </div>

        @livewire('muasamcong.search-hsmt')
    </div>
@endsection

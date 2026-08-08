{{-- 2. VIEW_CONTAINER --}}
{{-- Giả sử bạn có layout master, nếu chưa có thì dùng layout mặc định hoặc HTML thường --}}
@extends('Admin::layouts.master')

@section('title', $title)

@section('content')
    <div class="container-fluid">
        {{-- Nhúng Livewire Component tại đây --}}
        {{-- Lưu ý: Tên này dựa trên namespace Modules\Admin\Livewire\Home\HomeSettings --}}

        @livewire('admin.home.home-settings')

    </div>
@endsection
{{-- End 2. --}}

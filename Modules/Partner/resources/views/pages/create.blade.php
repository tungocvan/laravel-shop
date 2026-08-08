@extends('Admin::layouts.master')

@section('title', 'Thêm đối tác')

@section('content')
    <div class="container-fluid">
        {{-- Gọi Livewire Component hiển thị danh sách thuốc --}}
        @livewire('partner.partner.form')
    </div>
@endsection

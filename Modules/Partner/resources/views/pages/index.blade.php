@extends('Admin::layouts.master')

@section('title', 'Quản lý đối tác')

@section('content')
    <div class="container-fluid">
        {{-- Gọi Livewire Component hiển thị danh sách thuốc --}}
        @livewire('partner.partner.index')
    </div>
@endsection

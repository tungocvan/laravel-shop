@extends('Admin::layouts.master')

@section('title', 'Pharma')

@section('content')
    <div class="container-fluid">
        {{-- Gọi Livewire Component hiển thị danh sách thuốc --}}
        @livewire('pharma.supplier-trackings.form')
    </div>
@endsection
 
@extends('Admin::layouts.master')
@section('title', 'Thêm thủ tục hành chính')
@section('content')
<div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    @livewire('administrative.procedures.procedure-form')
</div>
@endsection

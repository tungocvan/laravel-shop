@extends('Admin::layouts.master')
@section('css')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endsection
@section('content')
    @livewire('admin.menus.menu-table')
@endsection

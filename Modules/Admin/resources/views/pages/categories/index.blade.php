@extends('Admin::layouts.master')

@section('title', 'Quản lý Danh mục')

@section('content')
    @livewire('admin.categories.category-table')
@endsection

@extends('Admin::layouts.master')
@section('content')
    @livewire('admin.categories.category-form', ['id' => $id])
@endsection

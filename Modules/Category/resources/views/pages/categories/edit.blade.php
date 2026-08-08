@extends('Admin::layouts.master')
@section('content')
    @livewire('category.categories.category-form', ['id' => $id])
@endsection

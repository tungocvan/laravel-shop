@extends('Admin::layouts.master')
@section('content')
    @livewire('admin.products.product-form', ['id' => $id])
@endsection

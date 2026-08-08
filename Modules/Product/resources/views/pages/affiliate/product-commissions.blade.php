@extends('Admin::layouts.master')

@section('content')
    @livewire('product.products.product-form', ['id' => $productId])
@endsection

@extends('Website::layouts.frontend')

@section('content')
    @livewire('website.products.product-detail', ['slug' => $slug])
@endsection


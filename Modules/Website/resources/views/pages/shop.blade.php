@extends('Website::layouts.frontend')

@section('title', 'Sản phẩm')

@section('content')
    @if(app(\Modules\Website\Services\WebsiteTemplateService::class)->isPharma())
        @livewire('website.products.pharma-catalog')
    @else
        @livewire('website.products.product-list')
    @endif
@endsection

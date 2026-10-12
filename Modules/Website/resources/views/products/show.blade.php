@extends('Website::layouts.frontend')

@section('content')
    @if(app(\Modules\Website\Services\WebsiteTemplateService::class)->isPharma())
        @include('Website::products.pharma-show')
    @else
        @livewire('website.products.product-detail', ['slug' => $slug])
    @endif
@endsection

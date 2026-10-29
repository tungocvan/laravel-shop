@extends('demo::projects.coffee-shop-sales.layouts.app')

@section('content')
<div class="app">
    @include('demo::projects.coffee-shop-sales.partials.sidebar')
    @include('demo::projects.coffee-shop-sales.partials.overlay')
    @include('demo::projects.coffee-shop-sales.pages.workspace')
</div>
<div id="printArea"></div>
@endsection

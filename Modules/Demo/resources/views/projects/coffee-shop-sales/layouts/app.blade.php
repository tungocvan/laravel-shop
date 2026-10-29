@extends('demo::layouts.app')

@section('demo-title', 'Quản lý bán hàng quán cà phê - trừ kho theo định lượng')

@push('demo-styles')
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-ban-hang-ca-phe/css/app.css') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/coffee-shop-sales/css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-ban-hang-ca-phe/shared/menu-editor.css') }}">
@endpush

@push('demo-data')
<script id="du-lieu" type="application/json">null</script>
@endpush

@push('demo-scripts')
<script src="{{ asset('demo-assets/quan-ly-ban-hang-ca-phe/js/app.js') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/coffee-shop-sales/js/app.js')) }}" defer></script>
<script src="{{ asset('demo-assets/quan-ly-ban-hang-ca-phe/js/integrations.js') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/coffee-shop-sales/js/integrations.js')) }}" defer></script>
<div data-demo-menu="quan-ly-ban-hang-ca-phe" hidden></div>
<script src="{{ asset('demo-assets/quan-ly-ban-hang-ca-phe/shared/menu-editor.js') }}" defer></script>
@endpush

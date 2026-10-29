@extends('demo::layouts.app')

@section('demo-title', 'Quản lý thiết bị và bảo trì')

@push('demo-styles')
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-thiet-bi-bao-tri/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-thiet-bi-bao-tri/shared/menu-editor.css') }}">
@endpush

@push('demo-scripts')
<script src="{{ asset('demo-assets/quan-ly-thiet-bi-bao-tri/js/app.js') }}"></script>
<script src="{{ asset('demo-assets/quan-ly-thiet-bi-bao-tri/js/integrations.js') }}"></script>
<div data-demo-menu="quan-ly-thiet-bi-bao-tri" hidden></div>
<script defer src="{{ asset('demo-assets/quan-ly-thiet-bi-bao-tri/shared/menu-editor.js') }}"></script>
@endpush

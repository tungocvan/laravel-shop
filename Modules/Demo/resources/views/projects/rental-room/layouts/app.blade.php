@extends('demo::layouts.app')
@section('demo-title', 'Quản Lý Phòng Trọ')
@push('demo-styles')
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-phong-tro/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-phong-tro/shared/menu-editor.css') }}">
@endpush
@push('demo-data')
<script id="du-lieu" type="application/json">null</script>
@endpush
@push('demo-scripts')
<script src="{{ asset('demo-assets/quan-ly-phong-tro/js/app.js') }}"></script>
<script src="{{ asset('demo-assets/quan-ly-phong-tro/js/integrations.js') }}"></script>
<div data-demo-theme="rental-room" data-theme-storage-key="rental_room_theme_v1" data-theme-target="page-settings"></div>
<script src="{{ asset('demo-assets/quan-ly-phong-tro/shared/theme-selector.js') }}"></script>
<div data-demo-menu="quan-ly-phong-tro" data-menu-storage-key="rental_room_sidebar_v1"></div>
<script src="{{ asset('demo-assets/quan-ly-phong-tro/shared/menu-editor.js') }}"></script>
@endpush

@extends('demo::layouts.app')
@section('demo-title', 'Quản lý đồng phục nhân viên khách sạn')
@push('demo-styles')
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-dong-phuc-khach-san/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-dong-phuc-khach-san/shared/menu-editor.css') }}">
@endpush
@push('demo-scripts')
<script src="{{ asset('demo-assets/quan-ly-dong-phuc-khach-san/js/app.js') }}"></script>
<script src="{{ asset('demo-assets/quan-ly-dong-phuc-khach-san/js/integrations.js') }}"></script>
<div data-demo-menu="quan-ly-dong-phuc-khach-san" data-menu-storage-key="shared_demo_sidebar_menu_v1_quan-ly-dong-phuc-khach-san"></div>
<script src="{{ asset('demo-assets/quan-ly-dong-phuc-khach-san/shared/menu-editor.js') }}"></script>
@endpush

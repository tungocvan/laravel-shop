@extends('demo::layouts.app')
@section('demo-title', 'Báo cáo công việc hằng ngày')
@push('demo-styles')
<link rel="stylesheet" href="{{ asset('demo-assets/bao-cao-cong-viec-hang-ngay/css/app.css') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/daily-work-report/css/app.css')) }}">
@endpush
@push('demo-scripts')
<script src="{{ asset('demo-assets/bao-cao-cong-viec-hang-ngay/js/app.js') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/daily-work-report/js/app.js')) }}" defer></script>
<x-demo.sidebar-menu-builder slug="bao-cao-cong-viec-hang-ngay" />
@endpush

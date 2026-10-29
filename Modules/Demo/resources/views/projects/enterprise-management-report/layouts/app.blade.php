@extends('demo::layouts.app')

@section('demo-title', 'Báo cáo quản trị tình hình doanh nghiệp')

@push('demo-styles')
<link rel="stylesheet" href="{{ asset('demo-assets/bao-cao-quan-tri-doanh-nghiep/css/app.css') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/css/app.css')) }}">
@endpush

@push('demo-scripts')
<script src="{{ asset('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/app.js') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/js/app.js')) }}" defer></script>
<script src="{{ asset('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/preferences.js') }}?v={{ filemtime(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/js/preferences.js')) }}" defer></script>
@endpush

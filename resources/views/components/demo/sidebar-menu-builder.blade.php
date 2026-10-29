@php
    $sharedMenuAssets = base_path('Modules/Demo/resources/assets/shared/sidebar-menu');
@endphp
<div data-demo-menu="{{ $slug }}" hidden></div>
<link rel="stylesheet" href="{{ route('demo.shared-sidebar.asset', ['asset' => 'menu-builder.css']) }}?v={{ filemtime($sharedMenuAssets.'/menu-builder.css') }}">
<script defer src="{{ route('demo.shared-sidebar.asset', ['asset' => 'menu-builder.js']) }}?v={{ filemtime($sharedMenuAssets.'/menu-builder.js') }}"></script>

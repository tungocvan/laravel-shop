{{-- Privileged trusted configuration; mutation requires website.settings.manage. --}}
{!! $headerScript !!}
{!! $analyticsCode ?? '' !!}

<x-realtime-config />

@include('Website::partials.design-tokens')
@include('Website::partials.layout.presentation-styles')
@livewireStyles
@vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
@yield('css')
@stack('styles')

{{-- Privileged trusted configuration; mutation requires website.settings.manage. --}}
{!! $headerScript !!}
{!! $analyticsCode ?? '' !!}

<script>
    window.CHAT_CONFIG_HOST = @json(config('realtime.host') ?: request()->getSchemeAndHttpHost());
</script>
<x-realtime-config />

@include('Website::partials.design-tokens')
@vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
@yield('css')
@stack('styles')

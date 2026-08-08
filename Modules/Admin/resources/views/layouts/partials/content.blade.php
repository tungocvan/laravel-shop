@php
    $containerKey = data_get($adminLayoutConfig, 'container', '7xl');
    $containerClass = [
        'full' => 'max-w-none',
        'narrow' => 'max-w-4xl',
        '7xl' => 'max-w-7xl',
        'screen-2xl' => 'max-w-screen-2xl',
    ][$containerKey] ?? 'max-w-7xl';
@endphp

<main id="admin-main" tabindex="-1" class="min-h-0 flex-1 overflow-y-auto focus:outline-none">
    <div class="mx-auto w-full {{ $containerClass }} px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
        @include('Admin::layouts.partials.flash')

        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </div>
</main>

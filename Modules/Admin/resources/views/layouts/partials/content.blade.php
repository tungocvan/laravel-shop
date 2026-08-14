<main id="admin-main" tabindex="-1" class="min-h-0 flex-1 overflow-y-auto focus:outline-none">
    <div class="w-full px-4 py-5 sm:px-5 lg:px-6 lg:py-6">
        @include('Admin::layouts.partials.flash')

        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </div>
</main>

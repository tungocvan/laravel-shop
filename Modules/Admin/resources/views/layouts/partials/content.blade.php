<main id="admin-main" tabindex="-1" class="min-h-0 flex-1 overflow-y-auto focus:outline-none">
    <div class="w-full {{ $adminShellPresentation['content_class'] }} {{ $adminShellPresentation['content_padding_class'] }}">
        @include('Admin::layouts.partials.flash')

        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </div>
</main>

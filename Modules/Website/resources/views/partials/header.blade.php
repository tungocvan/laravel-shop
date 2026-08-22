{{-- HEADER COMPONENT: $headerSettings, $mainMenu, $mobileMenu, $accountMenu, $headerLayout --}}
@include('Website::components.header.slot', ['slot' => 'desktop.topbar'])

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm"
    x-data="{ userDropdownOpen:false, mobileMenuOpen:false, mobileSearchOpen:false }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20 gap-4">
            @include('Website::components.header.slot', ['slot' => 'desktop.main.left'])
            @include('Website::components.header.slot', ['slot' => 'desktop.main.center'])
            @include('Website::components.header.slot', ['slot' => 'desktop.main.right'])
        </div>

        @include('Website::components.header.slot', ['slot' => 'mobile.search'])
    </div>

    @include('Website::components.header.slot', ['slot' => 'mobile.drawer'])
</header>

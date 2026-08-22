{{-- HEADER COMPONENT: $headerSettings, $mainMenu, $mobileMenu, $accountMenu --}}
@include('Website::components.header.topbar')

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm"
    x-data="{ userDropdownOpen:false, mobileMenuOpen:false, mobileSearchOpen:false }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20 gap-4">
            @include('Website::components.header.brand')
            @include('Website::components.header.search', ['mode' => 'desktop'])
            @include('Website::components.header.actions')
        </div>

        @include('Website::components.header.search', ['mode' => 'mobile'])
    </div>

    @include('Website::components.header.mobile-menu')
</header>

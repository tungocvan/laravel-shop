{{--
    HEADER COMPONENT
    Data Injected by: Modules/Website/Providers/WebsiteServiceProvider.php (View::composer)
    Variables: $headerSettings, $mainMenu, $mobileMenu
--}}

{{-- 1. TOP BAR (Desktop Only) --}}
<div class="bg-gray-900 text-white text-xs py-2 hidden md:block">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            {{-- Hotline --}}
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                {{ $headerSettings['hotline'] ?? '1900 xxxx' }}
            </span>
            <span class="text-gray-600">|</span>
            {{-- Email --}}
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                {{ $headerSettings['email'] ?? 'contact@domain.com' }}
            </span>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ $headerSettings['help_url'] ?? '#' }}" class="hover:text-yellow-400 transition">Trợ giúp</a>
            <a href="{{ $headerSettings['order_tracking_url'] ?? '#' }}" class="hover:text-yellow-400 transition">Theo dõi đơn hàng</a>
        </div>
    </div>
</div>

{{-- 2. MAIN HEADER --}}
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all duration-300"
    x-data="{
        userDropdownOpen: false,
        scrolled: false,
        mobileMenuOpen: false,
        mobileSearchOpen: false
    }"
    @scroll.window="scrolled = (window.pageYOffset > 20)">

    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20 gap-4">

            {{-- LEFT SECTION: LOGO & HAMBURGER --}}
            <div class="flex items-center gap-3 md:gap-8">
                {{-- Hamburger Button (Mobile) --}}
                <button @click="mobileMenuOpen = true" class="lg:hidden p-2 -ml-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                {{-- LOGO (Dynamic Brand Name) --}}
                <a href="/" class="flex-shrink-0 flex items-center gap-2 group">
                    @if(!empty($headerSettings['logo']))
                        <img
                            src="{{ asset('storage/' . $headerSettings['logo']) }}?v={{ md5($headerSettings['logo']) }}"
                            alt="{{ $headerSettings['brand_name'] ?? 'Logo website' }}"
                            class="h-10 w-auto max-w-40 object-contain md:h-12">
                    @else
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-black text-white rounded-lg flex items-center justify-center font-black text-lg md:text-xl group-hover:bg-blue-600 transition-colors">
                            {{ substr($headerSettings['brand_name'] ?? 'F', 0, 1) }}
                        </div>
                    @endif
                    <span class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight group-hover:text-blue-600 transition-colors">
                        {{ $headerSettings['brand_name'] ?? 'FlexBiz' }}<span class="text-blue-600 group-hover:text-black">.</span>
                    </span>
                </a>
            </div>

            {{-- CENTER SECTION: SEARCH BAR (Desktop) --}}
            <div class="hidden lg:flex flex-1 max-w-xl relative">
                {{-- Giả sử route product.list đã có --}}
                <form action="{{ Route::has('product.list') ? route('product.list') : '#' }}" method="GET" class="w-full">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm, thương hiệu..." class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-5 pr-12 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all placeholder-gray-500">
                    <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 p-1.5 bg-white rounded-full text-gray-500 hover:text-blue-600 shadow-sm hover:shadow transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>

            {{-- RIGHT SECTION: NAVIGATION & ICONS --}}
            <div class="flex items-center gap-1 md:gap-6">
                {{-- Mobile Search Toggle --}}
                <button @click="mobileSearchOpen = !mobileSearchOpen" class="lg:hidden p-2 text-gray-600 hover:text-blue-600 hover:bg-gray-100 rounded-full transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>

                {{-- DESKTOP MENU (DYNAMIC RENDER) --}}
                <nav class="hidden xl:flex items-center gap-6 text-sm font-bold text-gray-700">
                    @if(isset($mainMenu) && $mainMenu->isNotEmpty())
                        @foreach($mainMenu as $item)
                            <div class="relative group">
                                <a href="{{ $item->url }}" target="{{ $item->target }}" class="hover:text-blue-600 transition flex items-center gap-1 py-4">
                                    {{ $item->title }}
                                    @if($item->children->isNotEmpty())
                                        <svg class="w-3 h-3 text-gray-400 group-hover:text-blue-600 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    @endif
                                </a>

                                {{-- Dropdown Child Menu --}}
                                @if($item->children->isNotEmpty())
                                    <div class="absolute left-0 top-full w-56 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-2 group-hover:translate-y-0 z-50">
                                        <div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden py-2 mt-1">
                                            @foreach($item->children as $child)
                                                <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-4 py-2.5 hover:bg-blue-50 hover:text-blue-600 text-sm font-medium transition text-gray-600">
                                                    {{ $child->title }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </nav>

                {{-- ICONS WRAPPER --}}
                <div class="flex items-center gap-2 md:border-l md:border-gray-200 md:pl-6">

                    {{-- Wishlist (Static for now) --}}
                    @livewire('website.wishlist.wishlist-icon')

                    {{-- Cart Icon (Livewire Component) --}}
                    @if (class_exists(\Modules\Website\Livewire\Cart\CartIcon::class))
                        @livewire('website.cart.cart-icon')
                    @else
                        {{-- Fallback Cart Icon --}}
                        <a href="#" class="p-2 text-gray-600 hover:text-blue-600 transition relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </a>
                    @endif

                    {{-- User Auth Dropdown --}}
                    <div class="hidden lg:block relative">
                        @auth
                            <button @click="userDropdownOpen = !userDropdownOpen" class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-full border border-transparent hover:border-gray-200 transition">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" class="w-8 h-8 rounded-full border border-gray-200" alt="Avatar">
                                <span class="hidden md:block text-sm font-bold text-gray-700 max-w-[100px] truncate">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            {{-- Dropdown Body --}}
                            <div x-show="userDropdownOpen" @click.outside="userDropdownOpen = false" x-transition class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-2xl py-2 ring-1 ring-black ring-opacity-5 z-50 overflow-hidden" style="display: none;">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Tài khoản</p>
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <div class="py-1">
                                    <a href="{{ Route::has('account.profile') ? route('account.profile') : '#' }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Hồ sơ cá nhân</a>
                                    <a href="{{ Route::has('account.orders') ? route('account.orders') : '#' }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Đơn hàng của tôi</a>
                                </div>
                                <div class="border-t border-gray-100 py-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Đăng xuất</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <a href="{{ route('login') }}" class="hidden md:inline-block text-sm font-bold text-gray-700 hover:text-blue-600">Đăng nhập</a>
                                <a href="{{ route('register') }}" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-full hover:bg-blue-600 hover:shadow-lg transition">Đăng ký</a>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. MOBILE SEARCH BAR (Slide Down) --}}
        <div x-show="mobileSearchOpen" x-transition class="lg:hidden pb-4" style="display: none;">
            <form action="{{ Route::has('product.list') ? route('product.list') : '#' }}" method="GET" class="relative">
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." class="w-full bg-gray-100 border-none rounded-xl py-3 pl-12 pr-4 text-gray-900 focus:ring-2 focus:ring-blue-500">
                <svg class="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </form>
        </div>
    </div>

    {{-- 4. MOBILE OFF-CANVAS MENU (Slide Over) --}}
    <template x-teleport="body">
        <div x-show="mobileMenuOpen" class="fixed inset-0 z-[9999]" style="display: none;">
            {{-- Backdrop --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false"
                 class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            {{-- Sidebar Content --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="absolute left-0 top-0 bottom-0 bg-white w-[85%] max-w-sm h-full shadow-2xl overflow-y-auto z-10 flex flex-col">

                {{-- Header Sidebar --}}
                <div class="p-6 bg-gray-900 text-white shrink-0">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-xl font-bold">Menu</span>
                        <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-white p-1">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    @auth
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" class="w-12 h-12 rounded-full border-2 border-white/20">
                            <div>
                                <p class="font-bold text-lg">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-400 truncate max-w-[180px]">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <p class="text-gray-400 text-sm mb-2">Chào mừng đến với {{ $headerSettings['brand_name'] ?? 'Store' }}!</p>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('login') }}" class="text-center py-2 bg-white/10 rounded-lg hover:bg-white/20 transition text-sm font-bold">Đăng nhập</a>
                                <a href="{{ route('register') }}" class="text-center py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition text-sm font-bold">Đăng ký</a>
                            </div>
                        </div>
                    @endauth
                </div>

                {{-- Mobile Menu Items (Dynamic) --}}
                <div class="p-4 space-y-1 overflow-y-auto grow">
                    {{-- Logic fallback: Nếu không có mobile menu riêng thì dùng main menu --}}
                    @php
                        $menuToRender = (isset($mobileMenu) && $mobileMenu->isNotEmpty()) ? $mobileMenu : $mainMenu;
                    @endphp

                    @if(isset($menuToRender) && $menuToRender->isNotEmpty())
                        @foreach($menuToRender as $item)
                            <div x-data="{ open: false }">
                                <div class="flex items-center justify-between group">
                                    <a href="{{ $item->url }}" class="block flex-1 px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl hover:text-blue-600 transition">
                                        {{ $item->title }}
                                    </a>
                                    @if($item->children->isNotEmpty())
                                        <button @click="open = !open" class="p-3 text-gray-400 hover:text-blue-600 transition-colors">
                                            <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                    @endif
                                </div>

                                {{-- Child Mobile Menu (Accordion) --}}
                                @if($item->children->isNotEmpty())
                                    <div x-show="open" x-collapse class="pl-4 space-y-1 bg-gray-50 rounded-lg mb-1" style="display: none;">
                                        @foreach($item->children as $child)
                                            <a href="{{ $child->url }}" class="block px-4 py-2.5 text-sm text-gray-600 hover:text-blue-600">
                                                - {{ $child->title }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-center text-gray-400 text-sm py-4">Chưa có menu nào.</p>
                    @endif

                    @auth
                        <div class="border-t border-gray-100 my-2 pt-2">
                            <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Cá nhân</p>
                            <a href="{{ Route::has('account.profile') ? route('account.profile') : '#' }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl hover:text-blue-600 transition">Hồ sơ cá nhân</a>
                            <a href="{{ Route::has('account.orders') ? route('account.orders') : '#' }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl hover:text-blue-600 transition">Đơn hàng của tôi</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-red-600 font-medium hover:bg-red-50 rounded-xl transition flex items-center gap-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Đăng xuất
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>

                {{-- Footer Contact Info (Dynamic) --}}
                <div class="p-6 bg-gray-50 mt-auto border-t border-gray-100 pb-10 shrink-0">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Liên hệ</p>
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <span class="text-blue-600 font-bold">Hotline:</span> {{ $headerSettings['hotline'] ?? 'N/A' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-blue-600 font-bold">Email:</span> {{ $headerSettings['email'] ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</header>

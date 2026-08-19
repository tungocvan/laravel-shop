{{-- HEADER COMPONENT: $headerSettings, $mainMenu, $mobileMenu, $accountMenu --}}
<div class="bg-gray-900 text-white text-xs py-2 hidden md:block">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $headerSettings['hotline'] ?? '') }}" class="hover:text-yellow-400">{{ $headerSettings['hotline'] ?? '1900 xxxx' }}</a>
            <span class="text-gray-600">|</span>
            <a href="mailto:{{ $headerSettings['email'] ?? '' }}" class="hover:text-yellow-400">{{ $headerSettings['email'] ?? 'contact@domain.com' }}</a>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ $headerSettings['help_url'] ?? '#' }}" class="hover:text-yellow-400">Trợ giúp</a>
            <a href="{{ $headerSettings['order_tracking_url'] ?? '#' }}" class="hover:text-yellow-400">Theo dõi đơn hàng</a>
        </div>
    </div>
</div>

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm"
    x-data="{ userDropdownOpen:false, mobileMenuOpen:false, mobileSearchOpen:false }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20 gap-4">
            <div class="flex items-center gap-3 md:gap-8">
                <button type="button" @click="mobileMenuOpen=true" aria-label="Mở menu điều hướng" class="lg:hidden p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="/" class="flex-shrink-0 flex items-center gap-2 group">
                    @if(!empty($headerSettings['logo']))
                        <img src="{{ str_starts_with($headerSettings['logo'], 'http') ? $headerSettings['logo'] : asset('storage/'.$headerSettings['logo']).'?v='.md5($headerSettings['logo']) }}" alt="{{ $headerSettings['brand_name'] ?? 'Logo website' }}" class="h-10 w-auto max-w-40 object-contain md:h-12">
                    @else
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-black text-white rounded-lg flex items-center justify-center font-black text-lg md:text-xl">{{ substr($headerSettings['brand_name'] ?? 'F',0,1) }}</div>
                    @endif
                    <span class="text-xl md:text-2xl font-bold text-gray-900">{{ $headerSettings['brand_name'] ?? 'FlexBiz' }}<span class="text-blue-600">.</span></span>
                </a>
            </div>

            <div class="hidden lg:flex flex-1 max-w-xl relative">
                <form action="{{ Route::has('product.list') ? route('product.list') : '#' }}" method="GET" class="w-full relative">
                    <label for="desktop-product-search" class="sr-only">Tìm kiếm sản phẩm</label>
                    <input id="desktop-product-search" type="search" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm, thương hiệu..." class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-5 pr-12 text-sm focus:ring-2 focus:ring-blue-500">
                    <button type="submit" aria-label="Tìm kiếm" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-blue-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></button>
                </form>
            </div>

            <div class="flex items-center gap-1 md:gap-6">
                <button type="button" @click="mobileSearchOpen=!mobileSearchOpen" aria-label="Mở tìm kiếm" class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-full"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></button>

                <nav class="hidden xl:flex items-center gap-6 text-sm font-bold text-gray-700">
                    @foreach($mainMenu ?? [] as $item)
                        <div class="relative group">
                            <a href="{{ $item->url }}" target="{{ $item->target }}" class="hover:text-blue-600 flex items-center gap-1 py-4">{{ $item->title }} @if($item->children->isNotEmpty())<span>⌄</span>@endif</a>
                            @if($item->children->isNotEmpty())
                                <div class="absolute left-0 top-full w-56 opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50 pt-1"><div class="bg-white rounded-xl shadow-xl border py-2">@foreach($item->children as $child)<a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-4 py-2.5 hover:bg-blue-50 hover:text-blue-600 text-sm font-medium text-gray-600">{{ $child->title }}</a>@endforeach</div></div>
                            @endif
                        </div>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2 md:border-l md:border-gray-200 md:pl-6">
                    @livewire('website.wishlist.wishlist-icon')
                    @if(class_exists(\Modules\Website\Livewire\Cart\CartIcon::class))
                        @livewire('website.cart.cart-icon')
                    @endif

                    <div class="hidden lg:block relative">
                        @auth
                            <button @click="userDropdownOpen=!userDropdownOpen" class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-full border border-transparent hover:border-gray-200">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" class="w-8 h-8 rounded-full border" alt="Avatar">
                                <span class="hidden md:block text-sm font-bold text-gray-700 max-w-[100px] truncate">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="userDropdownOpen" @click.outside="userDropdownOpen=false" x-transition class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 ring-1 ring-black/5 z-50 overflow-hidden" style="display:none">
                                <div class="px-4 py-3 border-b bg-gray-50"><p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Tài khoản</p><p class="text-sm font-medium truncate">{{ Auth::user()->email }}</p></div>
                                <div class="py-1">
                                    @if(isset($accountMenu) && $accountMenu->isNotEmpty())
                                        @foreach($accountMenu as $item)
                                            <a href="{{ $item->url ?: '#' }}" target="{{ $item->target }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">{{ $item->title }}</a>
                                            @foreach($item->children as $child)
                                                <a href="{{ $child->url ?: '#' }}" target="{{ $child->target }}" class="block pl-7 pr-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">{{ $child->title }}</a>
                                            @endforeach
                                        @endforeach
                                    @else
                                        <a href="{{ Route::has('client.apps.index') ? route('client.apps.index') : '/my-apps' }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Ứng dụng của tôi</a>
                                        <a href="{{ Route::has('account.profile') ? route('account.profile') : '#' }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Hồ sơ cá nhân</a>
                                        <a href="{{ Route::has('account.orders') ? route('account.orders') : '#' }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">Đơn hàng của tôi</a>
                                    @endif
                                </div>
                                <div class="border-t py-1"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Đăng xuất</button></form></div>
                            </div>
                        @else
                            <div class="flex items-center gap-3"><a href="{{ route('login') }}" class="hidden md:inline-block text-sm font-bold text-gray-700 hover:text-blue-600">Đăng nhập</a><a href="{{ route('register') }}" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-full hover:bg-blue-600">Đăng ký</a></div>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <div x-show="mobileSearchOpen" x-transition class="lg:hidden pb-4" style="display:none"><form action="{{ Route::has('product.list') ? route('product.list') : '#' }}" method="GET"><input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm..." class="w-full bg-gray-100 border-none rounded-xl py-3 px-4 focus:ring-2 focus:ring-blue-500"></form></div>
    </div>

    <template x-teleport="body">
        <div x-show="mobileMenuOpen" class="fixed inset-0 z-[9999]" style="display:none">
            <div @click="mobileMenuOpen=false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div x-show="mobileMenuOpen" x-transition class="absolute left-0 top-0 bottom-0 bg-white w-[85%] max-w-sm h-full shadow-2xl overflow-y-auto z-10 flex flex-col">
                <div class="p-6 bg-gray-900 text-white shrink-0">
                    <div class="flex justify-between items-start mb-6"><span class="text-xl font-bold">Menu</span><button type="button" @click="mobileMenuOpen=false" class="text-gray-400 hover:text-white text-2xl">×</button></div>
                    @auth<div class="flex items-center gap-3"><img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" class="w-12 h-12 rounded-full"><div><p class="font-bold">{{ Auth::user()->name }}</p><p class="text-xs text-gray-400 truncate max-w-[180px]">{{ Auth::user()->email }}</p></div></div>@else<div class="grid grid-cols-2 gap-3"><a href="{{ route('login') }}" class="text-center py-2 bg-white/10 rounded-lg text-sm font-bold">Đăng nhập</a><a href="{{ route('register') }}" class="text-center py-2 bg-blue-600 rounded-lg text-sm font-bold">Đăng ký</a></div>@endauth
                </div>
                <div class="p-4 space-y-1 grow">
                    @php($menuToRender=(isset($mobileMenu)&&$mobileMenu->isNotEmpty())?$mobileMenu:($mainMenu??collect()))
                    @foreach($menuToRender as $item)<a href="{{ $item->url }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl">{{ $item->title }}</a>@endforeach
                    @auth
                        <div class="border-t my-2 pt-2"><p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Tài khoản</p>
                            @if(isset($accountMenu) && $accountMenu->isNotEmpty())
                                @foreach($accountMenu as $item)<a href="{{ $item->url ?: '#' }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl hover:text-blue-600">{{ $item->title }}</a>@endforeach
                            @else
                                <a href="{{ Route::has('client.apps.index') ? route('client.apps.index') : '/my-apps' }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl">Ứng dụng của tôi</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full text-left px-4 py-3 text-red-600 font-medium hover:bg-red-50 rounded-xl">Đăng xuất</button></form>
                        </div>
                    @endauth
                </div>
                <div class="p-6 bg-gray-50 border-t text-sm text-gray-600"><p><strong>Hotline:</strong> {{ $headerSettings['hotline'] ?? 'N/A' }}</p><p class="mt-2"><strong>Email:</strong> {{ $headerSettings['email'] ?? 'N/A' }}</p></div>
            </div>
        </div>
    </template>
</header>

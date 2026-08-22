<div class="hidden lg:block relative">
    @auth
        <button @click="userDropdownOpen=!userDropdownOpen" class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-full border border-transparent hover:border-gray-200">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" class="w-8 h-8 rounded-full border" alt="Avatar">
            <span class="hidden md:block text-sm font-bold text-gray-700 max-w-[100px] truncate">{{ Auth::user()->name }}</span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="userDropdownOpen" @click.outside="userDropdownOpen=false" x-transition class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl py-2 ring-1 ring-black/5 z-50 overflow-hidden" style="display:none">
            <div class="px-4 py-3 border-b bg-gray-50">
                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Tài khoản</p>
                <p class="text-sm font-medium truncate">{{ Auth::user()->email }}</p>
            </div>
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
            <div class="border-t py-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Đăng xuất</button>
                </form>
            </div>
        </div>
    @else
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="hidden md:inline-block text-sm font-bold text-gray-700 hover:text-blue-600">Đăng nhập</a>
            <a href="{{ route('register') }}" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-full hover:bg-blue-600">Đăng ký</a>
        </div>
    @endauth
</div>

<template x-teleport="body">
    <div x-show="mobileMenuOpen" class="fixed inset-0 z-[9999]" style="display:none">
        <div @click="mobileMenuOpen=false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div x-show="mobileMenuOpen" x-transition class="absolute left-0 top-0 bottom-0 bg-white w-[85%] max-w-sm h-full shadow-2xl overflow-y-auto z-10 flex flex-col">
            <div class="p-6 bg-gray-900 text-white shrink-0">
                <div class="flex justify-between items-start mb-6">
                    <span class="text-xl font-bold">Menu</span>
                    <button type="button" @click="mobileMenuOpen=false" class="text-gray-400 hover:text-white text-2xl">×</button>
                </div>
                @auth
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random" class="w-12 h-12 rounded-full">
                        <div>
                            <p class="font-bold">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400 truncate max-w-[180px]">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('login') }}" data-pwa-auth-target="{{ route('client.apps.login') }}" class="text-center py-2 bg-white/10 rounded-lg text-sm font-bold">Đăng nhập</a>
                        <a href="{{ route('register') }}" data-pwa-auth-target="{{ route('client.apps.register') }}" class="text-center py-2 bg-blue-600 rounded-lg text-sm font-bold">Đăng ký</a>
                    </div>
                @endauth
            </div>

            <div class="p-4 space-y-1 grow">
                @php($menuToRender=(isset($mobileMenu)&&$mobileMenu->isNotEmpty())?$mobileMenu:($mainMenu??collect()))
                @foreach($menuToRender as $item)
                    <a href="{{ $item->url }}" target="{{ $item->target }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl">{{ $item->title }}</a>
                @endforeach

                @auth
                    @if(isset($accountMenu) && $accountMenu->isNotEmpty())
                        <div class="border-t my-2 pt-2">
                            <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Tài khoản</p>
                            @foreach($accountMenu as $item)
                                <a href="{{ $item->url ?: '#' }}" target="{{ $item->target }}" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-50 rounded-xl hover:text-blue-600">{{ $item->title }}</a>
                            @endforeach
                        </div>
                    @endif
                    <div class="border-t my-2 pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 text-red-600 font-medium hover:bg-red-50 rounded-xl">Đăng xuất</button>
                        </form>
                    </div>
                @endauth
            </div>

            <div class="p-6 bg-gray-50 border-t text-sm text-gray-600">
                <p><strong>Hotline:</strong> {{ $headerSettings['hotline'] ?? 'N/A' }}</p>
                <p class="mt-2"><strong>Email:</strong> {{ $headerSettings['email'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</template>

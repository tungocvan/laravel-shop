<div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
    <div class="p-4 bg-gray-50 border-b border-gray-200 text-center">
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">
            {{ substr(auth()->user()->name, 0, 1) }}
        </div>
        <h3 class="font-bold text-gray-900">{{ auth()->user()->name }}</h3>
        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
    </div>

    <nav class="flex flex-col">
        @php
            $menus = [
                ['route' => 'account.dashboard', 'label' => 'Bảng điều khiển', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                // Sau này sẽ mở comment dòng dưới khi làm Bước 3
                // ['route' => 'account.orders', 'label' => 'Đơn hàng của tôi', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ];
        @endphp

        @foreach($menus as $menu)
            <a href="{{ route($menu['route']) }}"
               class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors
               {{ request()->routeIs($menu['route'])
                   ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-600'
                   : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent'
               }}">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $menu['icon'] }}"></path>
                </svg>
                {{ $menu['label'] }}
            </a>
        @endforeach
        {{-- LINK VÀO TRANG AFFILIATE --}}
        <a href="{{ route('account.affiliate') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-bold {{ request()->routeIs('account.affiliate') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            Tiếp thị liên kết
            <span class="ml-auto bg-yellow-400 text-yellow-900 text-[10px] px-2 py-0.5 rounded-full">HOT</span>
        </a>
        {{-- LINK VÀO TRANG PROFILE --}}
        <a href="{{ route('account.profile') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition font-bold {{ request()->routeIs('account.profile') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            Hồ sơ Cá nhân
            <span class="ml-auto bg-yellow-400 text-yellow-900 text-[10px] px-2 py-0.5 rounded-full">HOT</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Đăng xuất
            </button>
        </form>
    </nav>
</div>

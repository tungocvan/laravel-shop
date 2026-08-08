<header class="fixed inset-x-0 top-0 z-30 h-16 border-b border-slate-200 bg-white/95 backdrop-blur lg:left-64">
    <div class="flex h-full items-center justify-between px-4 sm:px-6">
        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                @click="sidebarOpen = ! sidebarOpen" aria-label="Mở menu">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <p class="font-semibold text-slate-800">@yield('title', 'Quản trị')</p>
        <div class="flex items-center gap-3 text-sm text-slate-600">
            <span class="hidden sm:inline">{{ auth()->user()?->name }}</span>
            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Đăng xuất</button>
                </form>
            @endif
        </div>
    </div>
</header>

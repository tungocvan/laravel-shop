@php
    $accountConfig = is_array($accountConfig ?? null) ? $accountConfig : [];
    $accountStyle = in_array($accountConfig['style'] ?? null, ['avatar_name', 'avatar', 'button'], true) ? $accountConfig['style'] : 'avatar_name';
    $user = auth()->user();
    $dashboardRoute = \Illuminate\Support\Facades\Route::has('account.dashboard') ? 'account.dashboard' : null;
    $profileRoute = collect(['account.profile', 'profile.edit'])->first(fn ($name) => \Illuminate\Support\Facades\Route::has($name));
    $passwordRoute = collect(['account.password', 'profile.password'])->first(fn ($name) => \Illuminate\Support\Facades\Route::has($name));
    $ordersRoute = collect(['account.orders.index', 'account.orders'])->first(fn ($name) => \Illuminate\Support\Facades\Route::has($name));
    $adminRoute = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? 'admin.dashboard' : null;
    $canAccessAdmin = $user && (method_exists($user, 'hasRole') ? $user->hasRole('admin') || $user->hasRole('super-admin') : false);
@endphp
<div class="relative shrink-0" x-data="{ accountOpen: false }" @keydown.escape.window="accountOpen = false">
    @guest
        <div class="flex min-w-0 flex-wrap items-center justify-end gap-1 text-sm sm:gap-2">
            @if(\Illuminate\Support\Facades\Route::has('login'))
                <a href="{{ route('login') }}" class="whitespace-nowrap rounded-lg px-1.5 py-2 font-semibold hover:bg-slate-100 sm:px-2">Đăng nhập</a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('register'))
                <a href="{{ route('register') }}" class="whitespace-nowrap rounded-lg border px-2 py-2 font-semibold sm:px-3" style="border-color:var(--pharma-header-accent);color:var(--pharma-header-accent)">Đăng ký</a>
            @endif
        </div>
    @else
        <button type="button" @click="accountOpen = !accountOpen" :aria-expanded="accountOpen.toString()" aria-haspopup="true" aria-controls="pharma-account-dropdown" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-sm font-semibold shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
            @if($accountStyle !== 'button')
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style="background:var(--pharma-header-accent)" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim((string) ($user->name ?? 'U')), 0, 1)) }}</span>
            @endif
            @if($accountStyle === 'button')
                <span>Tài khoản</span>
            @elseif($accountStyle === 'avatar_name' && ($accountConfig['show_name'] ?? true))
                <span class="hidden max-w-28 truncate sm:inline">{{ $user->name }}</span>
            @endif
            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div id="pharma-account-dropdown" x-show="accountOpen" x-cloak x-transition.origin.top.right @click.outside="accountOpen = false" class="absolute right-0 z-50 mt-2 w-64 max-w-[calc(100vw-24px)] overflow-hidden rounded-xl border border-slate-200 bg-white py-2 text-sm text-slate-800 shadow-xl" role="menu">
            <div class="border-b border-slate-100 px-4 py-3">
                <div class="truncate font-bold">{{ $user->name }}</div>
                <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
            </div>
            @if($dashboardRoute)
                <a role="menuitem" href="{{ route($dashboardRoute) }}" class="block px-4 py-2.5 hover:bg-emerald-50">Tổng quan tài khoản</a>
            @endif
            @if(($accountConfig['show_profile'] ?? true) && $profileRoute)
                <a role="menuitem" href="{{ route($profileRoute) }}" class="block px-4 py-2.5 hover:bg-emerald-50">Thông tin cá nhân</a>
            @endif
            @if(($accountConfig['show_password'] ?? true) && $passwordRoute)
                <a role="menuitem" href="{{ route($passwordRoute) }}" class="block px-4 py-2.5 hover:bg-emerald-50">Đổi mật khẩu</a>
            @endif
            @if(($accountConfig['show_orders'] ?? true) && $ordersRoute)
                <a role="menuitem" href="{{ route($ordersRoute) }}" class="block px-4 py-2.5 hover:bg-emerald-50">Đơn hàng của tôi</a>
            @endif
            @if(($accountConfig['show_admin'] ?? true) && $canAccessAdmin && $adminRoute)
                <a role="menuitem" href="{{ route($adminRoute) }}" class="block px-4 py-2.5 hover:bg-emerald-50">Quản trị hệ thống</a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                    @csrf
                    <button role="menuitem" type="submit" class="w-full px-4 py-2.5 text-left font-semibold text-red-600 hover:bg-red-50">Đăng xuất</button>
                </form>
            @endif
        </div>
    @endguest
</div>

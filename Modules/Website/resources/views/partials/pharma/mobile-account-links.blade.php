<?php
    $mobileAccountConfig = is_array($accountConfig ?? null) ? $accountConfig : [];
    $mobileAccountUser = auth()->user();
    $mobileAccountDashboard = \Illuminate\Support\Facades\Route::has('account.dashboard') ? 'account.dashboard' : null;
    $mobileAccountProfile = collect(['account.profile', 'profile.edit'])->first(fn ($name) => \Illuminate\Support\Facades\Route::has($name));
    $mobileAccountPassword = collect(['account.password', 'profile.password'])->first(fn ($name) => \Illuminate\Support\Facades\Route::has($name));
    $mobileAccountOrders = collect(['account.orders.index', 'account.orders'])->first(fn ($name) => \Illuminate\Support\Facades\Route::has($name));
    $mobileAccountAdmin = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? 'admin.dashboard' : null;
    $mobileAccountCanAdmin = $mobileAccountUser && method_exists($mobileAccountUser, 'hasRole')
        && ($mobileAccountUser->hasRole('admin') || $mobileAccountUser->hasRole('super-admin'));
?>
@if($mobileAccountUser)
    <section aria-label="Tài khoản của tôi" class="shrink-0 border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div class="mb-2 min-w-0">
            <div class="truncate text-sm font-bold">{{ $mobileAccountUser->name }}</div>
            <div class="truncate text-xs text-slate-500">{{ $mobileAccountUser->email }}</div>
        </div>
        <nav aria-label="Liên kết tài khoản" class="grid gap-0.5 text-sm">
            @if($mobileAccountDashboard)
                <a href="{{ route($mobileAccountDashboard) }}" class="rounded-lg px-3 py-2 font-medium hover:bg-white">Tổng quan tài khoản</a>
            @endif
            @if(($mobileAccountConfig['show_profile'] ?? true) && $mobileAccountProfile)
                <a href="{{ route($mobileAccountProfile) }}" class="rounded-lg px-3 py-2 font-medium hover:bg-white">Thông tin cá nhân</a>
            @endif
            @if(($mobileAccountConfig['show_password'] ?? true) && $mobileAccountPassword)
                <a href="{{ route($mobileAccountPassword) }}" class="rounded-lg px-3 py-2 font-medium hover:bg-white">Đổi mật khẩu</a>
            @endif
            @if(($mobileAccountConfig['show_orders'] ?? true) && $mobileAccountOrders)
                <a href="{{ route($mobileAccountOrders) }}" class="rounded-lg px-3 py-2 font-medium hover:bg-white">Đơn hàng của tôi</a>
            @endif
            @if(($mobileAccountConfig['show_admin'] ?? true) && $mobileAccountCanAdmin && $mobileAccountAdmin)
                <a href="{{ route($mobileAccountAdmin) }}" class="rounded-lg px-3 py-2 font-medium hover:bg-white">Quản trị hệ thống</a>
            @endif
        </nav>
        @if(\Illuminate\Support\Facades\Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-200 pt-2">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Đăng xuất</button>
            </form>
        @endif
    </section>
@endif

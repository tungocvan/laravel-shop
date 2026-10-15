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
    <section aria-label="Tài khoản của tôi" class="shrink-0 px-3 pt-3 pb-2">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-base font-bold text-white" style="background:var(--pharma-header-accent)" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim((string) $mobileAccountUser->name), 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-bold text-slate-900">{{ $mobileAccountUser->name }}</div>
                    <div class="truncate text-xs text-slate-500">{{ $mobileAccountUser->email }}</div>
                </div>
                @if($mobileAccountDashboard)
                    <a href="{{ route($mobileAccountDashboard) }}" aria-label="Tổng quan tài khoản" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-800 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                @endif
            </div>
            <nav aria-label="Liên kết tài khoản" class="mt-3 grid grid-cols-2 gap-2">
                @if(($mobileAccountConfig['show_profile'] ?? true) && $mobileAccountProfile)
                    <a href="{{ route($mobileAccountProfile) }}" class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-xl border border-slate-100 bg-white px-2 py-2 text-center text-xs font-semibold text-slate-800 shadow-sm">
                        <svg class="h-5 w-5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <span>Thông tin cá nhân</span>
                    </a>
                @endif
                @if(($mobileAccountConfig['show_orders'] ?? true) && $mobileAccountOrders)
                    <a href="{{ route($mobileAccountOrders) }}" class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-xl border border-slate-100 bg-white px-2 py-2 text-center text-xs font-semibold text-slate-800 shadow-sm">
                        <svg class="h-5 w-5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 7 9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10"/></svg>
                        <span>Đơn hàng của tôi</span>
                    </a>
                @endif
            </nav>
            @if((($mobileAccountConfig['show_password'] ?? true) && $mobileAccountPassword) || (($mobileAccountConfig['show_admin'] ?? true) && $mobileAccountCanAdmin && $mobileAccountAdmin))
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-emerald-800">
                    @if(($mobileAccountConfig['show_password'] ?? true) && $mobileAccountPassword)
                        <a href="{{ route($mobileAccountPassword) }}" class="py-1.5 underline-offset-2 hover:underline">Đổi mật khẩu</a>
                    @endif
                    @if(($mobileAccountConfig['show_admin'] ?? true) && $mobileAccountCanAdmin && $mobileAccountAdmin)
                        <a href="{{ route($mobileAccountAdmin) }}" class="py-1.5 underline-offset-2 hover:underline">Quản trị hệ thống</a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif

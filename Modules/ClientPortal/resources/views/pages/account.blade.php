@extends('ClientPortal::layouts.application')

@section('title', 'Thông tin tài khoản')
@section('app-name', 'Tài khoản')
@section('app-subtitle', 'INAFO Client Portal')

@section('content')
    <section class="mx-auto max-w-3xl" aria-labelledby="client-account-heading">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-900 text-xl font-bold text-white">
                    @if($account['avatar_url'])
                        <img src="{{ $account['avatar_url'] }}" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                    @else
                        {{ $account['initials'] }}
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Thông tin tài khoản</p>
                    <h1 id="client-account-heading" class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-950">{{ $account['name'] }}</h1>
                    <p class="mt-1 truncate text-sm text-slate-500">{{ $account['email'] ?: 'Chưa có email' }}</p>
                </div>
            </div>

            <dl class="mt-8 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                <div class="grid gap-1 px-4 py-4 sm:grid-cols-[10rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-slate-500">Họ và tên</dt>
                    <dd class="break-words text-sm font-bold text-slate-900">{{ $account['name'] }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-4 sm:grid-cols-[10rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-slate-500">Email</dt>
                    <dd class="break-all text-sm font-bold text-slate-900">{{ $account['email'] ?: 'Chưa có email' }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-4 sm:grid-cols-[10rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-slate-500">Điện thoại</dt>
                    <dd class="break-words text-sm font-bold text-slate-900">{{ $account['phone'] ?: 'Chưa cập nhật' }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-4 sm:grid-cols-[10rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-slate-500">Xác minh email</dt>
                    <dd class="text-sm font-bold {{ $account['email_verified'] ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $account['email_verified'] ? 'Đã xác minh' : 'Chưa xác minh' }}
                    </dd>
                </div>
                <div class="grid gap-1 px-4 py-4 sm:grid-cols-[10rem_1fr] sm:gap-4">
                    <dt class="text-sm font-semibold text-slate-500">Google</dt>
                    <dd class="text-sm font-bold {{ $account['google_linked'] ? 'text-emerald-700' : 'text-slate-600' }}">
                        {{ $account['google_linked'] ? 'Đã liên kết' : 'Chưa liên kết' }}
                    </dd>
                </div>
            </dl>

            <p class="mt-5 text-xs leading-5 text-slate-500">
                MR-8 chỉ hiển thị thông tin hiện tại. Thay đổi hồ sơ, mật khẩu và ảnh đại diện không nằm trong phạm vi này.
            </p>
        </div>
    </section>
@endsection

@extends('ClientPortal::layouts.application')

@section('title', 'Cài đặt tài khoản')
@section('app-name', 'Cài đặt')
@section('app-subtitle', 'INAFO Client Portal')

@section('content')
    <section class="mx-auto max-w-3xl space-y-5" aria-labelledby="client-settings-heading">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">ClientPortal</p>
            <h1 id="client-settings-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Cài đặt tài khoản</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Quản lý phương thức đăng nhập đang được hỗ trợ cho tài khoản của bạn.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-950">Đăng nhập bằng Google</h2>
                        @if($account['google_linked'])
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Đã liên kết</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Chưa liên kết</span>
                        @endif
                    </div>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">
                        Google phải cung cấp email đã xác minh và email đó phải khớp với tài khoản ClientPortal hiện tại.
                    </p>
                </div>

                @if(! $account['google_linked'] && Route::has('client.apps.google.link'))
                    <a href="{{ route('client.apps.google.link') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        Liên kết Google
                    </a>
                @endif
            </div>

            @if($account['google_linked'])
                <p class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    Tài khoản đã có thể sử dụng phương thức đăng nhập Google.
                </p>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <h2 class="text-lg font-bold text-slate-950">Phiên đăng nhập ClientPortal</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                PWA và trình duyệt dùng chung Laravel web guard. Đăng xuất từ menu tài khoản sẽ hủy phiên Client, làm mới CSRF token và quay về màn hình đăng nhập PWA.
            </p>
        </div>
    </section>
@endsection

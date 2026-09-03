@extends('Admin::layouts.master')
@section('title', 'Đăng nhập & Điều hướng')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Đăng nhập & Điều hướng</h1>
                <p class="mt-1 text-sm text-gray-500">Chọn trang mặc định được mở khi truy cập <code>/admin</code> và sau khi Admin đăng nhập thành công.</p>
            </div>
            <a href="{{ route('admin.system.settings.index') }}"
               class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Cấu hình hệ thống
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST"
              action="{{ route('admin.system.settings.login-redirect.update') }}"
              class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <h2 class="text-lg font-semibold text-gray-900">Đăng nhập & Điều hướng</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Route <code>/admin</code> là entrypoint động. Nếu route đã chọn không còn khả dụng, hệ thống sẽ quay về <code>/admin/dashboard</code>.
                </p>
            </div>

            <div class="mt-6">
                <label for="admin-login-redirect-route" class="block text-sm font-medium text-gray-900">Trang mặc định của Admin</label>
                <select id="admin-login-redirect-route"
                        name="route_name"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($routeOptions as $name => $label)
                        <option value="{{ $name }}" @selected(old('route_name', $routeName) === $name)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('route_name')
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex justify-end border-t pt-6">
                <button type="submit" class="h-12 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white hover:bg-indigo-700">
                    Lưu điều hướng Admin
                </button>
            </div>
        </form>
    </div>
@endsection

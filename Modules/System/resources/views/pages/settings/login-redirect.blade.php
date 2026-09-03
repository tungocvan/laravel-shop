@extends('Admin::layouts.master')
@section('title', 'Đăng nhập & Điều hướng')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Đăng nhập & Điều hướng</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý trang mặc định của Admin và trang thay thế khi Website không phục vụ route <code>/</code>.</p>
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
                    Route <code>/admin</code> là entrypoint động. Nếu route Admin đã chọn không còn khả dụng, hệ thống sẽ quay về <code>/admin/dashboard</code>.
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

            <div class="mt-6 border-t border-gray-200 pt-6">
                <div class="flex flex-col gap-1">
                    <label for="application-root-fallback-route" class="block text-sm font-medium text-gray-900">Trang thay thế khi Website không phục vụ <code>/</code></label>
                    <p class="text-sm text-gray-500">
                        Khi Website đang hoạt động và route <code>/</code> tồn tại, trang chủ Website vẫn được ưu tiên. Cấu hình này chỉ chạy khi route gốc không được đăng ký.
                    </p>
                </div>

                <select id="application-root-fallback-route"
                        name="root_route_name"
                        class="mt-3 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($rootRouteOptions as $name => $label)
                        <option value="{{ $name }}" @selected(old('root_route_name', $rootRouteName) === $name)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('root_route_name')
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-800">
                    Danh sách chỉ gồm các GET route có tên, không có tham số bắt buộc và không trỏ về chính <code>/</code>. Nhãn route cho biết route công khai hay yêu cầu đăng nhập.
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t pt-6">
                <button type="submit" class="h-12 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white hover:bg-indigo-700">
                    Lưu cấu hình điều hướng
                </button>
            </div>
        </form>
    </div>
@endsection

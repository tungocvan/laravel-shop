@extends('Muasamcong::client.layout')

@section('title', 'Mua sắm công')

@section('content')
    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white shadow-sm sm:px-8 sm:py-9">
        <p class="text-sm font-semibold text-slate-300">Ứng dụng dữ liệu thực tế</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">Mua sắm công</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">
            Tra cứu và phân tích dữ liệu mua sắm công. Chỉ các chức năng được quản trị viên cấp quyền mới xuất hiện trong dashboard này.
        </p>
    </section>

    <section class="mt-7">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold">Chức năng của bạn</h2>
                <p class="mt-1 text-sm text-slate-500">Chọn công cụ để bắt đầu làm việc.</p>
            </div>
        </div>

        @if($features->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <h3 class="font-semibold">Chưa có chức năng được cấp quyền</h3>
                <p class="mt-2 text-sm text-slate-500">Liên hệ quản trị viên để được cấp thêm quyền sử dụng.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($features as $feature)
                    @php($routeExists = !empty($feature['route']) && Route::has($feature['route']))
                    @if($routeExists)
                        <a href="{{ route($feature['route']) }}" class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $feature['key'] }}</p>
                                    <h3 class="mt-2 text-lg font-bold">{{ $feature['name'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['description'] ?: 'Chức năng thuộc ứng dụng Mua sắm công.' }}</p>
                                </div>
                                <span class="rounded-2xl bg-slate-100 px-3 py-2 text-sm">→</span>
                            </div>
                        </a>
                    @else
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 opacity-60">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Sắp có</p>
                            <h3 class="mt-2 text-lg font-bold">{{ $feature['name'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Chức năng đã được định nghĩa nhưng chưa triển khai giao diện Client.</p>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
@endsection

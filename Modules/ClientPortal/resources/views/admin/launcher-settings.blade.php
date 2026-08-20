@extends('Admin::layouts.master')
@section('title', 'Application Launcher')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Application Launcher</h1>
            <p class="mt-1 text-sm text-gray-500">Quản trị nội dung trang /my-apps và cách hiển thị từng application card. Route, permission và source module vẫn do manifest kiểm soát.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.client-apps.pwa.edit') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cấu hình PWA</a>
            <a href="{{ route('admin.client-apps.index') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">← Ứng dụng Client</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Chưa thể lưu cấu hình.</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.72fr)]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.client-apps.pwa.launcher.update') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Nội dung Launcher</h2>
                        <p class="mt-1 text-sm text-gray-500">Các text này được render động tại /my-apps.</p>
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu Launcher</button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach([
                        'browser_title' => 'Tiêu đề trình duyệt',
                        'brand_title' => 'Tên thương hiệu',
                        'brand_subtitle' => 'Dòng phụ thương hiệu',
                        'workspace_label' => 'Nhãn workspace',
                        'heading' => 'Tiêu đề chính',
                        'install_button_text' => 'Nút cài ứng dụng',
                        'logout_button_text' => 'Nút đăng xuất',
                        'open_application_text' => 'CTA mở ứng dụng',
                        'empty_title' => 'Tiêu đề trạng thái rỗng',
                    ] as $key => $label)
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                            <input name="{{ $key }}" value="{{ old($key, $launcher[$key] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        </label>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-800">Mô tả Launcher</span>
                        <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('description', $launcher['description'] ?? '') }}</textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-800">Mô tả trạng thái rỗng</span>
                        <textarea name="empty_description" rows="4" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('empty_description', $launcher['empty_description'] ?? '') }}</textarea>
                    </label>
                </div>

                <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <input type="hidden" name="show_source_module" value="0">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="show_source_module" value="1" @checked(old('show_source_module', $launcher['show_source_module'] ?? true)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span><strong class="block text-sm text-gray-900">Hiển thị tên source module trên application card</strong><span class="text-xs text-gray-500">Ví dụ: Muasamcong. Chỉ ảnh hưởng presentation.</span></span>
                    </label>
                </div>
            </form>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Application cards</h2>
                    <p class="mt-1 text-sm text-gray-500">Manifest là nguồn chuẩn cho route và permission. Admin chỉ override tên, mô tả, thứ tự và trạng thái hiển thị.</p>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse($applications as $item)
                        @php($manifest = $item['manifest'])
                        @php($presentation = $item['presentation'])
                        <form method="POST" action="{{ route('admin.client-apps.pwa.applications.update', $manifest['key']) }}" class="rounded-xl border border-gray-200 p-4">
                            @csrf
                            @method('PUT')
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-gray-900">{{ $manifest['name'] }}</h3>
                                        <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-500">{{ $manifest['key'] }}</code>
                                        <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-500">{{ $manifest['permission'] }}</code>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">Route: {{ $manifest['route'] }}</p>
                                </div>
                                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Lưu card</button>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-semibold text-gray-800">Tên hiển thị</span>
                                    <input name="name" value="{{ old('name', $presentation['name'] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                </label>
                                <label class="block">
                                    <span class="text-sm font-semibold text-gray-800">Thứ tự</span>
                                    <input name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $presentation['sort_order'] ?? 100) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                </label>
                                <label class="block md:col-span-2">
                                    <span class="text-sm font-semibold text-gray-800">Mô tả hiển thị</span>
                                    <textarea name="description" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('description', $presentation['description'] ?? '') }}</textarea>
                                </label>
                            </div>

                            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3">
                                <input type="hidden" name="enabled" value="0">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $presentation['enabled'] ?? true)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span><strong class="block text-sm text-gray-900">Hiển thị trên Launcher</strong><span class="text-xs text-gray-500">Tắt chỉ ẩn card ở /my-apps; không thay permission hay route của application.</span></span>
                                </label>
                            </div>
                        </form>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Chưa có application adapter khả dụng.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="xl:sticky xl:top-4 xl:self-start">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-slate-50 shadow-sm">
                <div class="border-b border-slate-200 bg-white px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Preview Launcher</p>
                    <h2 class="mt-1 font-bold text-slate-900">{{ $launcher['brand_title'] ?? '' }}</h2>
                    <p class="text-xs text-slate-500">{{ $launcher['brand_subtitle'] ?? '' }}</p>
                </div>
                <div class="p-5">
                    <div class="rounded-2xl bg-slate-900 p-5 text-white">
                        <p class="text-xs font-semibold text-slate-300">{{ $launcher['workspace_label'] ?? '' }}</p>
                        <h3 class="mt-1 text-2xl font-bold">{{ $launcher['heading'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $launcher['description'] ?? '' }}</p>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach($applications as $item)
                            @php($manifest = $item['manifest'])
                            @php($presentation = $item['presentation'])
                            @if($presentation['enabled'] ?? true)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    @if($launcher['show_source_module'] ?? true)<p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $manifest['module'] }}</p>@endif
                                    <strong class="mt-1 block text-slate-900">{{ $presentation['name'] }}</strong>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $presentation['description'] }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

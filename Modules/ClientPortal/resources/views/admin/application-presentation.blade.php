@extends('Admin::layouts.master')
@section('title', 'Giao diện ứng dụng Client')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $application['name'] }}</h1>
            <p class="mt-1 text-sm text-gray-500">Quản trị presentation của application và feature. Route/permission vẫn do manifest kiểm soát.</p>
        </div>
        <a href="{{ route('admin.client-apps.pwa.launcher.edit') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">← Launcher</a>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid gap-3 text-sm sm:grid-cols-3">
            <div><span class="text-gray-400">Key</span><strong class="block">{{ $application['key'] }}</strong></div>
            <div><span class="text-gray-400">Route</span><code class="block">{{ $application['route'] }}</code></div>
            <div><span class="text-gray-400">Permission</span><code class="block">{{ $application['permission'] }}</code></div>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.client-apps.pwa.applications.hub.update', $application['key']) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf @method('PUT')
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Hub / Dashboard</h2>
                <p class="mt-1 text-sm text-gray-500">Quản trị Hero và nội dung hỗ trợ của application Hub. Route, permission và nghiệp vụ vẫn do source code kiểm soát.</p>
            </div>
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Lưu Hub</button>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="block"><span class="text-sm font-semibold text-gray-800">Nhãn / Eyebrow</span><input name="eyebrow" required maxlength="80" value="{{ old('eyebrow', $hubPresentation['eyebrow']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
            <label class="block"><span class="text-sm font-semibold text-gray-800">Tiêu đề Hub</span><input name="title" required maxlength="160" value="{{ old('title', $hubPresentation['title']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
            <label class="block md:col-span-2"><span class="text-sm font-semibold text-gray-800">Mô tả Hub</span><textarea name="description" maxlength="500" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">{{ old('description', $hubPresentation['description']) }}</textarea></label>
        </div>

        <div class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4">
            <input type="hidden" name="supporting_visible" value="0">
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900"><input type="checkbox" name="supporting_visible" value="1" @checked(old('supporting_visible', $hubPresentation['supporting_visible']))> Hiển thị nội dung hỗ trợ</label>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block"><span class="text-sm font-semibold text-gray-800">Tiêu đề hỗ trợ</span><input name="supporting_title" maxlength="160" value="{{ old('supporting_title', $hubPresentation['supporting_title']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                <label class="block md:col-span-2"><span class="text-sm font-semibold text-gray-800">Nội dung hỗ trợ</span><textarea name="supporting_body" maxlength="1000" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">{{ old('supporting_body', $hubPresentation['supporting_body']) }}</textarea></label>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.client-apps.pwa.applications.navigation.update', $application['key']) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf @method('PUT')
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Bottom Navigation</h2>
                <p class="mt-1 text-sm text-gray-500">Chỉ điều khiển shortcut trên mobile. Sidebar tablet/desktop, route và permission vẫn giữ nguyên theo manifest.</p>
            </div>
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Lưu Navigation</button>
        </div>
        <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200">
            @foreach(collect($application['navigation'] ?? [])->values() as $index => $item)
                @php($navPresentation = collect($navigationPresentation['items'])->firstWhere('key', $item['key']))
                <div class="grid gap-3 border-b border-gray-100 p-4 last:border-b-0 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $item['name'] }}</div>
                        <div class="mt-1 text-xs text-gray-500"><code>{{ $item['route'] }}</code> · <code>{{ $item['permission'] ?? '—' }}</code></div>
                    </div>
                    <input type="hidden" name="items[{{ $index }}][key]" value="{{ $item['key'] }}">
                    <input type="hidden" name="items[{{ $index }}][bottom_enabled]" value="0">
                    <label class="block text-sm"><span class="font-semibold">Icon</span><input name="items[{{ $index }}][bottom_icon]" value="{{ old("items.$index.bottom_icon", $navPresentation['bottom_icon'] ?? $item['icon']) }}" class="mt-1 w-36 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm"></label>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="items[{{ $index }}][bottom_enabled]" value="1" @checked(old("items.$index.bottom_enabled", $navPresentation['bottom_enabled'] ?? true))> Hiển thị mobile</label>
                    <label class="flex items-center gap-2 text-sm"><span class="font-semibold">Thứ tự</span><input type="number" min="0" max="9999" name="items[{{ $index }}][bottom_sort_order]" value="{{ old("items.$index.bottom_sort_order", $navPresentation['bottom_sort_order'] ?? $item['sort_order']) }}" class="w-24 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm"></label>
                </div>
            @endforeach
        </div>
    </form>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach($features as $row)
            @php($feature = $row['manifest'])
            @php($presentation = $row['presentation'])
            <form method="POST" action="{{ route('admin.client-apps.pwa.features.update', [$application['key'], $feature['key']]) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                @csrf @method('PUT')
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="font-bold text-gray-900">{{ $feature['name'] }}</h2><p class="text-xs text-gray-400">{{ $feature['key'] }}</p></div>
                    <button class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Lưu</button>
                </div>
                <div class="mt-4 rounded-xl bg-gray-50 p-3 text-xs text-gray-600">
                    <div>Route: <code>{{ $feature['route'] ?? '—' }}</code></div>
                    <div class="mt-1">Permission: <code>{{ $feature['permission'] ?? '—' }}</code></div>
                </div>
                <div class="mt-4 space-y-4">
                    <input type="hidden" name="enabled" value="0"><label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $presentation['enabled']))> Hiển thị feature</label>
                    <label class="block"><span class="text-sm font-semibold">Tên hiển thị</span><input name="name" value="{{ old('name', $presentation['name']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                    <label class="block"><span class="text-sm font-semibold">Mô tả</span><textarea name="description" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">{{ old('description', $presentation['description']) }}</textarea></label>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50/60 p-4">
                        <div>
                            <h3 class="text-sm font-bold text-sky-950">Nội dung trang PWA</h3>
                            <p class="mt-1 text-xs leading-5 text-sky-700">Chỉ thay đổi nội dung hiển thị. Route, permission và nghiệp vụ vẫn do source code kiểm soát.</p>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="block"><span class="text-sm font-semibold text-gray-800">Nhãn / Eyebrow</span><input name="eyebrow" required maxlength="80" value="{{ old('eyebrow', $presentation['eyebrow']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                            <label class="block"><span class="text-sm font-semibold text-gray-800">Tiêu đề trang</span><input name="page_title" required maxlength="160" value="{{ old('page_title', $presentation['page_title']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                            <label class="block"><span class="text-sm font-semibold text-gray-800">Mô tả trang</span><textarea name="page_description" maxlength="500" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">{{ old('page_description', $presentation['page_description']) }}</textarea></label>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label><span class="text-sm font-semibold">Thứ tự</span><input type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', $presentation['sort_order']) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                        <label><span class="text-sm font-semibold">Badge</span><input name="badge" value="{{ old('badge', $presentation['badge']) }}" placeholder="Beta / Mới" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                    </div>
                    <input type="hidden" name="maintenance" value="0"><label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="maintenance" value="1" @checked(old('maintenance', $presentation['maintenance']))> Đánh dấu bảo trì</label>
                    <label class="block"><span class="text-sm font-semibold">Thông báo bảo trì</span><textarea name="maintenance_message" rows="2" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">{{ old('maintenance_message', $presentation['maintenance_message']) }}</textarea></label>
                </div>
            </form>
        @endforeach
    </div>
</div>
@endsection

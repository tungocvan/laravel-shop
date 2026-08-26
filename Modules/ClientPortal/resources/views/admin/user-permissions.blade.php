@extends('Admin::layouts.master')
@section('title', 'Quyền Web User')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('admin.client-apps.index') }}" class="text-sm font-medium text-blue-600">← Ứng dụng Client</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Phân quyền Web cho {{ $user->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $user->email }} · Quản lý Role và permission guard web; mọi Role/quyền guard admin được giữ nguyên.</p>
    </div>
    @if(session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('admin.client-apps.users.update', $user) }}" class="space-y-6">@csrf @method('PUT')
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900">Role guard web</h2>
            <p class="mt-1 text-sm text-gray-500">Ưu tiên gán Role thay vì cấp nhiều quyền trực tiếp cho từng User.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse($roles as $role)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-blue-300">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array((int) $role->id, $selectedRoles, true)) class="rounded border-gray-300">
                        <span><span class="block text-sm font-semibold text-gray-900">{{ $role->name }}</span><span class="text-xs text-gray-500">guard: web</span></span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Chưa có Role guard web. Hãy tạo Role tại trang Ứng dụng Client.</p>
                @endforelse
            </div>
        </section>

        @foreach($definitions->groupBy('source') as $source => $sourceItems)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-gray-900">{{ $source === 'application' ? 'Quyền trực tiếp · ClientPortal' : 'Quyền trực tiếp · Domain' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $source === 'application' ? 'Quyền truy cập ứng dụng/tính năng gán trực tiếp cho User.' : 'Quyền nghiệp vụ gán trực tiếp cho User. Chỉ dùng khi Role không phù hợp.' }}</p>
                </div>
                @foreach($sourceItems->groupBy('group') as $group => $items)
                    <fieldset class="mb-6 last:mb-0">
                        <legend class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-700">{{ $group }}</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach($items as $item)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:border-blue-300">
                                    <input type="checkbox" name="permissions[]" value="{{ $item['name'] }}" @checked(in_array($item['name'], $selected, true)) class="mt-1 rounded border-gray-300">
                                    <span><span class="block text-sm font-semibold text-gray-900">{{ $item['label'] }}</span><code class="mt-1 block text-xs text-gray-500">{{ $item['name'] }}</code></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </section>
        @endforeach

        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h2 class="font-bold text-slate-900">Quyền Web hiệu lực hiện tại</h2>
            <p class="mt-1 text-xs text-slate-500">Bao gồm quyền trực tiếp và quyền kế thừa từ Role.</p>
            <div class="mt-3 flex flex-wrap gap-2">@forelse($effective as $permission)<code class="rounded-lg bg-white px-2.5 py-1 text-xs text-slate-600 ring-1 ring-slate-200">{{ $permission }}</code>@empty<span class="text-sm text-slate-500">Chưa có quyền Web.</span>@endforelse</div>
        </section>

        <div class="flex justify-end"><button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lưu Role & quyền Web</button></div>
    </form>
</div>
@endsection

@extends('Admin::layouts.master')
@section('title', 'Quyền Web Role')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('admin.client-apps.index') }}" class="text-sm font-medium text-blue-600">← Ứng dụng Client</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Phân quyền Web cho Role {{ $role->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Guard: {{ $role->guard_name }} · Chỉ quản lý permission guard web.</p>
    </div>
    @if(session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif

    <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
        <h2 class="font-bold text-indigo-950">Profile nghiệp vụ</h2>
        <p class="mt-1 text-sm text-indigo-700">Áp dụng profile sẽ thay thế toàn bộ permission Web được quản lý của Role này; quyền ngoài catalog và guard admin không bị tác động.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach($profiles as $key => $profile)
                <form method="POST" action="{{ route('admin.client-apps.roles.profile', $role) }}" class="rounded-xl bg-white p-4 ring-1 ring-indigo-100">@csrf
                    <input type="hidden" name="profile" value="{{ $key }}">
                    <div class="font-semibold text-gray-900">{{ $profile['name'] }}</div>
                    <p class="mt-1 text-xs leading-5 text-gray-500">{{ $profile['description'] }}</p>
                    <button class="mt-3 rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700">Áp dụng profile</button>
                </form>
            @endforeach
        </div>
    </section>

    <form method="POST" action="{{ route('admin.client-apps.roles.update', $role) }}" class="space-y-6">@csrf @method('PUT')
        @foreach($definitions->groupBy('source') as $source => $sourceItems)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-gray-900">{{ $source === 'application' ? 'Quyền ứng dụng ClientPortal' : 'Quyền nghiệp vụ Domain' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $source === 'application' ? 'Kiểm soát truy cập ứng dụng, tính năng và hành động trên ClientPortal.' : 'Kiểm soát thao tác nghiệp vụ của role trên guard web.' }}</p>
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
        <div class="flex justify-end"><button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lưu phân quyền Web</button></div>
    </form>
</div>
@endsection

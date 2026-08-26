@extends('Admin::layouts.master')
@section('title', 'Quyền Web User')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <a href="{{ route('admin.client-apps.index') }}" class="text-sm font-medium text-blue-600">← Ứng dụng Client</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Phân quyền Web cho {{ $user->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $user->email }} · Quản lý permission guard web từ Application và Domain; quyền guard admin không bị thay đổi.</p>
    </div>
    @if(session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('admin.client-apps.users.update', $user) }}" class="space-y-6">@csrf @method('PUT')
        @foreach($definitions->groupBy('source') as $source => $sourceItems)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-gray-900">{{ $source === 'application' ? 'Quyền ứng dụng ClientPortal' : 'Quyền nghiệp vụ Domain' }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $source === 'application' ? 'Kiểm soát truy cập ứng dụng, tính năng và hành động trên ClientPortal.' : 'Kiểm soát thao tác nghiệp vụ của user trên guard web.' }}</p>
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

@extends('Admin::layouts.master')
@section('title', 'Ứng dụng Client')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">Ứng dụng Client</h1><p class="mt-1 text-sm text-gray-500">Quét manifest trong ClientPortal, đồng bộ permission và phân quyền User/Role.</p></div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.client-apps.sync-permissions') }}">@csrf<button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Quét & đồng bộ quyền</button></form>
            <form method="POST" action="{{ route('admin.client-apps.sync-super-admin') }}">@csrf<button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Đồng bộ Super Admin</button></form>
        </div>
    </div>
    @if(session('success'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($applications as $app)
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex justify-between gap-4"><div><h2 class="text-lg font-bold">{{ $app['name'] }}</h2><p class="text-xs uppercase text-gray-400">{{ $app['module'] }} · {{ $app['key'] }}</p><p class="mt-2 text-sm text-gray-600">{{ $app['description'] }}</p></div><span class="h-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Enabled</span></div>
                <div class="mt-4 space-y-2"><div class="rounded-lg bg-gray-50 px-3 py-2 text-xs font-mono">{{ $app['permission'] }}</div>@foreach($app['features'] as $feature)<div class="border-t border-gray-100 pt-2 text-sm"><div class="flex justify-between gap-3"><span>{{ $feature['name'] }}</span><code class="text-xs text-gray-500">{{ $feature['permission'] }}</code></div>@foreach($feature['actions'] ?? [] as $action)<div class="mt-1 flex justify-between gap-3 pl-4 text-xs text-gray-500"><span>↳ {{ $action['name'] }}</span><code>{{ $action['permission'] }}</code></div>@endforeach</div>@endforeach</div>
            </section>
        @empty<div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">Chưa có Application adapter khả dụng.</div>@endforelse
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Phân quyền theo User</h2><div class="mt-4 divide-y">@foreach($users as $user)<a href="{{ route('admin.client-apps.users.edit', $user) }}" class="flex justify-between py-3 text-sm hover:text-blue-600"><span><strong>{{ $user->name }}</strong> <span class="text-gray-400">{{ $user->email }}</span></span><span>Thiết lập →</span></a>@endforeach</div></section>
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Phân quyền theo Role Client</h2><div class="mt-4 divide-y">@forelse($roles as $role)<a href="{{ route('admin.client-apps.roles.edit', $role) }}" class="flex justify-between py-3 text-sm hover:text-blue-600"><strong>{{ $role->name }}</strong><span>Thiết lập →</span></a>@empty<p class="py-4 text-sm text-gray-500">Chưa có Role Client guard web.</p>@endforelse</div></section>
    </div>
</div>
@endsection

@extends('Admin::layouts.master')
@section('title', 'Quyền ứng dụng User')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div><a href="{{ route('admin.client-apps.index') }}" class="text-sm font-medium text-blue-600">← Ứng dụng Client</a><h1 class="mt-2 text-2xl font-bold text-gray-900">Phân quyền Application cho {{ $user->name }}</h1><p class="mt-1 text-sm text-gray-500">{{ $user->email }} · Chỉ cập nhật permission namespace client.*</p></div>
    @if(session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('admin.client-apps.users.update', $user) }}" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">@csrf @method('PUT')
        @foreach($definitions->groupBy('application') as $application => $items)
            <fieldset class="mb-6 last:mb-0"><legend class="mb-3 text-base font-bold uppercase tracking-wide text-gray-900">{{ $application }}</legend><div class="grid gap-3 sm:grid-cols-2">
                @foreach($items as $item)<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:border-blue-300"><input type="checkbox" name="permissions[]" value="{{ $item['name'] }}" @checked(in_array($item['name'], $selected, true)) class="mt-1 rounded border-gray-300"><span><span class="block text-sm font-semibold text-gray-900">{{ $item['label'] }}</span><code class="mt-1 block text-xs text-gray-500">{{ $item['name'] }}</code></span></label>@endforeach
            </div></fieldset>
        @endforeach
        <div class="mt-6 flex justify-end"><button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lưu phân quyền</button></div>
    </form>
</div>
@endsection

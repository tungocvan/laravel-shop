<div class="space-y-6">
    @if(session('request_success'))<div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('request_success') }}</div>@endif
    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="createGroup" class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">{{ __('Request::request.groups.create') }}</h2>
            <label class="mt-4 block text-sm">{{ __('Request::request.code') }}<input wire:model="groupCode" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>@error('groupCode')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <label class="mt-4 block text-sm">{{ __('Request::request.name') }}<input wire:model="groupName" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>@error('groupName')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-white" wire:loading.attr="disabled">{{ __('Request::request.create') }}</button>
        </form>
        <form wire:submit="createType" class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">{{ __('Request::request.types.create') }}</h2>
            <label class="mt-4 block text-sm">{{ __('Request::request.groups.title') }}<select wire:model="requestGroupId" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="">—</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
            <label class="mt-4 block text-sm">{{ __('Request::request.code') }}<input wire:model="typeCode" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
            <label class="mt-4 block text-sm">{{ __('Request::request.name') }}<input wire:model="typeName" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
            <button class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-white" wire:loading.attr="disabled">{{ __('Request::request.create') }}</button>
        </form>
    </div>
    <label class="block max-w-md text-sm">{{ __('Request::request.search') }}<input wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="min-w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="p-3 text-left">{{ __('Request::request.code') }}</th><th class="p-3 text-left">{{ __('Request::request.name') }}</th><th class="p-3 text-left">{{ __('Request::request.status') }}</th><th></th></tr></thead><tbody>@forelse($types as $type)<tr class="border-b"><td class="p-3">{{ $type->code }}</td><td class="p-3">{{ $type->name }}</td><td class="p-3">{{ $type->status->value }}</td><td class="p-3"><a class="text-indigo-600" href="{{ route('request.admin.types.designer', $type->public_id) }}">{{ __('Request::request.edit') }}</a></td></tr>@empty<tr><td colspan="4" class="p-6 text-center">{{ __('Request::request.empty') }}</td></tr>@endforelse</tbody></table></div><div>{{ $types->links() }}</div>
</div>

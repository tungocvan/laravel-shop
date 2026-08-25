<div class="space-y-6">
    @include('Request::partials.workspace-navigation')

    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-2xl font-bold text-gray-900">{{ __('Request::request.mine.title') }}</h1><p class="mt-1 text-sm text-gray-600">{{ __('Request::request.mine.description') }}</p></div><a href="{{ route('request.catalog') }}" class="inline-flex justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white">{{ __('Request::request.create_draft') }}</a></header>
    <div class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <label class="sm:col-span-2"><span class="text-sm font-medium text-gray-700">{{ __('Request::request.search') }}</span><input wire:model.live.debounce.300ms="search" type="search" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
        <label><span class="text-sm font-medium text-gray-700">{{ __('Request::request.status') }}</span><select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">{{ __('Request::request.all') }}</option>@foreach($statuses as $option)<option value="{{ $option->value }}">{{ __('Request::request.statuses.'.$option->value) }}</option>@endforeach</select></label>
        <label><span class="text-sm font-medium text-gray-700">{{ __('Request::request.page_size') }}</span><select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">@foreach($pageSizes as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach</select></label>
        <button type="button" wire:click="resetFilters" class="text-left text-sm font-semibold text-indigo-700 sm:col-span-4 sm:justify-self-end">{{ __('Request::request.reset_filters') }}</button>
    </div>
    <div wire:loading.delay class="text-sm text-indigo-700">{{ __('Request::request.loading') }}</div>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse($requests as $item)
            <a href="{{ route('request.show', $item->public_id) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300">
                <div class="flex items-start justify-between gap-3"><div><div class="font-mono text-xs text-gray-500">{{ $item->request_number }}</div><h2 class="mt-1 font-bold text-gray-900">{{ $item->title_snapshot }}</h2></div><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$item->status->value) }}</span></div>
                <div class="mt-4 text-sm text-gray-500">{{ __('Request::request.updated') }} {{ $item->updated_at->diffForHumans() }}</div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center md:col-span-2"><h2 class="font-semibold text-gray-900">{{ __('Request::request.mine.empty') }}</h2><p class="mt-1 text-sm text-gray-500">{{ __('Request::request.mine.empty_help') }}</p></div>
        @endforelse
    </div>
    @if($requests->hasPages())<div>{{ $requests->links() }}</div>@endif
</div>

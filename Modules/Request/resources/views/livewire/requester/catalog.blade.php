<div class="space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">{{ __('Request::request.catalog.title') }}</h1><p class="mt-1 text-sm text-gray-600">{{ __('Request::request.catalog.description') }}</p></div>
        <a href="{{ route('request.mine') }}" class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-indigo-700">{{ __('Request::request.mine.title') }}</a>
    </header>

    <div class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <label class="sm:col-span-2"><span class="text-sm font-medium text-gray-700">{{ __('Request::request.search') }}</span><input wire:model.live.debounce.300ms="search" type="search" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></label>
        <label><span class="text-sm font-medium text-gray-700">{{ __('Request::request.group') }}</span><select wire:model.live="groupId" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">{{ __('Request::request.all') }}</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
        <label><span class="text-sm font-medium text-gray-700">{{ __('Request::request.page_size') }}</span><select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">@foreach($pageSizes as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach</select></label>
        <button type="button" wire:click="resetFilters" class="text-left text-sm font-semibold text-indigo-700 sm:col-span-4 sm:justify-self-end">{{ __('Request::request.reset_filters') }}</button>
    </div>

    <div wire:loading.delay class="rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-700">{{ __('Request::request.loading') }}</div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($types as $type)
            <article class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $type->group->name }}</div>
                <h2 class="mt-2 text-lg font-bold text-gray-900">{{ $type->name }}</h2>
                <p class="mt-2 flex-1 text-sm leading-6 text-gray-600">{{ $type->summary ?: __('Request::request.catalog.no_summary') }}</p>
                <a href="{{ route('request.create', $type->public_id) }}" class="mt-5 inline-flex justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white">{{ __('Request::request.create_draft') }}</a>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center md:col-span-2 xl:col-span-3"><h2 class="font-semibold text-gray-900">{{ __('Request::request.catalog.empty') }}</h2><p class="mt-1 text-sm text-gray-500">{{ __('Request::request.catalog.empty_help') }}</p></div>
        @endforelse
    </div>
    @if($types->hasPages())<div>{{ $types->links() }}</div>@endif
</div>

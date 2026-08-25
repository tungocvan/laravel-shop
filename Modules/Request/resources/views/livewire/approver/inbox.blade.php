<div class="mx-auto max-w-6xl space-y-5">
    <header><h1 class="text-2xl font-bold text-gray-900">{{ __('Request::request.inbox.title') }}</h1><p class="mt-1 text-sm text-gray-600">{{ __('Request::request.inbox.subtitle') }}</p></header>
    @if(session('request_success'))<div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('request_success') }}</div>@endif
    <div class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto]"><label class="sr-only" for="request-inbox-search">{{ __('Request::request.search') }}</label><input id="request-inbox-search" wire:model.live.debounce.300ms="search" class="rounded-xl border border-gray-300 px-4 py-3" placeholder="{{ __('Request::request.inbox.search_placeholder') }}"><select wire:model.live="perPage" aria-label="{{ __('Request::request.page_size') }}" class="rounded-xl border border-gray-300 px-4 py-3">@foreach([10,25,50,100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach</select><button type="button" wire:click="resetFilters" class="rounded-xl border border-gray-300 px-4 py-3 font-semibold">{{ __('Request::request.reset') }}</button></div>
    <div wire:loading.class="opacity-60" class="grid gap-3">
        @forelse($tasks as $task)
            @php($request = $task->run->requestInstance)
            @php($sla = \Modules\Request\Support\RequestTaskSlaPresenter::present($task))
            @php($slaClasses = match($sla['state'] ?? null) {'suspended' => 'border-red-200 bg-red-50 text-red-800', 'grace' => 'border-orange-200 bg-orange-50 text-orange-800', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800', default => 'border-emerald-200 bg-emerald-50 text-emerald-800'})
            <a href="{{ route('request.show', $request->public_id) }}" class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <div class="flex flex-wrap justify-between gap-2"><span class="font-mono text-xs text-gray-500">{{ $request->request_number }}</span><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ $task->stage_name_snapshot }}</span></div>
                <h2 class="mt-2 font-bold text-gray-900">{{ $request->title_snapshot }}</h2>
                <p class="mt-2 text-sm text-gray-600">{{ __('Request::request.inbox.submitted_at') }} {{ optional($request->submitted_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                @if($sla)<div class="mt-3 flex flex-wrap items-center gap-2 text-xs"><span class="rounded-full border px-2.5 py-1 font-semibold {{ $slaClasses }}">{{ $sla['label'] }}</span><span class="font-medium text-gray-700">{{ $sla['detail'] }}</span><span class="text-gray-500">Hạn xử lý: <time datetime="{{ $sla['deadline_iso'] }}">{{ $sla['deadline'] }}</time></span></div>@endif
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600">{{ __('Request::request.inbox.empty') }}</div>
        @endforelse
    </div>
    {{ $tasks->links() }}
</div>

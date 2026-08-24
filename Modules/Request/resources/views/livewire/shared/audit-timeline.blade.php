<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="request-audit-title">
    <h2 id="request-audit-title" class="text-lg font-bold text-gray-900">{{ __('Request::request.audit_timeline') }}</h2>
    <ol class="mt-4 space-y-3">
        @forelse($events as $event)<li class="border-l-2 border-indigo-200 pl-4"><p class="text-sm font-medium text-gray-900">{{ $event->event_key }}</p><div class="mt-1 flex flex-wrap gap-3 text-xs text-gray-500"><time datetime="{{ $event->occurred_at?->toIso8601String() }}">{{ $event->occurred_at?->diffForHumans() }}</time><span>{{ __('Request::request.user_reference', ['id' => $event->actor_user_id]) }}</span><span class="font-mono">{{ $event->correlation_id }}</span></div></li>@empty<li class="text-sm text-gray-500">{{ __('Request::request.no_audit_events') }}</li>@endforelse
    </ol>
    <div class="mt-4">{{ $events->links() }}</div>
</section>

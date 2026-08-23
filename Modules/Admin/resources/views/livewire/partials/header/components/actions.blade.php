@php
    $customActions = (array) data_get($actions, 'items', []);
    $notification = (array) data_get($actions, 'notification', []);
    $primaryActions = array_values(array_filter($customActions, fn ($action) => ($action['priority'] ?? 'secondary') === 'primary'));
    $secondaryActions = array_values(array_filter($customActions, fn ($action) => ($action['priority'] ?? 'secondary') !== 'primary'));
    $mobileOverflow = (bool) data_get($actions, 'mobile_overflow', true) && (bool) data_get($actions, 'overflow_secondary_actions', true) && count($secondaryActions) > 0;
@endphp

<div class="flex shrink-0 items-center gap-1.5 sm:gap-2" data-admin-header-actions>
    @if ((bool) data_get($actions, 'notifications', true))
        <div data-admin-system-action="notifications">
            @if (data_get($notification, 'behavior', 'dropdown') === 'link' && data_get($notification, 'url'))
                <a href="{{ data_get($notification, 'url') }}" target="{{ data_get($notification, 'target', '_self') }}"
                    @if (data_get($notification, 'target', '_self') === '_blank') rel="noopener noreferrer" @endif
                    class="group relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" aria-label="Xem thông báo">
                    <i class="{{ data_get($notification, 'icon', 'fa-regular fa-bell') }} text-base" aria-hidden="true"></i>
                    <span class="absolute right-2.5 top-2 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white" aria-hidden="true"></span>
                </a>
            @else
                @livewire('admin.partials.header-notifications', ['icon' => data_get($notification, 'icon', 'fa-regular fa-bell')])
            @endif
        </div>
    @endif

    @foreach ($primaryActions as $action)
        <a href="{{ $action['url'] }}" target="{{ $action['target'] ?? '_self' }}" @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif title="{{ $action['title'] }}" aria-label="{{ $action['title'] }}" data-admin-header-action-priority="primary" class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/10"><i class="{{ $action['icon'] }} text-sm" aria-hidden="true"></i>@if (! empty($action['badge']))<span class="absolute right-0.5 top-0.5 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[9px] font-bold leading-4 text-white">{{ $action['badge'] }}</span>@endif</a>
    @endforeach

    @foreach ($secondaryActions as $action)
        <a href="{{ $action['url'] }}" target="{{ $action['target'] ?? '_self' }}" @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif title="{{ $action['title'] }}" aria-label="{{ $action['title'] }}" data-admin-header-action-priority="secondary" class="relative h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 {{ $mobileOverflow ? 'hidden sm:inline-flex' : 'inline-flex' }}"><i class="{{ $action['icon'] }} text-sm" aria-hidden="true"></i>@if (! empty($action['badge']))<span class="absolute right-0.5 top-0.5 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[9px] font-bold leading-4 text-white">{{ $action['badge'] }}</span>@endif</a>
    @endforeach

    @if ($mobileOverflow)
        <div class="relative sm:hidden" x-data="{ open: false }" data-admin-header-mobile-overflow>
            <button type="button" @click="open = !open" @keydown.escape.window="open = false" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" aria-label="Thêm thao tác" :aria-expanded="open.toString()"><i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i></button>
            <div x-cloak x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10">
                @foreach ($secondaryActions as $action)
                    <a href="{{ $action['url'] }}" target="{{ $action['target'] ?? '_self' }}" @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-950"><i class="{{ $action['icon'] }} w-4 text-center text-xs text-slate-400" aria-hidden="true"></i><span class="min-w-0 flex-1 truncate">{{ $action['title'] }}</span>@if (! empty($action['badge']))<span class="rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-600">{{ $action['badge'] }}</span>@endif</a>
                @endforeach
            </div>
        </div>
    @endif
</div>

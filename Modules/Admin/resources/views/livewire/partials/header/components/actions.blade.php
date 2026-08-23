@php
    $customActions = (array) data_get($actions, 'items', []);
@endphp

<div class="flex shrink-0 items-center gap-1.5 sm:gap-2" data-admin-header-actions>
    @if ((bool) data_get($actions, 'notifications', true))
        <div data-admin-system-action="notifications">
            @livewire('admin.partials.header-notifications')
        </div>
    @endif

    @foreach ($customActions as $action)
        <a
            href="{{ $action['url'] }}"
            target="{{ $action['target'] ?? '_self' }}"
            @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
            title="{{ $action['title'] }}"
            aria-label="{{ $action['title'] }}"
            data-admin-header-action-priority="{{ $action['priority'] ?? 'secondary' }}"
            class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
        >
            <i class="{{ $action['icon'] }} text-sm" aria-hidden="true"></i>
            @if (! empty($action['badge']))
                <span class="absolute right-0.5 top-0.5 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[9px] font-bold leading-4 text-white">
                    {{ $action['badge'] }}
                </span>
            @endif
        </a>
    @endforeach
</div>

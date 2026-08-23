@php
    $adminFooterContext = app(\Modules\Admin\Services\AdminFooterService::class)->context();
    $footerPresentation = $adminFooterContext['presentation'] ?? [];
    $footerAlignment = $footerPresentation['alignment'] ?? 'split';
    $footerCompact = (bool) ($footerPresentation['compact'] ?? true);
    $footerBackground = ($footerPresentation['background'] ?? 'system') === 'transparent' ? 'transparent' : 'var(--admin-surface-raised)';
    $footerBorder = ($footerPresentation['divider'] ?? 'subtle') === 'none' ? 'transparent' : 'var(--admin-border-subtle)';
@endphp

@if ($adminFooterContext['enabled'])
    <footer
        class="shrink-0 text-xs text-[var(--admin-text-muted)]"
        style="background: {{ $footerBackground }}; border-top: 1px solid {{ $footerBorder }};"
        data-admin-footer
    >
        <div @class([
            'mx-auto flex w-full max-w-screen-2xl gap-x-6 gap-y-2 px-4 sm:px-6 lg:px-8',
            'py-2.5' => $footerCompact,
            'py-4' => ! $footerCompact,
            'flex-col items-center justify-center text-center sm:flex-row' => $footerAlignment === 'center',
            'flex-col items-start justify-between sm:flex-row sm:items-center' => $footerAlignment === 'split',
        ])>
            @foreach ($adminFooterContext['components'] as $footerComponent)
                @include($footerComponent['view'], $footerComponent['data'])
            @endforeach
        </div>
    </footer>
@endif

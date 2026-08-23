@php
    $adminFooterContext = app(\Modules\Admin\Services\AdminFooterService::class)->context();
@endphp

@if ($adminFooterContext['enabled'])
    <footer class="border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4">
            @foreach ($adminFooterContext['components'] as $footerComponent)
                @include($footerComponent['view'], $footerComponent['data'])
            @endforeach
        </div>
    </footer>
@endif

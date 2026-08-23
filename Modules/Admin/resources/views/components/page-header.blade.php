@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

<header {{ $attributes->class(['mb-6']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $eyebrow }}</p>
            @endif

            <h1 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $title }}</h1>

            @if ($description)
                <p class="mt-1.5 max-w-3xl text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @isset($toolbar)
        <div class="mt-4 flex min-h-10 flex-col gap-3 border-t border-slate-200/80 pt-4 sm:flex-row sm:items-center sm:justify-between">
            {{ $toolbar }}
        </div>
    @endisset
</header>

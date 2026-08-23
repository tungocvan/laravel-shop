@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->class(['min-w-0']) }}>
    @if ($title || $description || isset($actions))
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                @endif

                @if ($description)
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>

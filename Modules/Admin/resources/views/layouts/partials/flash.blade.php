@php
    $flashMessages = collect([
        'success' => session('success') ?? session('status'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ])->filter();

    $flashClasses = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'error' => 'border-rose-200 bg-rose-50 text-rose-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'info' => 'border-sky-200 bg-sky-50 text-sky-800',
    ];
@endphp

@if ($flashMessages->isNotEmpty())
    <div class="mb-5 space-y-3" aria-live="polite">
        @foreach ($flashMessages as $type => $message)
            <div class="rounded-lg border px-4 py-3 text-sm font-medium {{ $flashClasses[$type] ?? $flashClasses['info'] }}">
                {{ $message }}
            </div>
        @endforeach
    </div>
@endif

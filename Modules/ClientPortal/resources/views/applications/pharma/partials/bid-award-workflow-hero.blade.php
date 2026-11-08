<header class="flex min-h-16 items-center gap-3 border-b border-slate-200 bg-white pb-4">
    <a href="{{ $backUrl }}" aria-label="Quay lại kết quả trúng thầu" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-700 shadow-sm">←</a>
    <div class="min-w-0">
        <h1 class="truncate text-lg font-black text-slate-950">{{ $heading }}</h1>
        <p class="truncate text-xs text-slate-500">{{ $subtitle }}</p>
    </div>
</header>
<section class="rounded-[28px] bg-slate-950 p-5 text-white sm:p-6">
    <p class="text-[11px] font-black uppercase tracking-[.18em] text-slate-300">{{ $eyebrow }}</p>
    <h2 class="mt-1 text-2xl font-black">{{ $heading }}</h2>
    <p class="mt-2 text-sm text-slate-300">{{ $subtitle }} · {{ $reference }}</p>
</section>

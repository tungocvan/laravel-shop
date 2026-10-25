@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, min($current - 2, max(1, $last - 4)));
        $end = min($last, $start + 4);
    @endphp
    <nav class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm" aria-label="{{ $paginationLabel }}">
        <p class="text-slate-600">Trang <strong class="text-slate-900">{{ $current }} / {{ $last }}</strong> · {{ number_format($paginator->total(), 0, ',', '.') }} công nợ</p>
        <div class="flex flex-wrap items-center gap-1.5">
            <button type="button" wire:click="gotoPage(1, '{{ $pageName }}')" @disabled($current === 1) class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-40">Đầu</button>
            <button type="button" wire:click="previousPage('{{ $pageName }}')" @disabled($current === 1) aria-label="Trang trước" class="min-h-10 min-w-10 rounded-lg border border-slate-300 bg-white px-3 text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-40">‹</button>
            @if ($start > 1)
                <button type="button" wire:click="gotoPage(1, '{{ $pageName }}')" class="min-h-10 min-w-10 rounded-lg border border-slate-300 bg-white px-3 text-slate-700 hover:bg-slate-50">1</button>
                @if ($start > 2) <span class="px-1 text-slate-500" aria-hidden="true">…</span> @endif
            @endif
            @for ($page = $start; $page <= $end; $page++)
                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" @if ($page === $current) aria-current="page" @endif class="min-h-10 min-w-10 rounded-lg border px-3 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 {{ $page === $current ? 'border-indigo-700 bg-indigo-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">{{ $page }}</button>
            @endfor
            @if ($end < $last)
                @if ($end < $last - 1) <span class="px-1 text-slate-500" aria-hidden="true">…</span> @endif
                <button type="button" wire:click="gotoPage({{ $last }}, '{{ $pageName }}')" class="min-h-10 min-w-10 rounded-lg border border-slate-300 bg-white px-3 text-slate-700 hover:bg-slate-50">{{ $last }}</button>
            @endif
            <button type="button" wire:click="nextPage('{{ $pageName }}')" @disabled($current === $last) aria-label="Trang sau" class="min-h-10 min-w-10 rounded-lg border border-slate-300 bg-white px-3 text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-40">›</button>
            <button type="button" wire:click="gotoPage({{ $last }}, '{{ $pageName }}')" @disabled($current === $last) class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-40">Cuối</button>
        </div>
    </nav>
@endif

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang hồ sơ tuyển sinh" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-500">
            Hiển thị <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
            trên tổng số <span class="font-semibold text-gray-900">{{ $paginator->total() }}</span> hồ sơ
        </div>

        <div class="flex flex-wrap items-center justify-end gap-1.5">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Trang trước" class="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 text-sm text-gray-400">‹</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" aria-label="Trang trước" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 text-sm text-gray-700 hover:border-indigo-300 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100">‹</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-indigo-600 bg-indigo-600 px-3 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-indigo-300 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" aria-label="Trang sau" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-2.5 text-sm text-gray-700 hover:border-indigo-300 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100">›</button>
            @else
                <span aria-disabled="true" aria-label="Trang sau" class="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 text-sm text-gray-400">›</span>
            @endif
        </div>
    </nav>
@endif

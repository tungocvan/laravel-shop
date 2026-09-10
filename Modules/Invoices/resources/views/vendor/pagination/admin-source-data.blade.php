@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang dữ liệu nguồn" class="flex items-center justify-between gap-3">
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-10 items-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400">Trước</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">Trước</button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">Sau</button>
            @else
                <span class="inline-flex min-h-10 items-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400">Sau</span>
            @endif
        </div>

        <div class="hidden w-full items-center justify-end sm:flex">
            <div class="inline-flex flex-wrap items-center justify-end gap-1" aria-label="Các trang">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400">‹</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Trang trước" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">‹</button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-10 min-w-10 items-center justify-center px-2 text-sm font-medium text-gray-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-indigo-600 bg-indigo-600 px-3 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Trang sau" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">›</button>
                @else
                    <span aria-disabled="true" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400">›</span>
                @endif
            </div>
        </div>
    </nav>
@endif

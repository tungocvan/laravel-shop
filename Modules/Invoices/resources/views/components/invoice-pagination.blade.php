@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang hóa đơn" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-500">
            Hiển thị
            <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
            –
            <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
            trong
            <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
            hóa đơn
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400">Trước</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50">Trước</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-sm text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white shadow-sm">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50">Sau</button>
            @else
                <span class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400">Sau</span>
            @endif
        </div>
    </nav>
@endif

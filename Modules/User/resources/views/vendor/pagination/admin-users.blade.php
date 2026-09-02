@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang danh sách nhân sự" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-600">
            Hiển thị
            <span class="font-semibold text-gray-900">{{ $paginator->firstItem() }}</span>
            –
            <span class="font-semibold text-gray-900">{{ $paginator->lastItem() }}</span>
            trong
            <span class="font-semibold text-gray-900">{{ $paginator->total() }}</span>
            nhân sự
        </div>

        <div class="inline-flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex cursor-not-allowed items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400">Trước</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">Trước</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center px-2 py-2 text-sm text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex min-w-10 items-center justify-center rounded-lg border border-indigo-600 bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="inline-flex min-w-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">Sau</button>
            @else
                <span aria-disabled="true" class="inline-flex cursor-not-allowed items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400">Sau</span>
            @endif
        </div>
    </nav>
@endif

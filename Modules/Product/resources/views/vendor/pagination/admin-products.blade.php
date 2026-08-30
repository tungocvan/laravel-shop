@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between gap-4">
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex cursor-default items-center rounded-md border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400">Trước</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">Trước</button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">Sau</button>
            @else
                <span class="ml-3 inline-flex cursor-default items-center rounded-md border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400">Sau</span>
            @endif
        </div>

        <div class="hidden flex-1 items-center justify-between sm:flex">
            <div>
                <p class="text-sm text-gray-600">
                    Hiển thị
                    <span class="font-semibold text-gray-900">{{ $paginator->firstItem() }}</span>
                    đến
                    <span class="font-semibold text-gray-900">{{ $paginator->lastItem() }}</span>
                    trong
                    <span class="font-semibold text-gray-900">{{ $paginator->total() }}</span>
                    sản phẩm
                </p>
            </div>

            <div>
                <span class="isolate inline-flex rounded-md shadow-sm">
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Trang trước" class="relative inline-flex cursor-default items-center rounded-l-md border border-gray-200 bg-gray-50 px-2 py-2 text-gray-400">
                            <span class="sr-only">Trang trước</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M11.78 14.78a.75.75 0 01-1.06 0l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 111.06 1.06L8.06 10l3.72 3.72a.75.75 0 010 1.06z" clip-rule="evenodd" /></svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Trang trước" class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-gray-500 transition hover:z-10 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:z-20 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <span class="sr-only">Trang trước</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M11.78 14.78a.75.75 0 01-1.06 0l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 111.06 1.06L8.06 10l3.72 3.72a.75.75 0 010 1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="relative inline-flex items-center border-y border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="relative z-10 inline-flex items-center border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:z-10 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:z-20 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Đến trang {{ $page }}">{{ $page }}</button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Trang sau" class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-gray-500 transition hover:z-10 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:z-20 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <span class="sr-only">Trang sau</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 11-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="Trang sau" class="relative inline-flex cursor-default items-center rounded-r-md border border-gray-200 bg-gray-50 px-2 py-2 text-gray-400">
                            <span class="sr-only">Trang sau</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 11-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif

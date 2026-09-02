@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-gray-500">
            Hiển thị <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
            đến <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
            trong <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span> bản ghi
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-400">Trước</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">Trước</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex min-h-9 min-w-9 items-center justify-center px-2 text-xs font-semibold text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border border-indigo-600 bg-indigo-600 px-3 text-xs font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">Sau</a>
            @else
                <span aria-disabled="true" class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-400">Sau</span>
            @endif
        </div>
    </nav>
@endif

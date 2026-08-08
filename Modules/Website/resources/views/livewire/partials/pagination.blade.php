<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center gap-2">

            {{-- Nút Previous --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                    &laquo; Trước
                </span>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled"
                        class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100 transition">
                    &laquo; Trước
                </button>
            @endif

            {{-- Các số trang --}}
            @foreach ($elements as $element)

                {{-- Dấu ba chấm "..." --}}
                @if (is_string($element))
                    <span class="px-3 py-2 text-gray-400 border border-gray-300 bg-gray-50 rounded">{{ $element }}</span>
                @endif

                {{-- Mảng các link số trang --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            {{-- STATE ACTIVE (Trang hiện tại) --}}
                            <span class="px-3 py-2 text-white bg-blue-600 border border-blue-600 rounded shadow-sm font-bold">
                                {{ $page }}
                            </span>
                        @else
                            {{-- STATE BÌNH THƯỜNG --}}
                            <button wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled"
                                    class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100 hover:text-blue-600 transition">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Nút Next --}}
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled"
                        class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-100 transition">
                    Sau &raquo;
                </button>
            @else
                <span class="px-3 py-2 text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                    Sau &raquo;
                </span>
            @endif
        </nav>
    @endif
</div>

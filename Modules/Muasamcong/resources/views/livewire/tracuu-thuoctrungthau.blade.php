<div class="space-y-5" @if(request()->filled('q')) wire:init="searchRecent(@js((string) request()->query('q')))" @endif>
    @php
        $syncedLookup = array_fill_keys($syncedSourceIds, true);
        $wishlistLookup = array_fill_keys($wishlistSourceIds, true);
        $canSyncPricing = auth('admin')->check() && auth('admin')->user()->can('muasamcong.pricing.sync');
        $canWishlistPricing = auth('admin')->check() && auth('admin')->user()->can('muasamcong.pricing.wishlist');
        $hasResultFilters = $medicineNameFilter !== '' || $activeIngredientFilter !== '' || $medicineGroupFilter !== '' || $winningCompanyFilter !== '';
    @endphp

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <form wire:submit="search" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="pricing-keyword" class="text-sm font-semibold text-gray-800">Tên thuốc, hoạt chất, mã TBMT hoặc công ty trúng thầu</label>
                <p class="mt-1 text-xs text-gray-500">Từ khóa đã tra cứu sẽ ưu tiên đọc kết quả đã lưu trên server. Chỉ dùng “Tìm kiếm mới” khi cần lấy lại dữ liệu từ API.</p>
                <input id="pricing-keyword" type="search" wire:model="keyword" placeholder="Ví dụ: Gourcuff-2,5, Ibuprofen, IB2500539527, INAFO" class="mt-3 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @error('keyword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="search,searchRecent" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="search,searchRecent">Tra cứu</span><span wire:loading wire:target="search,searchRecent">Đang tải...</span>
            </button>
            <button type="button" wire:click="refreshSearch" wire:loading.attr="disabled" wire:target="refreshSearch" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-amber-300 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:opacity-60">
                <span wire:loading.remove wire:target="refreshSearch">Tìm kiếm mới</span><span wire:loading wire:target="refreshSearch">Đang gọi API...</span>
            </button>
        </form>

        @if ($recentSearches !== [])
            <div class="mt-5 border-t border-gray-100 pt-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tra cứu gần đây</p>
                        <p class="mt-0.5 text-xs text-gray-400">Mở lại bằng tải trang mới để tránh gửi toàn bộ dữ liệu lớn qua Livewire.</p>
                    </div>
                    <form method="POST" action="{{ route('muasamcong.pricing.history.clear') }}" onsubmit="return confirm('Xóa toàn bộ lịch sử tra cứu đã lưu? Dữ liệu đồng bộ và Wishlist không bị xóa.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">Xóa toàn bộ</button>
                    </form>
                </div>
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($recentSearches as $recent)
                        <div class="relative min-w-52 rounded-xl border border-gray-200 bg-gray-50 hover:border-indigo-200 hover:bg-indigo-50/50">
                            <a href="{{ route('muasamcong.index', ['q' => $recent['keyword']]) }}" class="block px-3 py-2.5 pr-10 text-left">
                                <p class="truncate text-sm font-semibold text-gray-800">{{ $recent['keyword'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $recent['loaded_total'] }} kết quả · {{ $recent['searched_at'] ? \Illuminate\Support\Carbon::parse($recent['searched_at'])->format('d/m/Y H:i') : '—' }}</p>
                            </a>
                            <form method="POST" action="{{ route('muasamcong.pricing.history.destroy') }}" class="absolute right-1.5 top-1.5" onsubmit="return confirm('Xóa tra cứu {{ addslashes($recent['keyword']) }}?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="keyword" value="{{ $recent['keyword'] }}">
                                <button type="submit" title="Xóa tra cứu" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 bg-white text-sm font-bold text-red-600 hover:bg-red-50">×</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if ($canWishlistPricing && $wishlistItems !== [])
        <section class="rounded-2xl border border-rose-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-rose-100 px-4 py-3 sm:px-5">
                <div><p class="text-sm font-bold text-gray-900">Wishlist thuốc cần theo dõi</p><p class="mt-0.5 text-xs text-gray-500">Lưu nhanh kết quả quan trọng và tìm lại bằng một lần bấm.</p></div>
                <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">♥ {{ count($wishlistItems) }}</span>
            </div>
            <div class="flex gap-3 overflow-x-auto px-4 py-4 sm:px-5">
                @foreach ($wishlistItems as $wishlistItem)
                    <button type="button" wire:click="searchWishlist(@js($wishlistItem['medicine_name'] ?: $wishlistItem['search_keyword']))" class="min-w-64 rounded-xl border border-gray-200 bg-gray-50 p-3 text-left hover:border-rose-200 hover:bg-rose-50/50">
                        <div class="flex items-start gap-2"><span class="text-lg text-rose-500">♥</span><div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-900">{{ $wishlistItem['medicine_name'] ?: $wishlistItem['search_keyword'] }}</p><p class="mt-1 truncate text-xs text-gray-500">{{ $wishlistItem['active_ingredient'] ?: 'Chưa có hoạt chất' }}{{ $wishlistItem['strength'] ? ' · '.$wishlistItem['strength'] : '' }}</p></div></div>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    @if ($wishlistMessage !== '')<div role="status" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $wishlistMessage }}</div>@endif
    @if ($error)<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>@endif
    @if ($syncMessage !== '')<div role="status" class="rounded-xl border px-4 py-3 text-sm {{ $syncStatus === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($syncStatus === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-red-200 bg-red-50 text-red-700') }}">{{ $syncMessage }}</div>@endif

    <div wire:loading.flex wire:target="search,searchRecent,refreshSearch,searchWishlist" class="items-center justify-center rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm">Đang tải dữ liệu tra cứu...</div>

    <div wire:loading.remove wire:target="search,searchRecent,refreshSearch,searchWishlist" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if ($results !== [])
            <div class="border-b border-gray-200 bg-gray-50/70 px-4 py-4">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-gray-900">Kết quả tra cứu</p>
                            <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $filteredResultCount }} / {{ count($results) }} đã tải</span>
                            @if ($sourceTotal > count($results))<span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Nguồn báo {{ $sourceTotal }} kết quả</span>@endif
                            @if ($searchDataSource === 'database')<span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Từ database</span>@elseif ($searchDataSource === 'api')<span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Mới từ API</span>@endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Hiển thị 20 dòng/trang. Checkbox đã chọn được giữ nguyên khi chuyển trang.</p>
                        @if ($searchSnapshotAt)<p class="mt-1 text-xs font-medium text-gray-600">Thời gian tra cứu nguồn gần nhất: {{ \Illuminate\Support\Carbon::parse($searchSnapshotAt)->format('d/m/Y H:i:s') }}</p>@endif
                        @if ($sourcePartial)<p class="mt-1 text-xs font-medium text-amber-700">Cảnh báo: chưa tải hết toàn bộ kết quả từ nguồn. Hãy bấm “Tìm kiếm mới” để thử lại.</p>@endif
                    </div>
                    @if ($canSyncPricing)
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="toggleSelectedSummary" @disabled(count($selectedSourceIds) === 0) class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">Đã chọn ({{ count($selectedSourceIds) }})</button>
                            <button type="button" wire:click="selectAllUnsynced" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Chọn tất cả kết quả</button>
                            @if (count($selectedSourceIds) > 0)<button type="button" wire:click="clearSelection" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-600">Bỏ chọn tất cả</button>@endif
                            <form method="POST" action="{{ route('muasamcong.pricing.export-selected') }}" class="inline-flex">
                                @csrf
                                <input type="hidden" name="keyword" value="{{ $keyword }}">
                                @foreach ($selectedSourceIds as $selectedSourceId)
                                    <input type="hidden" name="selected_ids[]" value="{{ $selectedSourceId }}">
                                @endforeach
                                <button type="submit" @disabled(count($selectedSourceIds) === 0) class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40">Xuất Excel ({{ count($selectedSourceIds) }})</button>
                            </form>
                            <button type="button" wire:click="syncSelected" wire:loading.attr="disabled" @disabled(count($selectedSourceIds) === 0) class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Đồng bộ ({{ count($selectedSourceIds) }})</button>
                        </div>
                    @endif
                </div>

                @if ($canSyncPricing && $showSelectedSummary && count($selectedSourceIds) > 0)
                    <div class="mt-4 rounded-2xl border border-indigo-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-indigo-100 px-4 py-3">
                            <div><p class="text-sm font-bold text-gray-900">Các thuốc đã chọn</p><p class="text-xs text-gray-500">Lựa chọn được giữ xuyên suốt các trang cho đến khi đồng bộ hoặc bỏ chọn.</p></div>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ count($selectedSourceIds) }} bản ghi</span>
                        </div>
                        <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                            @foreach ($selectedItems as $selectedItem)
                                <div class="flex items-center justify-between gap-3 px-4 py-3">
                                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-900">{{ $selectedItem['tenThuoc'] ?: 'Chưa có tên thuốc' }}</p><p class="mt-0.5 truncate text-xs text-gray-500">{{ $selectedItem['tenHoatChat'] ?: 'Chưa có hoạt chất' }}{{ $selectedItem['nhomThuoc'] ? ' · '.$selectedItem['nhomThuoc'] : '' }}{{ $selectedItem['maTbmt'] ? ' · '.$selectedItem['maTbmt'] : '' }}</p></div>
                                    <button type="button" wire:click="removeSelected('{{ $selectedItem['id'] }}')" class="shrink-0 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700">Bỏ chọn</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div><label class="mb-1 block text-xs font-semibold text-gray-600">Tên thuốc</label><x-search wire:model.live.debounce.250ms="medicineNameFilter" placeholder="Ví dụ: Acetylcystein..." /></div>
                    <div><label class="mb-1 block text-xs font-semibold text-gray-600">Hoạt chất</label><x-search wire:model.live.debounce.250ms="activeIngredientFilter" placeholder="Ví dụ: Piracetam..." /></div>
                    <div><label class="mb-1 block text-xs font-semibold text-gray-600">Nhóm thuốc</label><x-search wire:model.live.debounce.250ms="medicineGroupFilter" placeholder="Ví dụ: N2, Nhóm 4..." /></div>
                    <div><label class="mb-1 block text-xs font-semibold text-gray-600">Đơn vị trúng thầu</label><x-search wire:model.live.debounce.250ms="winningCompanyFilter" placeholder="Ví dụ: INAFO, NAM SƠN..." /></div>
                </div>

                @if ($hasResultFilters)
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-3 py-2">
                        <p class="text-xs text-indigo-700">Đang lọc <span class="font-semibold">{{ $filteredResultCount }}</span> / {{ count($results) }} kết quả đã tải.</p>
                        <button type="button" wire:click="clearResultFilters" class="shrink-0 text-xs font-semibold text-indigo-700 hover:text-indigo-900">Xóa bộ lọc</button>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1600px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            @if ($canWishlistPricing)<th class="w-12 px-3 py-3 text-center">♥</th>@endif
                            @if ($canSyncPricing)
                                <th class="w-16 px-3 py-3 text-center">
                                    <label class="inline-flex cursor-pointer flex-col items-center gap-1 normal-case tracking-normal" title="Chọn/bỏ chọn tất cả bản ghi chưa đồng bộ trên trang hiện tại">
                                        <input type="checkbox" wire:click="toggleCurrentPageSelection" @checked($currentPageAllSelected) @disabled($currentPageSelectableCount === 0) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="whitespace-nowrap text-[10px] text-gray-500">{{ $currentPageSelectedCount }}/{{ $currentPageSelectableCount }}</span>
                                    </label>
                                </th>
                            @endif
                            <th class="w-14 px-3 py-3 text-center">STT</th><th class="min-w-40 px-4 py-3">Thuốc</th><th class="min-w-32 px-4 py-3">Nhóm thuốc</th><th class="min-w-40 px-4 py-3">Hoạt chất</th><th class="min-w-40 px-4 py-3">Nồng độ / hàm lượng</th><th class="min-w-28 px-4 py-3 text-right">Giá trúng thầu</th><th class="min-w-24 px-4 py-3 text-right">Số lượng</th><th class="min-w-64 px-4 py-3">Đơn vị trúng thầu</th><th class="min-w-64 px-4 py-3">Chủ đầu tư / Bên mời thầu</th><th class="min-w-32 px-4 py-3">Mã TBMT</th><th class="w-24 px-4 py-3 text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($displayResults as $item)
                            @php
                                $sourceId = is_string($item['id'] ?? null) ? $item['id'] : '';
                                $isSynced = $sourceId !== '' && isset($syncedLookup[$sourceId]);
                                $isWishlisted = $sourceId !== '' && isset($wishlistLookup[$sourceId]);
                                $winningNames = array_values(array_filter(is_array($item['winningName'] ?? null) ? $item['winningName'] : [], fn ($name) => is_scalar($name) && trim((string) $name) !== ''));
                            @endphp
                            <tr class="align-top {{ $isSynced ? 'bg-emerald-50/30' : 'hover:bg-indigo-50/30' }}">
                                @if($canWishlistPricing)<td class="px-3 py-4 text-center">@if($sourceId)<button wire:click="toggleWishlist('{{ $sourceId }}')" class="h-9 w-9 rounded-full border {{ $isWishlisted ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-gray-200 text-gray-400' }}">{{ $isWishlisted ? '♥' : '♡' }}</button>@endif</td>@endif
                                @if($canSyncPricing)<td class="px-3 py-4 text-center">@if($sourceId)<input type="checkbox" value="{{ $sourceId }}" wire:model.live="selectedSourceIds" @disabled($isSynced) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">@endif</td>@endif
                                <td class="px-3 py-4 text-center text-gray-500">{{ $resultOffset + $loop->iteration }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-950">{{ $item['tenThuoc'] ?? '-' }}</td>
                                <td class="px-4 py-4">{{ $item['nhomThuoc'] ?? '-' }}</td>
                                <td class="px-4 py-4">{{ $item['tenHoatChat'] ?? '-' }}</td>
                                <td class="px-4 py-4">{{ $item['nongDo'] ?? '-' }}</td>
                                <td class="px-4 py-4 text-right font-semibold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-4 text-right">{{ is_numeric($item['soLuong'] ?? null) ? number_format((float) $item['soLuong'], 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-4">@forelse($winningNames as $winningName)<div class="font-semibold text-emerald-700">{{ $winningName }}</div>@empty<span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500">Nguồn không cung cấp</span>@endforelse</td>
                                <td class="px-4 py-4">{{ $item['tenCdtBmt'] ?? '-' }}</td>
                                <td class="px-4 py-4"><span class="rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs">{{ $item['maTbmt'] ?? '-' }}</span></td>
                                <td class="px-4 py-4 text-center">@if($sourceId)<button wire:click="openDetail('{{ $sourceId }}')" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Xem</button>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="px-5 py-10 text-center text-gray-500">Không có kết quả phù hợp với các bộ lọc đang chọn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($resultPageCount > 1)
                <div class="flex flex-col gap-3 border-t border-gray-200 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">Trang <span class="font-semibold text-gray-800">{{ $resultPage }}</span> / {{ $resultPageCount }} · {{ $filteredResultCount }} kết quả · <span class="font-semibold text-indigo-700">{{ count($selectedSourceIds) }} đã chọn</span></p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" wire:click="previousResultPage" @disabled($resultPage <= 1) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40">← Trước</button>
                        @php $pageStart = max(1, $resultPage - 2); $pageEnd = min($resultPageCount, $resultPage + 2); @endphp
                        @if ($pageStart > 1)<button type="button" wire:click="goToResultPage(1)" class="h-9 min-w-9 rounded-lg border border-gray-300 bg-white px-2 text-xs font-semibold text-gray-700">1</button>@if ($pageStart > 2)<span class="px-1 text-gray-400">…</span>@endif @endif
                        @for ($page = $pageStart; $page <= $pageEnd; $page++)<button type="button" wire:click="goToResultPage({{ $page }})" class="h-9 min-w-9 rounded-lg border px-2 text-xs font-semibold {{ $page === $resultPage ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700' }}">{{ $page }}</button>@endfor
                        @if ($pageEnd < $resultPageCount)@if ($pageEnd < $resultPageCount - 1)<span class="px-1 text-gray-400">…</span>@endif<button type="button" wire:click="goToResultPage({{ $resultPageCount }})" class="h-9 min-w-9 rounded-lg border border-gray-300 bg-white px-2 text-xs font-semibold text-gray-700">{{ $resultPageCount }}</button>@endif
                        <button type="button" wire:click="nextResultPage" @disabled($resultPage >= $resultPageCount) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40">Sau →</button>
                    </div>
                </div>
            @endif
        @else
            <div class="p-10 text-center"><p class="text-sm font-medium text-gray-700">Chưa có dữ liệu tra cứu</p><p class="mt-1 text-sm text-gray-500">Nhập tên thuốc, hoạt chất, mã TBMT hoặc công ty trúng thầu để bắt đầu.</p></div>
        @endif
    </div>

    @include('Muasamcong::livewire.partials.pricing-detail-modal')
</div>
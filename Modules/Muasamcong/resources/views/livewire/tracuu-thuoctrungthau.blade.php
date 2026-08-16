<div class="space-y-5">
    @php
        $syncedLookup = array_fill_keys($syncedSourceIds, true);
        $wishlistLookup = array_fill_keys($wishlistSourceIds, true);
        $canSyncPricing = auth('admin')->check() && auth('admin')->user()->can('muasamcong.pricing.sync');
        $canWishlistPricing = auth('admin')->check() && auth('admin')->user()->can('muasamcong.pricing.wishlist');
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <form wire:submit="search" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="pricing-keyword" class="text-sm font-semibold text-gray-800">Tên thuốc, hoạt chất hoặc mã TBMT</label>
                <p class="mt-1 text-xs text-gray-500">Tra cứu dữ liệu đơn giá trúng thầu thuốc từ Hệ thống mạng đấu thầu quốc gia.</p>
                <input id="pricing-keyword" type="search" wire:model="keyword" placeholder="Ví dụ: Gourcuff-2,5, Unafen, Ibuprofen, IB2500539527" class="mt-3 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @error('keyword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="search" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="search">Tìm kiếm</span><span wire:loading wire:target="search">Đang tìm...</span>
            </button>
        </form>
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

    <div wire:loading.flex wire:target="search,searchWishlist" class="items-center justify-center rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm">Đang tải đầy đủ dữ liệu từ Hệ thống mạng đấu thầu quốc gia...</div>

    <div wire:loading.remove wire:target="search,searchWishlist" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if ($results !== [])
            <div class="border-b border-gray-200 bg-gray-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-gray-900">Kết quả tra cứu</p>
                            <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $filteredResultCount }} / {{ count($results) }} đã tải</span>
                            @if ($sourceTotal > count($results))<span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Nguồn báo {{ $sourceTotal }} kết quả</span>@endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Hiển thị 20 dòng/trang. Tra cứu bằng mã TBMT sẽ tự tải các trang dữ liệu nguồn trước khi phân trang tại đây.</p>
                        @if ($sourcePartial)<p class="mt-1 text-xs font-medium text-amber-700">Cảnh báo: chưa tải hết toàn bộ kết quả từ nguồn. Hãy thử tìm kiếm lại.</p>@endif
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                        <div class="min-w-72">
                            <label class="mb-1 block text-xs font-semibold text-gray-600">Đơn vị trúng thầu</label>
                            <div class="relative"><input type="search" wire:model.live.debounce.250ms="winningCompanyFilter" placeholder="Ví dụ: INAFO, NAM SƠN..." class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@if ($winningCompanyFilter !== '')<button type="button" wire:click="$set('winningCompanyFilter', '')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">×</button>@endif</div>
                        </div>
                        @if ($canSyncPricing)<div class="flex flex-wrap items-center gap-2"><button type="button" wire:click="selectAllUnsynced" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Chọn tất cả chưa đồng bộ</button><button type="button" wire:click="syncSelected" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-60">Đồng bộ ({{ count($selectedSourceIds) }})</button></div>@endif
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1600px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-600"><tr>@if ($canWishlistPricing)<th class="w-12 px-3 py-3 text-center">♥</th>@endif @if ($canSyncPricing)<th class="w-12 px-3 py-3 text-center">Chọn</th>@endif<th class="w-14 px-3 py-3 text-center">STT</th><th class="min-w-40 px-4 py-3">Thuốc</th><th class="min-w-32 px-4 py-3">Nhóm thuốc</th><th class="min-w-40 px-4 py-3">Hoạt chất</th><th class="min-w-40 px-4 py-3">Nồng độ / hàm lượng</th><th class="min-w-28 px-4 py-3 text-right">Giá trúng thầu</th><th class="min-w-24 px-4 py-3 text-right">Số lượng</th><th class="min-w-64 px-4 py-3">Đơn vị trúng thầu</th><th class="min-w-64 px-4 py-3">Chủ đầu tư / Bên mời thầu</th><th class="min-w-32 px-4 py-3">Mã TBMT</th><th class="w-24 px-4 py-3 text-center">Chi tiết</th></tr></thead>
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
                                @if($canSyncPricing)<td class="px-3 py-4 text-center">@if($sourceId)<input type="checkbox" value="{{ $sourceId }}" wire:model="selectedSourceIds" @disabled($isSynced) class="rounded border-gray-300">@endif</td>@endif
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
                            <tr><td colspan="13" class="px-5 py-10 text-center text-gray-500">Không có kết quả phù hợp với đơn vị trúng thầu đang lọc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($resultPageCount > 1)
                <div class="flex flex-col gap-3 border-t border-gray-200 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">Trang <span class="font-semibold text-gray-800">{{ $resultPage }}</span> / {{ $resultPageCount }} · {{ $filteredResultCount }} kết quả</p>
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
            <div class="p-10 text-center"><p class="text-sm font-medium text-gray-700">Chưa có dữ liệu tra cứu</p><p class="mt-1 text-sm text-gray-500">Nhập tên thuốc, hoạt chất hoặc mã TBMT để bắt đầu.</p></div>
        @endif
    </div>

    @include('Muasamcong::livewire.partials.pricing-detail-modal')
</div>

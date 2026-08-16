<div class="space-y-5">
    @php
        $syncedLookup = array_fill_keys($syncedSourceIds, true);
        $canSyncPricing = auth('admin')->check() && auth('admin')->user()->can('muasamcong.pricing.sync');
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <form wire:submit="search" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="pricing-keyword" class="text-sm font-semibold text-gray-800">Tên thuốc, hoạt chất hoặc mã TBMT</label>
                <p class="mt-1 text-xs text-gray-500">Tra cứu dữ liệu đơn giá trúng thầu thuốc từ Hệ thống mạng đấu thầu quốc gia.</p>
                <input id="pricing-keyword" type="search" wire:model="keyword"
                    placeholder="Ví dụ: Unafen, Ibuprofen, IB2500029154"
                    class="mt-3 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @error('keyword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="search"
                class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="search">Tìm kiếm</span>
                <span wire:loading wire:target="search">Đang tìm...</span>
            </button>
        </form>
    </div>

    @if ($error)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    @if ($syncMessage !== '')
        <div role="status" class="rounded-xl border px-4 py-3 text-sm {{ $syncStatus === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($syncStatus === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-red-200 bg-red-50 text-red-700') }}">
            {{ $syncMessage }}
        </div>
    @endif

    <div wire:loading.flex wire:target="search" class="items-center justify-center rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm">
        Đang tải dữ liệu từ Hệ thống mạng đấu thầu quốc gia...
    </div>

    <div wire:loading.remove wire:target="search" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if ($results !== [])
            <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/70 px-4 py-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900">Kết quả tra cứu</p>
                        <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ count($results) }} kết quả</span>
                        @if ($syncedSourceIds !== [])
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ count($syncedSourceIds) }} đã đồng bộ</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Chọn các bản ghi chưa tồn tại để lưu vào cơ sở dữ liệu. Dữ liệu trùng theo ID nguồn sẽ tự động bị bỏ qua.</p>
                </div>

                @if ($canSyncPricing)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-600">Đã chọn: {{ count($selectedSourceIds) }}</span>
                        <button type="button" wire:click="selectAllUnsynced"
                            class="inline-flex min-h-9 items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            Chọn tất cả chưa đồng bộ
                        </button>
                        @if ($selectedSourceIds !== [])
                            <button type="button" wire:click="clearSelection"
                                class="inline-flex min-h-9 items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Bỏ chọn
                            </button>
                        @endif
                        <button type="button" wire:click="syncSelected" wire:loading.attr="disabled" wire:target="syncSelected"
                            class="inline-flex min-h-9 items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="syncSelected">Đồng bộ dữ liệu</span>
                            <span wire:loading wire:target="syncSelected">Đang đồng bộ...</span>
                        </button>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1550px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            @if ($canSyncPricing)<th class="w-12 px-3 py-3 text-center">Chọn</th>@endif
                            <th class="w-14 px-3 py-3 text-center">STT</th>
                            <th class="min-w-40 px-4 py-3">Thuốc</th>
                            <th class="min-w-32 px-4 py-3">Nhóm thuốc</th>
                            <th class="min-w-40 px-4 py-3">Hoạt chất</th>
                            <th class="min-w-40 px-4 py-3">Nồng độ / hàm lượng</th>
                            <th class="min-w-28 px-4 py-3 text-right">Giá trúng thầu</th>
                            <th class="min-w-24 px-4 py-3 text-right">Số lượng</th>
                            <th class="min-w-64 px-4 py-3">Đơn vị trúng thầu</th>
                            <th class="min-w-64 px-4 py-3">Chủ đầu tư / Bên mời thầu</th>
                            <th class="min-w-32 px-4 py-3">Mã TBMT</th>
                            <th class="w-24 px-4 py-3 text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach ($results as $item)
                            @php
                                $sourceId = is_string($item['id'] ?? null) ? $item['id'] : '';
                                $isSynced = $sourceId !== '' && isset($syncedLookup[$sourceId]);
                                $winningNames = array_values(array_filter(
                                    is_array($item['winningName'] ?? null) ? $item['winningName'] : [],
                                    static fn ($name) => is_scalar($name) && trim((string) $name) !== ''
                                ));
                            @endphp
                            <tr class="align-top transition-colors {{ $isSynced ? 'bg-emerald-50/30' : 'hover:bg-indigo-50/30' }}">
                                @if ($canSyncPricing)
                                    <td class="px-3 py-4 text-center">
                                        @if ($sourceId !== '')
                                            <input type="checkbox" value="{{ $sourceId }}" wire:model="selectedSourceIds" @disabled($isSynced)
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-40">
                                            @if ($isSynced)
                                                <div class="mt-1 whitespace-nowrap text-[10px] font-semibold text-emerald-700">Đã đồng bộ</div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-3 py-4 text-center font-medium text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-950">{{ $item['tenThuoc'] ?? '-' }}</td>
                                <td class="px-4 py-4">
                                    @if (! empty($item['nhomThuoc']))
                                        <span class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{{ $item['nhomThuoc'] }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">{{ $item['tenHoatChat'] ?? '-' }}</td>
                                <td class="px-4 py-4">{{ $item['nongDo'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-gray-950">{{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.') : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">{{ is_numeric($item['soLuong'] ?? null) ? number_format((float) $item['soLuong'], 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-4">
                                    @if ($winningNames !== [])
                                        <div class="space-y-1">@foreach ($winningNames as $winningName)<div class="font-semibold leading-5 text-emerald-700">{{ $winningName }}</div>@endforeach</div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 leading-5">{{ $item['tenCdtBmt'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4"><span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-semibold text-gray-700">{{ $item['maTbmt'] ?? '-' }}</span></td>
                                <td class="px-4 py-4 text-center">
                                    @if ($sourceId !== '')
                                        <button type="button" wire:click="openDetail('{{ $sourceId }}')"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                            Xem
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-10 text-center">
                <p class="text-sm font-medium text-gray-700">Chưa có dữ liệu tra cứu</p>
                <p class="mt-1 text-sm text-gray-500">Nhập tên thuốc, hoạt chất hoặc mã TBMT để bắt đầu.</p>
            </div>
        @endif
    </div>

    @include('Muasamcong::livewire.partials.pricing-detail-modal')
</div>

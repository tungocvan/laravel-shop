<div class="space-y-5">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Tên doanh nghiệp</label>
                <input type="text" wire:model="companyKeyword" wire:keydown.enter="searchCompany"
                       placeholder="Ví dụ: CÔNG TY TNHH INAFO VIỆT NAM"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="button" wire:click="searchCompany" wire:loading.attr="disabled"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                Tìm doanh nghiệp
            </button>
        </div>

        @if ($error)
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
        @endif
        @if ($notice)
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ $notice }}</div>
        @endif

        @if (count($companies) > 1)
            <div class="mt-4 grid gap-2 md:grid-cols-2">
                @foreach ($companies as $company)
                    <button type="button" wire:click="selectCompany('{{ $company['code'] }}')"
                            class="rounded-lg border border-gray-200 p-3 text-left hover:border-indigo-300 hover:bg-indigo-50">
                        <div class="font-medium text-gray-900">{{ $company['name'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $company['code'] }}</div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($contractorCode)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Gói thầu đã tham gia</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">{{ $contractorName }}</h3>
                        <p class="text-sm text-gray-500">Mã nhà thầu: {{ $contractorCode }}</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Từ ngày</label>
                            <input type="date" wire:model="fromDate" class="rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Đến ngày</label>
                            <input type="date" wire:model="toDate" class="rounded-lg border-gray-300 text-sm">
                        </div>
                        <button wire:click="loadHistory" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-50">Tra cứu</button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                <div class="text-sm text-gray-600">Tổng cộng <strong class="text-gray-900">{{ count($results) }}</strong> gói <span class="text-gray-400">(API báo {{ $reportedTotal }})</span></div>
                <div class="flex gap-2">
                    <button wire:click="selectAll" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Chọn tất cả</button>
                    <button wire:click="clearSelection" class="text-sm font-medium text-gray-500 hover:text-gray-700">Bỏ chọn</button>
                    <button wire:click="syncSelected" @disabled(empty($selected))
                            class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-40">Đồng bộ ({{ count($selected) }})</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3"></th>
                        <th class="px-4 py-3">Mã TBMT</th>
                        <th class="px-4 py-3">Tên gói thầu</th>
                        <th class="px-4 py-3">Ngày</th>
                        <th class="px-4 py-3">Năm</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($results as $row)
                        @php($isSynced = isset($syncedNotifyNos[$row['notifyNo'] ?? '']))
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><input type="checkbox" wire:model="selected" value="{{ $row['notifyNo'] ?? '' }}" @disabled($isSynced) class="rounded border-gray-300"></td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-indigo-700">{{ $row['notifyNo'] ?? '—' }}</td>
                            <td class="max-w-xl px-4 py-3 text-gray-800">{{ $row['bidName'] ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ isset($row['createdDate']) ? \Illuminate\Support\Carbon::parse($row['createdDate'])->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['dateYear'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($isSynced)
                                    <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Đã đồng bộ</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Đã tham gia</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button wire:click="showDetail('{{ $row['notifyNo'] ?? '' }}')" class="text-sm font-medium text-gray-600 hover:text-gray-900">Chi tiết</button>
                                <button wire:click="showKqlcnt('{{ $row['notifyNo'] ?? '' }}')" wire:loading.attr="disabled"
                                        class="ml-3 text-sm font-semibold text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                                    Xem KQLCNT
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">Không có gói thầu trong khoảng thời gian đã chọn.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($detail)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/50 p-4" wire:click.self="closeDetail">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b p-5">
                    <div><p class="text-xs font-semibold uppercase text-indigo-600">Chi tiết gói đã tham gia</p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ $detail['notifyNo'] ?? '' }}</h3></div>
                    <button wire:click="closeDetail" class="text-2xl text-gray-400 hover:text-gray-700">&times;</button>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><div class="text-xs text-gray-500">Tên gói thầu</div><div class="mt-1 font-medium text-gray-900">{{ $detail['bidName'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Mã nhà thầu</div><div class="mt-1 text-gray-800">{{ $detail['contractorCode'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Mã bên mời thầu</div><div class="mt-1 text-gray-800">{{ $detail['procuringEntityCode'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Ngày tham gia</div><div class="mt-1 text-gray-800">{{ $detail['createdDate'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Kỳ dữ liệu</div><div class="mt-1 text-gray-800">{{ $detail['dateQuarter'] ?? '—' }} / {{ $detail['dateMonth'] ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    @endif

    @if ($kqlcnt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4" wire:click.self="closeKqlcnt">
            <div class="max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Kết quả lựa chọn nhà thầu</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">{{ $kqlcnt['notify_no'] ?? '' }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $kqlcnt['bid_name'] ?? '—' }}</p>
                    </div>
                    <button wire:click="closeKqlcnt" class="text-2xl text-gray-400 hover:text-gray-700">&times;</button>
                </div>

                <div class="max-h-[calc(92vh-82px)] overflow-y-auto p-6">
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="text-xs uppercase text-gray-500">Trạng thái</div>
                            <div class="mt-1 font-semibold text-gray-900">{{ ($kqlcnt['status'] ?? '') === 'PUB_KQLCNT' ? 'Đã công bố KQLCNT' : ($kqlcnt['status'] ?? '—') }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="text-xs uppercase text-gray-500">Mã nhà thầu đang xem</div>
                            <div class="mt-1 font-semibold text-gray-900">{{ $kqlcnt['contractor_code'] ?? '—' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="text-xs uppercase text-gray-500">Số hợp đồng phù hợp</div>
                            <div class="mt-1 text-xl font-bold text-gray-900">{{ count($kqlcnt['contracts'] ?? []) }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="text-xs uppercase text-gray-500">Lô đã xác minh</div>
                            <div class="mt-1 text-xl font-bold text-gray-900">{{ count($kqlcnt['verified_lots'] ?? []) }}</div>
                        </div>
                    </div>

                    <section class="mt-6">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h4 class="text-base font-bold text-gray-900">Đơn vị trúng thầu / Hợp đồng</h4>
                                <p class="text-sm text-gray-500">Chỉ hiển thị hợp đồng có contractorPassList khớp đúng mã nhà thầu đang xem.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse (($kqlcnt['contracts'] ?? []) as $contract)
                                @php($winner = $contract['contractorPassListParsed'][0] ?? [])
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <div class="grid gap-4 lg:grid-cols-3">
                                        <div class="lg:col-span-2">
                                            <div class="text-xs uppercase text-gray-500">Đơn vị trúng thầu</div>
                                            <div class="mt-1 font-semibold text-gray-900">{{ $winner['contractorName'] ?? '—' }}</div>
                                            <div class="mt-1 text-xs text-gray-500">{{ $winner['contractorCode'] ?? '—' }}</div>
                                            @if (!empty($winner['contractorAddress']))
                                                <div class="mt-2 text-sm text-gray-600">{{ $winner['contractorAddress'] }}</div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase text-gray-500">Hợp đồng / TTK</div>
                                            <div class="mt-1 font-semibold text-gray-900">{{ $contract['contractNo'] ?? '—' }}</div>
                                            <div class="mt-1 text-sm text-gray-600">{{ $contract['contractName'] ?? '—' }}</div>
                                            <div class="mt-1 text-xs text-gray-500">{{ !empty($contract['contractSignDate']) ? \Illuminate\Support\Carbon::parse($contract['contractSignDate'])->format('d/m/Y') : '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    Chưa tìm thấy hợp đồng/KQLCNT có mã nhà thầu {{ $contractorCode }} trong dữ liệu nguồn hiện tại.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="mt-6">
                        <div>
                            <h4 class="text-base font-bold text-gray-900">Danh mục lô / thuốc của nhà thầu</h4>
                            <p class="mt-1 text-sm text-gray-500">Hệ thống chỉ hiển thị lô khi dữ liệu nguồn có khóa xác minh trực tiếp lô ↔ nhà thầu.</p>
                        </div>

                        @if (!empty($kqlcnt['verified_lots']))
                            <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                                    <tr><th class="px-4 py-3">Mã lô</th><th class="px-4 py-3">Tên lô / thuốc</th><th class="px-4 py-3">Số lượng</th><th class="px-4 py-3">Đơn giá / Giá</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @foreach ($kqlcnt['verified_lots'] as $lot)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-indigo-700">{{ $lot['lotNo'] ?? $lot['lotCode'] ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-800">{{ $lot['lotName'] ?? $lot['tenThuoc'] ?? $lot['goodsName'] ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $lot['quantity'] ?? $lot['soLuong'] ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $lot['unitPrice'] ?? $lot['donGia'] ?? $lot['price'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="mt-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                                Chưa xác định được danh mục lô thuộc nhà thầu này từ dữ liệu nguồn hiện có. Hệ thống không hiển thị toàn bộ lot của TBMT để tránh gán sai thuốc cho nhà thầu.
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    @endif
</div>

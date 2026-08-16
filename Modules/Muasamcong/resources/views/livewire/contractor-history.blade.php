<div class="space-y-5">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Tên doanh nghiệp</label>
                <input type="text" wire:model="companyKeyword" wire:keydown.enter="searchCompany"
                       placeholder="Ví dụ: CÔNG TY TNHH INAFO VIỆT NAM"
                       class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
            <button type="button" wire:click="searchCompany" wire:loading.attr="disabled"
                    class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">
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
                            <input type="date" wire:model="fromDate" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Đến ngày</label>
                            <input type="date" wire:model="toDate" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        </div>
                        <button wire:click="loadHistory" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-50">Tra cứu</button>
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
                        @php($isKqlcntSynced = isset($syncedKqlcntNotifyNos[$row['notifyNo'] ?? '']))
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><input type="checkbox" wire:model="selected" value="{{ $row['notifyNo'] ?? '' }}" @disabled($isSynced) class="rounded border-gray-300"></td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-indigo-700">{{ $row['notifyNo'] ?? '—' }}</td>
                            <td class="max-w-xl px-4 py-3 text-gray-800">{{ $row['bidName'] ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ isset($row['createdDate']) ? \Illuminate\Support\Carbon::parse($row['createdDate'])->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['dateYear'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @if ($isSynced)
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Đã đồng bộ</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Đã tham gia</span>
                                    @endif
                                    @if ($isKqlcntSynced)
                                        <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">KQLCNT đã lưu</span>
                                    @endif
                                </div>
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
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-950/50 p-4" wire:click.self="closeDetail">
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
        @teleport('body')
            @include('Muasamcong::livewire.partials.kqlcnt-modal')
        @endteleport
    @endif
</div>

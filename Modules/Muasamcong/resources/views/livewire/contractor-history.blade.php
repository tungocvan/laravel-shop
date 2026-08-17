<div class="space-y-5">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium text-gray-700">Tên doanh nghiệp, CONTRACTOR_CODE hoặc mã số thuế</label>
                <input type="text" wire:model="companyKeyword" wire:keydown.enter="searchCompany"
                       placeholder="Ví dụ: KHANG TÍN, vn0315681994 hoặc 0315681994"
                       class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">Mã số thuế chỉ cần nhập số; hệ thống tự chuẩn hóa thành CONTRACTOR_CODE dạng <code>vn+mã_số_thuế</code>.</p>
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
                        <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                            <span>{{ $company['code'] }}</span>
                            @if (($company['source'] ?? '') === 'server')
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">Đã lưu trên server</span>
                            @endif
                        </div>
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
                        <p class="text-sm text-gray-500">CONTRACTOR_CODE: {{ $contractorCode }}</p>
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
                        <button type="button" wire:click="searchFresh" wire:loading.attr="disabled" wire:target="searchFresh"
                                class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 disabled:opacity-50">
                            <span wire:loading.remove wire:target="searchFresh">Tìm kiếm mới</span>
                            <span wire:loading wire:target="searchFresh">Đang kiểm tra Session…</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                <div class="text-sm text-gray-600">
                    API báo <strong class="text-gray-900">{{ number_format($reportedTotal) }}</strong> gói
                    <span class="text-gray-400">· Trang {{ $historyPage }}/{{ $historyTotalPages }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-xs text-gray-500">Hiển thị</label>
                    <select wire:model.live="historyPerPage" class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <button wire:click="selectAll" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Chọn trang này</button>
                    <button wire:click="clearSelection" class="text-sm font-medium text-gray-500 hover:text-gray-700">Bỏ chọn tất cả</button>
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
                        <th class="px-4 py-3">Chủ đầu tư</th>
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
                        @php($investorName = $row['investorName'] ?? $row['investor_name'] ?? $row['investorNameEn'] ?? '—')
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3"><input type="checkbox" wire:model="selected" value="{{ $row['notifyNo'] ?? '' }}" @disabled($isSynced) class="rounded border-gray-300"></td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-indigo-700">{{ $row['notifyNo'] ?? '—' }}</td>
                            <td class="max-w-xl px-4 py-3 text-gray-800">{{ $row['bidName'] ?? '—' }}</td>
                            <td class="max-w-sm px-4 py-3 text-gray-700">{{ $investorName }}</td>
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
                        <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">Không có gói thầu trong dữ liệu đã lưu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($historyTotalPages > 1)
                <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs text-gray-500">Các checkbox đã chọn vẫn được giữ khi chuyển trang.</div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="historyPreviousPage" @disabled($historyPage <= 1)
                                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">← Trước</button>
                        @php($startPage = max(1, $historyPage - 2))
                        @php($endPage = min($historyTotalPages, $historyPage + 2))
                        @for ($page = $startPage; $page <= $endPage; $page++)
                            <button type="button" wire:click="historyGoToPage({{ $page }})"
                                    class="rounded-lg px-3 py-2 text-sm font-semibold {{ $page === $historyPage ? 'bg-indigo-600 text-white' : 'border border-gray-300 bg-white text-gray-700' }}">
                                {{ $page }}
                            </button>
                        @endfor
                        <button type="button" wire:click="historyNextPage" @disabled($historyPage >= $historyTotalPages)
                                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Sau →</button>
                    </div>
                </div>
            @endif
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
                    <div class="sm:col-span-2"><div class="text-xs text-gray-500">Chủ đầu tư</div><div class="mt-1 font-medium text-gray-900">{{ $detail['investorName'] ?? $detail['investor_name'] ?? $detail['investorNameEn'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Mã nhà thầu</div><div class="mt-1 text-gray-800">{{ $detail['contractorCode'] ?? $contractorCode }}</div></div>
                    <div><div class="text-xs text-gray-500">Mã bên mời thầu</div><div class="mt-1 text-gray-800">{{ $detail['procuringEntityCode'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Ngày tham gia</div><div class="mt-1 text-gray-800">{{ $detail['createdDate'] ?? '—' }}</div></div>
                    <div><div class="text-xs text-gray-500">Kỳ dữ liệu</div><div class="mt-1 text-gray-800">{{ $detail['dateQuarter'] ?? '—' }} / {{ $detail['dateMonth'] ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    @endif

    @if ($showSessionExpiredModal)
        @teleport('body')
            <div class="fixed inset-0 z-[140] flex items-center justify-center bg-gray-950/60 p-4" wire:click.self="closeSessionExpiredModal">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-gray-100 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Cần cập nhật Session</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">Personal Page Session đã hết hạn hoặc không hợp lệ</h3>
                    </div>
                    <div class="space-y-3 p-5 text-sm text-gray-600">
                        <p>Tìm kiếm mới cần gọi Cổng Mua sắm công nên phải có Cookie/Session còn hiệu lực.</p>
                        <p>Dữ liệu lịch sử đã lưu vẫn xem bình thường và không cần gọi API.</p>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 p-5 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeSessionExpiredModal"
                                class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Đóng</button>
                        <a href="{{ route('muasamcong.config') }}"
                           class="rounded-xl bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-indigo-700">Mở Config cập nhật Session</a>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($kqlcnt)
        @teleport('body')
            @include('Muasamcong::livewire.partials.kqlcnt-modal')
        @endteleport
    @endif
</div>

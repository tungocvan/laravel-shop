<div @if ($hasRunningJobs) wire:poll.2s @endif class="space-y-4">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center">
            <input type="text" wire:model.live.debounce.300ms="keyword"
                   placeholder="Tên nhà thầu, CONTRACTOR_CODE hoặc mã số thuế..."
                   class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <div class="rounded-xl bg-gray-900 px-5 py-3 text-center text-sm font-semibold text-white">Tìm trong dữ liệu đã lưu</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Nhà thầu</th>
                    <th class="px-5 py-3">CONTRACTOR_CODE</th>
                    <th class="px-5 py-3">MST</th>
                    <th class="px-5 py-3 text-right">Số gói</th>
                    <th class="px-5 py-3">Danh mục đã lưu</th>
                    <th class="px-5 py-3">Tra cứu gần nhất</th>
                    <th class="px-5 py-3">Trạng thái cập nhật</th>
                    <th class="px-5 py-3 text-right">Thao tác</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($searches as $search)
                    @php($job = $latestJobs->get($search->contractor_code))
                    @php($isRunning = $job && in_array($job->status, ['queued', 'running', 'saving'], true))
                    @php($catalogues = $cataloguesByCode->get($search->contractor_code, collect()))
                    <tr class="hover:bg-gray-50">
                        <td class="max-w-md px-5 py-4">
                            <div class="font-semibold text-gray-900">{{ $search->contractor_name ?: $search->contractor_code }}</div>
                            @if (! $search->contractor_name || $search->contractor_name === $search->contractor_code)
                                <div class="mt-1 text-xs text-amber-600">Chưa xác định được tên doanh nghiệp; hãy bấm Cập nhật mới.</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-indigo-700">{{ $search->contractor_code }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $search->tax_code ?: '—' }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-right text-gray-700">
                            {{ number_format($search->unique_total) }}
                            @if ($search->reported_total !== $search->unique_total)
                                <span class="text-xs text-gray-400">/ API {{ number_format($search->reported_total) }}</span>
                            @endif
                        </td>
                        <td class="min-w-72 px-5 py-4">
                            @if ($catalogues->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach ($catalogues as $catalogue)
                                        <div class="rounded-lg border border-violet-100 bg-violet-50/60 px-3 py-2">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <div class="font-semibold text-violet-800">{{ $catalogue->notify_no }}</div>
                                                    <div class="mt-0.5 text-xs text-gray-500">
                                                        {{ number_format((int) $catalogue->lot_count, 0, ',', '.') }} lô
                                                        @if ((float) $catalogue->plan_amount > 0)
                                                            · Tổng KH {{ number_format((float) $catalogue->plan_amount, 0, ',', '.') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs font-semibold">
                                                    <a href="{{ route('muasamcong.contractors.manual-lots.show', [$search->contractor_code, $catalogue->notify_no]) }}"
                                                       class="text-indigo-600 hover:text-indigo-800">Xem</a>
                                                    <a href="{{ route('muasamcong.contractors.manual-lots.download', [$search->contractor_code, $catalogue->notify_no]) }}"
                                                       class="text-emerald-600 hover:text-emerald-800">Excel</a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400">Chưa có danh mục</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $search->last_searched_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="min-w-56 px-5 py-4">
                            @if ($isRunning)
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <span class="font-medium text-indigo-700">{{ $job->status_message ?: 'Đang cập nhật...' }}</span>
                                        <span class="font-semibold text-indigo-700">{{ (int) $job->progress }}%</span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-indigo-100">
                                        <div class="h-full rounded-full bg-indigo-600 transition-all duration-500" style="width: {{ max(3, min(100, (int) $job->progress)) }}%"></div>
                                    </div>
                                </div>
                            @elseif ($job && $job->status === 'failed')
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Cập nhật lỗi</span>
                            @elseif ($job && $job->status === 'completed')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã cập nhật</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-right">
                            <div class="inline-flex items-center gap-3">
                                <a href="{{ route('muasamcong.contractors.history.show', $search) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Chi tiết</a>
                                <button type="button" wire:click="refreshSearch({{ $search->id }})" @disabled($isRunning)
                                        class="font-semibold text-emerald-600 hover:text-emerald-800 disabled:cursor-not-allowed disabled:opacity-40">
                                    {{ $isRunning ? 'Đang lấy...' : 'Cập nhật mới' }}
                                </button>
                                <button type="button" wire:click="askDelete({{ $search->id }})" @disabled($isRunning)
                                        class="font-semibold text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-40">Xóa</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-gray-500">Chưa có lịch sử tra cứu nhà thầu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($searches->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $searches->links() }}</div>
        @endif
    </div>

    @if ($confirmDeleteId)
        @teleport('body')
            <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-950/60 p-4" wire:click.self="cancelDelete">
                <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-gray-900">Xóa lịch sử tra cứu?</h3>
                        <p class="mt-2 text-sm text-gray-600">Dữ liệu lịch sử nhà thầu đã lưu của doanh nghiệp này sẽ bị xóa. Bạn có thể tra cứu lại sau.</p>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 p-5">
                        <button type="button" wire:click="cancelDelete" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Hủy</button>
                        <button type="button" wire:click="deleteSearch" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Xóa dữ liệu</button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($showSessionExpiredModal)
        @teleport('body')
            <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-950/60 p-4" wire:click.self="closeSessionExpiredModal">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-gray-100 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Cần cập nhật Session</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">Không thể thực hiện cập nhật mới</h3>
                    </div>
                    <div class="p-5 text-sm text-gray-600">Personal Page Session đã hết hạn hoặc không hợp lệ. Hãy cập nhật Session trước rồi thực hiện lại.</div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 p-5">
                        <button type="button" wire:click="closeSessionExpiredModal" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Đóng</button>
                        <a href="{{ route('muasamcong.config') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Mở Config</a>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>

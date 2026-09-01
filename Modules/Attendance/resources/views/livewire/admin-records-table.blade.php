@php
    $admin = auth('admin')->user();
    $statusLabels = ['checked_in' => 'Đã vào ca', 'completed' => 'Hoàn tất', 'voided' => 'Đã vô hiệu'];
    $statusClasses = ['checked_in' => 'bg-sky-100 text-sky-700', 'completed' => 'bg-emerald-100 text-emerald-700', 'voided' => 'bg-slate-200 text-slate-700'];
@endphp

<div class="space-y-5">
    @if ($notice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $notice }}</div>
    @endif
    @if ($error)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Bộ lọc chấm công">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <label class="xl:col-span-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tìm nhân viên</span>
                <input wire:model.live.debounce.350ms="search" type="search" placeholder="Tên, email, mã NV, phòng ban..." class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </label>
            <label>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</span>
                <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả</option>
                    <option value="checked_in">Đã vào ca</option>
                    <option value="completed">Hoàn tất</option>
                    <option value="voided">Đã vô hiệu</option>
                </select>
            </label>
            <label>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ca</span>
                <select wire:model.live="shift" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả</option>
                    @foreach ($shifts as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vị trí</span>
                <select wire:model.live="location" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả</option>
                    @foreach ($locations as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Số dòng</span>
                <select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Từ ngày</span>
                <input wire:model.live="fromDate" type="date" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </label>
            <label>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đến ngày</span>
                <input wire:model.live="toDate" type="date" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </label>
            <div class="flex items-end xl:col-span-4">
                <button wire:click="resetFilters" type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Xóa bộ lọc</button>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nhân viên</th>
                        <th class="px-4 py-3">Ngày / ca</th>
                        <th class="px-4 py-3">Giờ vào / ra</th>
                        <th class="px-4 py-3">Kết quả</th>
                        <th class="px-4 py-3">Vị trí</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php
                            $pending = $record->adjustmentRequests->first();
                            $status = $record->status->value;
                        @endphp
                        <tr wire:key="attendance-record-{{ $record->id }}" class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ $record->user?->name ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $record->employeeProfile?->employee_code ?? 'Chưa có mã NV' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-800">{{ $record->work_date?->format('d/m/Y') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $record->shift_name_snapshot }} · {{ $record->shift_code_snapshot }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                <p>Vào: {{ $record->checked_in_at?->format('H:i') ?? '—' }}</p>
                                <p class="mt-1">Ra: {{ $record->checked_out_at?->format('H:i') ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabels[$status] ?? $status }}</span>
                                <p class="mt-2 text-xs text-slate-500">Làm {{ (int) $record->worked_minutes }}p · Trễ {{ (int) $record->late_minutes }}p · Sớm {{ (int) $record->early_leave_minutes }}p</p>
                                @if ($pending)
                                    <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Có yêu cầu điều chỉnh</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-600">
                                <p>Vào: {{ $record->checkInLocation?->name ?? '—' }}</p>
                                <p class="mt-1">Ra: {{ $record->checkOutLocation?->name ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($pending && $admin?->can('attendance.adjustment.view'))
                                        <button wire:click="openAdjustment({{ $pending->id }})" type="button" class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50">Duyệt YC</button>
                                    @endif
                                    @if ($status !== 'voided' && $admin?->can('attendance.record.adjust'))
                                        <button wire:click="openCorrection({{ $record->id }})" type="button" class="rounded-lg border border-indigo-300 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Sửa giờ</button>
                                    @endif
                                    @if ($status !== 'voided' && $admin?->can('attendance.record.void'))
                                        <button wire:click="openVoid({{ $record->id }})" type="button" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Void</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">Không có bản ghi phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-4">
            {{ $records->links('Attendance::vendor.pagination.admin-attendance') }}
        </div>
    </section>

    @if ($dialog !== '')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                @if ($dialog === 'void')
                    <h2 class="text-lg font-bold text-slate-950">Vô hiệu hóa bản ghi</h2>
                    <p class="mt-2 text-sm text-slate-600">Không xóa dữ liệu. Hành động này được ghi audit.</p>
                    <textarea wire:model="voidReason" rows="4" placeholder="Lý do bắt buộc" class="mt-4 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                    <div class="mt-5 flex justify-end gap-3">
                        <button wire:click="closeDialog" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button>
                        <button wire:click="voidRecord" wire:loading.attr="disabled" type="button" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">Xác nhận void</button>
                    </div>
                @elseif ($dialog === 'correction')
                    <h2 class="text-lg font-bold text-slate-950">Điều chỉnh giờ chấm công</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label><span class="text-sm font-medium text-slate-700">Giờ vào</span><input wire:model="correctionCheckIn" type="datetime-local" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                        <label><span class="text-sm font-medium text-slate-700">Giờ ra</span><input wire:model="correctionCheckOut" type="datetime-local" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
                    </div>
                    <textarea wire:model="correctionReason" rows="3" placeholder="Lý do điều chỉnh" class="mt-4 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm placeholder:text-gray-400"></textarea>
                    <div class="mt-5 flex justify-end gap-3"><button wire:click="closeDialog" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button wire:click="correctRecord" wire:loading.attr="disabled" type="button" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">Lưu điều chỉnh</button></div>
                @elseif ($dialog === 'adjustment' && $selectedAdjustment)
                    <h2 class="text-lg font-bold text-slate-950">Duyệt yêu cầu điều chỉnh</h2>
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                        <p><strong>Lý do:</strong> {{ $selectedAdjustment->reason }}</p>
                        <p class="mt-2"><strong>Giờ vào đề nghị:</strong> {{ $selectedAdjustment->requested_check_in_at?->format('d/m/Y H:i') ?? 'Giữ nguyên' }}</p>
                        <p class="mt-1"><strong>Giờ ra đề nghị:</strong> {{ $selectedAdjustment->requested_check_out_at?->format('d/m/Y H:i') ?? 'Giữ nguyên' }}</p>
                    </div>
                    <textarea wire:model="reviewNote" rows="3" placeholder="Ghi chú duyệt; bắt buộc nếu từ chối" class="mt-4 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm placeholder:text-gray-400"></textarea>
                    <div class="mt-5 flex flex-wrap justify-end gap-3">
                        <button wire:click="closeDialog" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button>
                        @if ($admin?->can('attendance.adjustment.approve'))
                            <button wire:click="rejectAdjustment" wire:loading.attr="disabled" type="button" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 disabled:opacity-60">Từ chối</button>
                            <button wire:click="approveAdjustment" wire:loading.attr="disabled" type="button" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">Phê duyệt</button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

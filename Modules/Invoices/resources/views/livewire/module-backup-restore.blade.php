<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Module protection</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">Backup & Recovery</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Snapshot chỉ chứa dữ liệu nghiệp vụ Invoices và metadata PDF. Partner master và binary PDF không nằm trong restore scope.</p>
        </div>
        <button type="button" wire:click="backupNow" wire:loading.attr="disabled" wire:target="backupNow"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60">
            <span wire:loading.remove wire:target="backupNow">Backup ngay</span>
            <span wire:loading wire:target="backupNow">Đang tạo backup…</span>
        </button>
    </div>

    @if ($message)
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $message }}</div>
    @endif
    @if ($error)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.8fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h3 class="font-bold text-slate-950">Lịch sử snapshot</h3>
                <p class="mt-1 text-sm text-slate-500">Hiển thị tối đa 20 snapshot gần nhất. Chọn snapshot trước khi kiểm tra restore.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($snapshots as $snapshot)
                    <button type="button" wire:click="selectSnapshot(@js($snapshot['directory']))"
                            class="block w-full px-5 py-4 text-left transition hover:bg-indigo-50/50 {{ $selectedSnapshot === $snapshot['directory'] ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : 'bg-white' }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $snapshot['created_at'] ? \Illuminate\Support\Carbon::parse($snapshot['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : basename($snapshot['directory']) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ number_format($snapshot['invoices']) }} hóa đơn · {{ number_format($snapshot['files']) }} metadata PDF</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ str_starts_with($snapshot['mode'], 'safety') ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">{{ strtoupper($snapshot['mode']) }}</span>
                        </div>
                    </button>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">Chưa có module snapshot. Hãy tạo Backup ngay trước khi cần khôi phục.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-950">Restore Readiness</h3>
                    <p class="mt-1 text-sm text-slate-500">Backup tồn tại không đồng nghĩa snapshot có thể khôi phục.</p>
                </div>
                @if ($readiness)
                    @php
                        $status = strtoupper($readiness['status']);
                        $statusClass = $status === 'READY' ? 'bg-emerald-100 text-emerald-800' : ($status === 'WARNING' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800');
                    @endphp
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $status }}</span>
                @else
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">CHƯA KIỂM TRA</span>
                @endif
            </div>

            <button type="button" wire:click="checkRestore" wire:loading.attr="disabled" wire:target="checkRestore"
                    @disabled(! $selectedSnapshot)
                    class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-indigo-300 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400">
                <span wire:loading.remove wire:target="checkRestore">Kiểm tra khả năng khôi phục</span>
                <span wire:loading wire:target="checkRestore">Đang kiểm tra…</span>
            </button>

            @if ($readiness)
                <div class="mt-5 space-y-4">
                    @if ($readiness['blockers'])
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-bold">Restore bị chặn</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($readiness['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if ($readiness['warnings'])
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-bold">Cảnh báo cần xem xét</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($readiness['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if ($impact)
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-900">Ảnh hưởng dự kiến — Merge an toàn</p>
                            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-slate-500">Thêm mới</dt><dd class="font-bold text-emerald-700">+ {{ number_format($impact['invoices']['insert']) }}</dd></div>
                                <div><dt class="text-slate-500">Đã tồn tại</dt><dd class="font-bold text-slate-900">{{ number_format($impact['invoices']['existing']) }}</dd></div>
                                <div><dt class="text-slate-500">Có khác biệt</dt><dd class="font-bold text-amber-700">! {{ number_format($impact['invoices']['different']) }}</dd></div>
                                <div><dt class="text-slate-500">Sẽ xóa</dt><dd class="font-bold text-slate-900">0</dd></div>
                                <div class="col-span-2"><dt class="text-slate-500">Partner master</dt><dd class="font-bold text-emerald-700">0 thay đổi</dd></div>
                            </dl>
                        </div>
                    @endif

                    <button type="button" wire:click="restoreMerge" wire:loading.attr="disabled" wire:target="restoreMerge"
                            @disabled(strtoupper($readiness['status']) === 'BLOCKED')
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                        <span wire:loading.remove wire:target="restoreMerge">Tạo Safety Backup + Khôi phục Merge</span>
                        <span wire:loading wire:target="restoreMerge">Đang bảo vệ và khôi phục…</span>
                    </button>
                    <p class="text-xs leading-5 text-slate-500">Safety Backup hiện trạng là bắt buộc. Nếu không tạo được Safety Backup, restore sẽ dừng trước khi thay đổi dữ liệu.</p>
                </div>
            @endif

            @if ($lastRestore)
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="font-bold">Post-Restore Verification hoàn tất</p>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs sm:text-sm">
                        <div><dt class="text-emerald-700">Hóa đơn thêm</dt><dd class="font-bold">{{ number_format($lastRestore['inserted_invoices']) }}</dd></div>
                        <div><dt class="text-emerald-700">Hóa đơn giữ nguyên</dt><dd class="font-bold">{{ number_format($lastRestore['preserved_invoices']) }}</dd></div>
                        <div><dt class="text-emerald-700">Metadata PDF thêm</dt><dd class="font-bold">{{ number_format($lastRestore['inserted_files']) }}</dd></div>
                        <div><dt class="text-emerald-700">Partner master</dt><dd class="font-bold">{{ number_format($lastRestore['partner_master_changes']) }} thay đổi</dd></div>
                    </dl>
                    <p class="mt-3 break-all text-xs">Safety Backup: <span class="font-mono">{{ $lastRestore['safety_backup_directory'] !== '' ? $lastRestore['safety_backup_directory'] : 'Đã tạo' }}</span></p>
                </div>
            @endif
        </section>
    </div>
</div>

<div>
    @if ($blockedSnapshots !== [])
        <section class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-600 px-2.5 py-1 text-xs font-bold text-white">SCHEMA DOCTOR</span>
                        <h2 class="text-sm font-bold text-amber-950">{{ count($blockedSnapshots) }} snapshot đang bị khóa Restore</h2>
                    </div>
                    <p class="mt-2 max-w-4xl text-sm leading-6 text-amber-900">Doctor chỉ đọc schema và giải thích nguyên nhân. Không DROP/ALTER dữ liệu, không force Restore và không tự bỏ qua fingerprint validation.</p>
                </div>
            </div>

            <div class="mt-4 grid gap-3 xl:grid-cols-2">
                @foreach ($blockedSnapshots as $snapshot)
                    <div class="rounded-xl border border-amber-200 bg-white p-4" wire:key="doctor-{{ $snapshot['reference'] }}">
                        <p class="break-all text-sm font-semibold text-gray-900">{{ $snapshot['name'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span>{{ count($snapshot['tables']) }} bảng</span>
                            <span class="rounded-full bg-red-50 px-2 py-0.5 font-bold text-red-700">SCHEMA KHÔNG TƯƠNG THÍCH</span>
                        </div>
                        <button type="button" wire:click="diagnose('{{ $snapshot['reference'] }}')" wire:loading.attr="disabled" class="mt-3 inline-flex items-center justify-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-900 hover:bg-amber-100 disabled:opacity-50">
                            <span wire:loading.remove wire:target="diagnose('{{ $snapshot['reference'] }}')">Chẩn đoán Schema</span>
                            <span wire:loading wire:target="diagnose('{{ $snapshot['reference'] }}')">Đang kiểm tra...</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($open && $report)
        <div class="fixed inset-0 z-[10020] flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Schema Doctor · {{ $module }}</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-950">Kết quả chẩn đoán</h2>
                    </div>
                    <button type="button" wire:click="close" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Đóng">✕</button>
                </div>

                <div class="space-y-4 p-5 sm:p-6">
                    @php($verdict = $report['verdict'] ?? 'BLOCKED')
                    <div class="rounded-xl border p-4 {{ $verdict === 'SAFE' ? 'border-emerald-200 bg-emerald-50' : ($verdict === 'REVIEW' ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50') }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $verdict === 'SAFE' ? 'bg-emerald-600 text-white' : ($verdict === 'REVIEW' ? 'bg-amber-600 text-white' : 'bg-red-600 text-white') }}">{{ $verdict }}</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $report['summary'] }}</span>
                        </div>
                    </div>

                    @if (($report['issues'] ?? []) !== [])
                        <div class="space-y-3">
                            @foreach ($report['issues'] as $issue)
                                <article class="rounded-xl border border-gray-200 bg-white p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-700">{{ $issue['risk'] ?? 'REVIEW' }}</span>
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $issue['type'] ?? 'schema') }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ $issue['message'] ?? '' }}</p>
                                    @if (! empty($issue['suggestion']))<p class="mt-1 text-sm leading-6 text-gray-600"><span class="font-semibold">Khắc phục:</span> {{ $issue['suggestion'] }}</p>@endif
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm leading-6 text-gray-700">
                        <p class="font-bold text-gray-900">Fix Plan an toàn</p>
                        <p class="mt-1">Auto-repair hiện bị khóa. Chỉ sau khi remediation được xác minh và fingerprint trở thành COMPATIBLE thì nút Restore Module mới được phép xuất hiện.</p>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-5 py-4 sm:px-6">
                    <button type="button" wire:click="close" class="rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-gray-800">Đóng</button>
                </div>
            </div>
        </div>
    @endif
</div>

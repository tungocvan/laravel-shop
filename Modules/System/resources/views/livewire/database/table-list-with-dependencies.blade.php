<div class="space-y-4">
    @if ($moduleFilter !== '' && $moduleFilter !== 'Unknown' && $moduleDependencies !== [])
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-4 shadow-sm sm:p-5" role="alert">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-600 px-2.5 py-1 text-xs font-bold tracking-wide text-white">DEPENDENCY WARNING</span>
                        <h2 class="text-sm font-bold text-amber-950">{{ $moduleFilter }} có Module phụ thuộc</h2>
                    </div>

                    <p class="mt-2 text-sm leading-6 text-amber-900">
                        <span class="font-semibold">{{ $moduleFilter }} phụ thuộc:</span>
                        {{ implode(' · ', $moduleDependencies) }}
                    </p>

                    <p class="mt-1 max-w-4xl text-sm leading-6 text-amber-800">
                        Snapshot này chỉ backup các bảng thuộc ownership của Module {{ $moduleFilter }}.
                        Các Module phụ thuộc không được tự động backup hoặc restore cùng snapshot này.
                        Nên tạo snapshot riêng cho các Module phụ thuộc trước các thao tác phục hồi quan trọng.
                    </p>
                </div>

                <div class="shrink-0 rounded-xl border border-amber-200 bg-white/80 px-3 py-2 text-xs font-semibold text-amber-800">
                    Metadata dependency được lưu trong manifest.json
                </div>
            </div>
        </section>
    @endif

    @include('System::livewire.database.table-list')
</div>

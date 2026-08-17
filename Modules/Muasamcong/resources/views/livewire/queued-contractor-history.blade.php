<div @if ($showQueueModal) wire:poll.2s="pollQueue" @endif>
    @include('Muasamcong::livewire.contractor-history')

    @if ($showQueueModal)
        @teleport('body')
            <div class="fixed inset-0 z-[140] flex items-center justify-center bg-gray-950/55 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-gray-100 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Tra cứu lịch sử nhà thầu</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">Hệ thống đang thực thi...</h3>
                        <p class="mt-1 text-sm text-gray-500">Bạn có thể giữ nguyên cửa sổ này; tác vụ đang chạy bằng Queue nên không còn phụ thuộc timeout của request web.</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $queueMessage ?: 'Đang chờ xử lý...' }}</span>
                            <span class="font-semibold text-indigo-700">{{ $queueProgress }}%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                                 style="width: {{ max(3, min(100, $queueProgress)) }}%"></div>
                        </div>
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                            CONTRACTOR_CODE: <strong>{{ $contractorCode }}</strong><br>
                            Trạng thái: <strong>{{ $queueStatus ?: 'queued' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <style>
        @media (min-width: 1024px) {
            button[wire\:click="searchCompany"] {
                transform: translateY(-18px);
            }
        }
    </style>
</div>

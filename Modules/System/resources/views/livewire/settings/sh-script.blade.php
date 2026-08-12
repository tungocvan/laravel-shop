<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Thao tác Script hệ thống</h1>
            <p class="mt-1 text-sm text-gray-500">Chỉ các script do hệ thống đăng ký và kiểm soát mới có thể được thực thi.</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
            Restricted Scripts
        </span>
    </div>

    @if($errorMessage)
        <div class="rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4">
            @forelse($operations as $operation)
                <button
                    type="button"
                    wire:click="$set('selectedOperation', '{{ $operation['id'] }}')"
                    class="w-full rounded-2xl border p-5 text-left shadow-sm transition {{ $selectedOperation === $operation['id'] ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-indigo-300' }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $operation['label'] }}</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-500">{{ $operation['description'] }}</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700">
                            Controlled
                        </span>
                    </div>
                </button>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500">
                    Chưa có script nào được phê duyệt và đăng ký. Không có shell code tùy ý nào có thể chạy từ trình duyệt.
                </div>
            @endforelse

            @if(!empty($operations))
                @php
                    $selected = collect($operations)->firstWhere('id', $selectedOperation);
                @endphp
                <button
                    type="button"
                    wire:click="executeOperation"
                    @if(($selected['confirmation'] ?? false))
                        wire:confirm="Thao tác script này có thể thay đổi trạng thái hệ thống. Bạn chắc chắn muốn thực hiện?"
                    @endif
                    wire:loading.attr="disabled"
                    wire:target="executeOperation"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="executeOperation">Thực hiện thao tác</span>
                    <span wire:loading wire:target="executeOperation">Đang thực hiện...</span>
                </button>
            @endif
        </div>

        <div class="lg:col-span-2">
            <div class="min-h-[350px] overflow-hidden rounded-2xl bg-gray-900 shadow-xl">
                <div class="border-b border-gray-700 bg-gray-800 px-4 py-3">
                    <span class="text-xs font-medium text-gray-400">system-script-operation output</span>
                </div>
                <div class="p-6 font-mono text-sm leading-relaxed">
                    @if($executionOutput)
                        <pre class="whitespace-pre-wrap text-emerald-400">{{ $executionOutput }}</pre>
                    @else
                        <div class="flex min-h-[260px] items-center justify-center text-center text-sm text-gray-500">
                            Chọn một thao tác script đã được hệ thống đăng ký để xem kết quả thực thi.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

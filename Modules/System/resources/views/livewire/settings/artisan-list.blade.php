<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Thao tác bảo trì hệ thống</h1>
            <p class="mt-1 text-sm text-gray-500">Chỉ các thao tác đã được hệ thống cho phép mới có thể thực thi.</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
            Restricted Operations
        </span>
    </div>

    @if ($errorMessage)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-1">
            @foreach ($operations as $operation)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ $operation['label'] }}</h2>
                            <p class="mt-1 text-sm leading-6 text-gray-500">{{ $operation['description'] }}</p>
                        </div>
                        @if ($operation['confirmation'])
                            <span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-bold uppercase text-amber-700">Mutation</span>
                        @else
                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold uppercase text-emerald-700">Read only</span>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="$set('selectedOperation', '{{ $operation['id'] }}')"
                        class="mt-4 w-full rounded-xl border px-4 py-2.5 text-sm font-semibold transition
                            {{ $selectedOperation === $operation['id']
                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}"
                    >
                        {{ $selectedOperation === $operation['id'] ? 'Đã chọn' : 'Chọn thao tác' }}
                    </button>
                </div>
            @endforeach

            @php
                $selected = collect($operations)->firstWhere('id', $selectedOperation);
            @endphp

            <button
                type="button"
                wire:click="executeOperation"
                wire:loading.attr="disabled"
                @if (($selected['confirmation'] ?? false))
                    wire:confirm="Thao tác này sẽ thay đổi trạng thái cache runtime của Laravel. Bạn có chắc chắn muốn tiếp tục?"
                @endif
                class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="executeOperation">Thực hiện thao tác</span>
                <span wire:loading wire:target="executeOperation">Đang xử lý...</span>
            </button>
        </div>

        <div class="lg:col-span-2">
            <div class="flex min-h-[420px] flex-col overflow-hidden rounded-2xl bg-gray-900 shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-700 bg-gray-800 px-4 py-3">
                    <span class="text-xs font-medium text-gray-400">system-operation output</span>
                    @if ($commandOutput)
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('system-operation-output').innerText)"
                            class="text-xs font-medium text-gray-400 transition hover:text-white"
                        >
                            Sao chép
                        </button>
                    @endif
                </div>

                <div class="flex-grow overflow-y-auto p-6 font-mono text-sm leading-relaxed">
                    @if ($commandOutput)
                        <pre id="system-operation-output" class="whitespace-pre-wrap text-emerald-400">{{ $commandOutput }}</pre>
                    @else
                        <div class="flex h-full min-h-[320px] items-center justify-center text-center text-sm text-gray-500">
                            Chọn một thao tác được cho phép và bấm “Thực hiện thao tác”.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

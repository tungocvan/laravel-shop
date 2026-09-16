<div>
    @include('Pharma::livewire.price-list.export-configurator-v31')
    @include('Pharma::livewire.price-list.export-media-dimensions')

    @if($open && $activeSection === 'brand')
        <div
            x-data
            x-init="$nextTick(() => {
                const labels = [...document.querySelectorAll('label')];
                const label = labels.find(el => [...el.childNodes].some(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Năm'));
                if (!label) return;

                const textNode = [...label.childNodes].find(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Năm');
                if (textNode) textNode.textContent = 'Ngày tháng năm';

                const input = label.querySelector('input');
                if (!input || input.value.trim() !== '') return;

                const now = new Date();
                const pad = value => String(value).padStart(2, '0');
                input.value = `Ngày ${pad(now.getDate())} tháng ${pad(now.getMonth() + 1)} năm ${now.getFullYear()}`;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            })"
            class="hidden"
            aria-hidden="true"
        ></div>
    @endif

    {{--
        Interaction-safe v3.2 overlays.

        v3.1 passes JSON filenames directly inside wire:click JavaScript expressions and
        also exposes a public property named confirmAction alongside the method with the
        same name. Both patterns are fragile in Livewire's browser expression parser.
        This layer keeps filenames in ordinary HTML values and explicitly calls the
        confirmation method through Livewire's $call API. The original v3.1 markup stays
        intact for backwards compatibility while these higher-z overlays own interaction.
    --}}
    @if($open && $jsonLibraryOpen)
        <div class="fixed inset-0 z-[125] grid place-items-center bg-slate-950/55 p-4" wire:key="excel-designer-json-library-v32">
            <div class="flex max-h-[80vh] w-full max-w-2xl flex-col rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wide text-indigo-600">JSON Server Library</span>
                        <h3 class="mt-2 text-lg font-extrabold text-slate-900">Thư viện cấu hình JSON</h3>
                        <p class="mt-1 text-xs text-slate-500">Chọn cấu hình để import hoặc xóa khỏi thư viện.</p>
                    </div>
                    <button type="button" wire:click="$set('jsonLibraryOpen',false)" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500">×</button>
                </div>

                <div class="mt-4 overflow-y-auto rounded-2xl border border-slate-200">
                    @forelse($jsonFiles as $index => $file)
                        <div wire:key="json-v32-{{ md5($file['name']) }}" class="flex items-center gap-3 border-b border-slate-100 p-3 last:border-b-0 {{ $selectedJsonFile === $file['name'] ? 'bg-indigo-50' : 'bg-white' }}">
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                <input
                                    type="radio"
                                    name="pharma-price-list-json-v32"
                                    wire:model.live="selectedJsonFile"
                                    value="{{ $file['name'] }}"
                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span class="min-w-0 flex-1">
                                    <b class="block truncate text-sm text-slate-800">{{ $file['name'] }}</b>
                                    <span class="text-xs text-slate-400">{{ date('d/m/Y H:i', $file['updated_at']) }} · {{ number_format($file['size'] / 1024, 1) }} KB</span>
                                </span>
                            </label>
                            <button
                                type="button"
                                value="{{ $file['name'] }}"
                                x-data
                                x-on:click="$wire.selectedJsonFile = $el.value; $wire.requestDeleteJson($el.value)"
                                class="rounded-lg px-2 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50"
                            >Xóa</button>
                        </div>
                    @empty
                        <p class="p-8 text-center text-sm text-slate-400">Chưa có file JSON trên server.</p>
                    @endforelse
                </div>

                @error('jsonLibrary')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror

                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="$set('jsonLibraryOpen',false)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold">Hủy</button>
                    <button type="button" wire:click="importSelectedJson" @disabled(!$selectedJsonFile) class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Import file đã chọn</button>
                </div>
            </div>
        </div>
    @endif

    @if($open && $confirmOpen)
        <div class="fixed inset-0 z-[135] grid place-items-center bg-slate-950/60 p-4" wire:key="excel-designer-confirm-v32">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-extrabold text-slate-900">{{ $noticeTitle }}</h3>
                <p class="mt-2 text-sm text-slate-500">Thao tác này cần xác nhận trước khi thực hiện.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="$set('confirmOpen',false)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold">Hủy</button>
                    <button type="button" x-data x-on:click="$wire.$call('confirmAction')" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white">Xác nhận</button>
                </div>
            </div>
        </div>
    @endif
</div>

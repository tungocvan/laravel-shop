<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="mb-5">
        <h2 class="text-lg font-bold text-gray-900">Bố cục Homepage</h2>
        <p class="mt-1 text-sm text-gray-500">Kéo để đổi thứ tự. Mỗi section mở đúng workspace quản trị riêng.</p>
    </div>

    <div class="grid grid-cols-1 gap-4" x-data x-init="Sortable.create($el, { handle: '.section-drag-handle', animation: 160, onEnd() { $wire.reorderSections(this.toArray()) } })">
        @foreach($sectionCards as $card)
            @php($key = $card['layout_key'])
            @php($state = $layout[$key] ?? 'all')
            <article data-id="{{ $key }}" wire:key="homepage-section-card-{{ $key }}" class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <button type="button" class="section-drag-handle cursor-grab rounded-lg border border-indigo-100 bg-indigo-50 p-2 text-indigo-600">☰</button>
                        <div>
                            <div class="flex items-center gap-2"><h3 class="font-bold text-gray-900">{{ $card['label'] }}</h3>@if($card['is_copy'])<span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">Bản sao</span>@endif</div>
                            <p class="mt-1 text-sm text-gray-500">{{ $card['description'] }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-xs font-semibold text-gray-600">Hiển thị</label>
                            <select wire:model="layout.{{ $key }}" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                <option value="all">Desktop + Mobile</option><option value="desktop">Chỉ Desktop</option><option value="mobile">Chỉ Mobile</option><option value="none">Ẩn hoàn toàn</option>
                            </select>
                        </div>

                        @if($card['admin'])
                            @if($card['admin']['type'] === 'route')
                                <a href="{{ $card['admin']['url'] }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{{ $card['admin']['label'] }} ↗</a>
                            @else
                                <button type="button" wire:click="$set('activeTab', '{{ $card['admin']['tab'] }}')" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">⚙ {{ $card['admin']['label'] }}</button>
                            @endif
                        @endif

                        @if($card['duplicatable'])<button type="button" wire:click="duplicateSection('{{ $key }}')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700">⧉ Nhân bản</button>@endif
                        @if(in_array($state, ['none', 'hidden'], true))
                            <button type="button" wire:click="restoreSection('{{ $key }}')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">↻ Khôi phục</button>
                        @else
                            <button type="button" wire:click="removeSection('{{ $key }}')" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600">{{ $card['is_copy'] ? '× Xóa' : '× Ẩn' }}</button>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>

<div class="space-y-6" x-data="{ previewDevice: 'desktop' }">
    @php
        $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
    @endphp

    <section class="mx-auto max-w-6xl rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Homepage Presentation & Responsive Preview</h2>
                <p class="mt-1 text-sm text-gray-500">Điều chỉnh khung trang và khoảng cách. Các thay đổi chỉ publish khi bấm <strong>Lưu thay đổi</strong>.</p>
            </div>
            <div class="inline-flex self-start rounded-lg border border-gray-200 bg-gray-50 p-1">
                <button type="button" @click="previewDevice = 'desktop'" :class="previewDevice === 'desktop' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500'" class="rounded-md px-3 py-2 text-sm font-semibold">Desktop</button>
                <button type="button" @click="previewDevice = 'mobile'" :class="previewDevice === 'mobile' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500'" class="rounded-md px-3 py-2 text-sm font-semibold">Mobile</button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
            <div class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Chế độ</label>
                        <select wire:model.live="presentation.mode" class="{{ $fieldClass }}">
                            <option value="basic">Basic</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Container</label>
                        <select wire:model.live="presentation.container" class="{{ $fieldClass }}">
                            <option value="standard">Standard</option>
                            <option value="wide">Wide</option>
                            <option value="full">Full width</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Khoảng cách Section</label>
                        <select wire:model.live="presentation.spacing" class="{{ $fieldClass }}">
                            <option value="compact">Compact</option>
                            <option value="normal">Normal</option>
                            <option value="comfortable">Comfortable</option>
                        </select>
                    </div>
                </div>

                @if(($presentation['mode'] ?? 'basic') === 'advanced')
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-3 font-semibold text-gray-900">Advanced Tokens</div>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <div><label class="block text-sm font-semibold text-gray-700">Container width (px)</label><input type="number" min="960" max="1920" wire:model.live.debounce.300ms="presentation.custom.container_width" class="{{ $fieldClass }}"></div>
                            <div><label class="block text-sm font-semibold text-gray-700">Page padding (px)</label><input type="number" min="0" max="64" wire:model.live.debounce.300ms="presentation.custom.page_padding" class="{{ $fieldClass }}"></div>
                            <div><label class="block text-sm font-semibold text-gray-700">Desktop section gap (px)</label><input type="number" min="16" max="120" wire:model.live.debounce.300ms="presentation.custom.section_gap" class="{{ $fieldClass }}"></div>
                            <div><label class="block text-sm font-semibold text-gray-700">Mobile section gap (px)</label><input type="number" min="12" max="96" wire:model.live.debounce.300ms="presentation.custom.mobile_section_gap" class="{{ $fieldClass }}"></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-100 p-4">
                <div class="mx-auto transition-all duration-200" :class="previewDevice === 'mobile' ? 'max-w-[390px]' : 'max-w-full'">
                    <div class="rounded-xl border border-gray-300 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-4 py-2 text-xs font-semibold text-gray-500" x-text="previewDevice === 'mobile' ? 'Mobile Preview · 390px' : 'Desktop Preview'"></div>
                        <div class="p-4" :style="`padding-left:${Math.min(Number(@js($presentation['custom']['page_padding'] ?? 16)), 40)}px;padding-right:${Math.min(Number(@js($presentation['custom']['page_padding'] ?? 16)), 40)}px`">
                            <div class="space-y-3">
                                @foreach($sectionCards as $card)
                                    @php($state = $layout[$card['layout_key']] ?? 'all')
                                    @if(!in_array($state, ['none', 'hidden'], true))
                                        <div
                                            x-show="previewDevice === 'desktop' ? @js($state !== 'mobile') : @js($state !== 'desktop')"
                                            class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="min-w-0"><div class="truncate text-sm font-bold text-gray-800">{{ $card['label'] }}</div><div class="truncate text-xs text-gray-500">{{ $card['description'] }}</div></div>
                                                <span class="shrink-0 rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-gray-500">{{ $state }}</span>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('Website::livewire.admin.home.partials.layout-themes')
    @include('Website::livewire.admin.home.home-settings-v2')
</div>

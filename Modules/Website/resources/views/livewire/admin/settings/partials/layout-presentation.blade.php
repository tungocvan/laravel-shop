<section class="space-y-5 border-t border-slate-200 pt-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="font-bold text-slate-900">Website Layout Presentation</h3>
            <p class="mt-1 text-sm text-slate-500">Điều chỉnh khung nội dung toàn site. Header, Homepage và Footer vẫn giữ presentation riêng của từng hệ thống.</p>
        </div>
        <button type="button" wire:click="resetLayoutPresentation" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Khôi phục mặc định</button>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
        <label class="{{ $labelClass }}">Nền toàn trang
            <select wire:model="layoutPresentation.body.background" class="{{ $fieldClass }}">
                <option value="background">Background token</option>
                <option value="surface">Surface token</option>
            </select>
        </label>
        <label class="{{ $labelClass }}">Nền vùng nội dung
            <select wire:model="layoutPresentation.main.background" class="{{ $fieldClass }}">
                <option value="transparent">Trong suốt</option>
                <option value="background">Background token</option>
                <option value="surface">Surface token</option>
            </select>
        </label>
        <label class="{{ $labelClass }}">Chiều rộng nội dung
            <select wire:model="layoutPresentation.main.container" class="{{ $fieldClass }}">
                <option value="full">Full width</option>
                <option value="wide">Wide</option>
                <option value="standard">Standard</option>
                <option value="compact">Compact</option>
            </select>
        </label>
        <label class="{{ $labelClass }}">Canh vùng nội dung
            <select wire:model="layoutPresentation.main.alignment" class="{{ $fieldClass }}">
                <option value="center">Giữa trang</option>
                <option value="left">Bên trái</option>
            </select>
        </label>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach(['desktop' => 'Desktop', 'mobile' => 'Mobile'] as $device => $deviceLabel)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-4 font-bold text-slate-900">{{ $deviceLabel }} spacing</div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="{{ $labelClass }}">Padding trên (px)
                        <input type="number" min="0" max="160" wire:model="layoutPresentation.main.{{ $device }}.padding_top" class="{{ $fieldClass }}">
                    </label>
                    <label class="{{ $labelClass }}">Padding dưới (px)
                        <input type="number" min="0" max="160" wire:model="layoutPresentation.main.{{ $device }}.padding_bottom" class="{{ $fieldClass }}">
                    </label>
                    <label class="{{ $labelClass }}">Padding ngang (px)
                        <input type="number" min="0" max="96" wire:model="layoutPresentation.main.{{ $device }}.padding_x" class="{{ $fieldClass }}">
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div>
            <div class="font-semibold text-slate-900">Smooth scroll</div>
            <div class="mt-1 text-xs text-slate-500">Cuộn mượt khi điều hướng tới anchor trong cùng trang.</div>
        </div>
        <input type="checkbox" wire:model="layoutPresentation.scroll.smooth" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
    </label>
</section>

<section class="border-t border-slate-200 pt-6">
    @include('Website::livewire.admin.settings.partials.appearance')
</section>

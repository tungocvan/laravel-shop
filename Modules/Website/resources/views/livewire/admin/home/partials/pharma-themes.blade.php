<section class="space-y-5 rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Themes Pharma</h2>
        <p class="mt-1 text-sm text-slate-500">Bộ màu riêng cho Website dược phẩm. Không sử dụng hoặc ghi đè Homepage Layout Themes của Default.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach(['clinical' => ['Clinical Green', '#047857', '#10b981'], 'ocean' => ['Medical Ocean', '#075985', '#06b6d4'], 'modern' => ['Modern Navy', '#1e3a8a', '#6366f1']] as $key => $preset)
            <button type="button" wire:key="pharma-theme-{{ $key }}" wire:click="choosePharmaTheme('{{ $key }}')" class="overflow-hidden rounded-xl border-2 bg-white text-left transition hover:shadow-md {{ $pharmaTheme === $key ? 'border-emerald-600 ring-2 ring-emerald-100' : 'border-slate-200' }}" aria-pressed="{{ $pharmaTheme === $key ? 'true' : 'false' }}">
                <div class="flex h-20 items-center justify-center gap-3" style="background: {{ $preset[1] }}">
                    <span class="h-9 w-9 rounded-lg border border-white/40" style="background: {{ $preset[2] }}"></span>
                    <span class="text-sm font-bold text-white">Pharma</span>
                </div>
                <div class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $preset[0] }} @if($pharmaTheme === $key) ✓ @endif</div>
            </button>
        @endforeach
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="pharma-primary" class="mb-1 block text-sm font-semibold text-slate-700">Màu chủ đạo</label>
            <input id="pharma-primary" type="text" wire:model="pharmaPrimaryColor" placeholder="#047857" maxlength="7" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            @error('pharmaPrimaryColor')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="pharma-accent" class="mb-1 block text-sm font-semibold text-slate-700">Màu nhấn</label>
            <input id="pharma-accent" type="text" wire:model="pharmaAccentColor" placeholder="#10b981" maxlength="7" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            @error('pharmaAccentColor')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <p class="text-xs text-amber-700">Cấu hình được lưu riêng cho Pharma. Chưa áp dụng màu này vào giao diện công khai cho đến khi hoàn tất renderer và UI gate.</p>
</section>

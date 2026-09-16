@if($open && $activeSection==='brand')
<div class="fixed bottom-20 right-8 z-[92] w-[360px] max-w-[calc(100vw-2rem)] rounded-2xl border border-indigo-100 bg-white p-4 shadow-2xl lg:right-10">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-indigo-600">Kích thước ảnh Excel</p>
            <h4 class="mt-1 text-sm font-extrabold text-slate-900">Logo & chữ ký</h4>
            <p class="mt-1 text-[11px] leading-4 text-slate-500">Đơn vị cm. Kích thước được lưu riêng theo profile hiện tại.</p>
        </div>
        <span class="rounded-lg bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">Custom</span>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
        <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
            <p class="text-xs font-extrabold text-slate-800">Logo</p>
            <p class="mt-0.5 text-[10px] text-slate-400">Vùng A:C</p>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <label class="text-[10px] font-bold text-slate-600">Rộng
                    <input type="number" min="1" max="12" step="0.05" wire:model="headerFooter.logo_width_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-50">
                </label>
                <label class="text-[10px] font-bold text-slate-600">Cao
                    <input type="number" min="1" max="8" step="0.05" wire:model="headerFooter.logo_height_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-50">
                </label>
            </div>
            <p class="mt-2 text-[10px] text-slate-400">Mặc định 4,65 × 2,82 cm</p>
        </section>

        <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
            <p class="text-xs font-extrabold text-slate-800">Chữ ký</p>
            <p class="mt-0.5 text-[10px] text-slate-400">Giữa 5 cột cuối</p>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <label class="text-[10px] font-bold text-slate-600">Rộng
                    <input type="number" min="1" max="12" step="0.05" wire:model="headerFooter.signature_width_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-50">
                </label>
                <label class="text-[10px] font-bold text-slate-600">Cao
                    <input type="number" min="1" max="8" step="0.05" wire:model="headerFooter.signature_height_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-50">
                </label>
            </div>
            <p class="mt-2 text-[10px] text-slate-400">Mặc định 4,00 × 3,60 cm</p>
        </section>
    </div>

    @error('headerFooter.logo_width_cm')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    @error('headerFooter.logo_height_cm')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    @error('headerFooter.signature_width_cm')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    @error('headerFooter.signature_height_cm')<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
</div>
@endif

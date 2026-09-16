@if($open && $activeSection==='brand')
<div class="hidden" aria-hidden="true">
    <div
        data-pharma-media-sizing="logo"
        x-data
        x-init="$nextTick(() => { const cards=[...document.querySelectorAll('section.rounded-2xl div.rounded-xl')]; const target=cards.find(card => [...card.querySelectorAll('p')].some(p => p.textContent.trim()==='Logo công ty')); if(target && $el.parentElement!==target){ target.appendChild($el); $el.classList.remove('hidden'); } })"
        class="hidden mt-3 border-t border-slate-200 pt-3"
    >
        <div class="flex items-center justify-between gap-2">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.12em] text-indigo-600">Kích thước xuất Excel</p>
                <p class="mt-0.5 text-[10px] text-slate-400">Logo · vùng A:C · đơn vị cm</p>
            </div>
            <span class="rounded-md bg-indigo-50 px-2 py-1 text-[9px] font-bold text-indigo-700">Custom</span>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <label class="text-[10px] font-bold text-slate-600">Rộng (cm)
                <input type="number" min="1" max="12" step="0.05" wire:model="headerFooter.logo_width_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-50">
            </label>
            <label class="text-[10px] font-bold text-slate-600">Cao (cm)
                <input type="number" min="1" max="8" step="0.05" wire:model="headerFooter.logo_height_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-50">
            </label>
        </div>
        <p class="mt-2 text-[10px] text-slate-400">Mặc định 4,65 × 2,82 cm</p>
        @error('headerFooter.logo_width_cm')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        @error('headerFooter.logo_height_cm')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div
        data-pharma-media-sizing="signature"
        x-data
        x-init="$nextTick(() => { const cards=[...document.querySelectorAll('section.rounded-2xl div.rounded-xl')]; const target=cards.find(card => [...card.querySelectorAll('p')].some(p => p.textContent.trim()==='Ảnh chữ ký')); if(target && $el.parentElement!==target){ target.appendChild($el); $el.classList.remove('hidden'); } })"
        class="hidden mt-3 border-t border-slate-200 pt-3"
    >
        <div class="flex items-center justify-between gap-2">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.12em] text-violet-600">Kích thước xuất Excel</p>
                <p class="mt-0.5 text-[10px] text-slate-400">Chữ ký · giữa 5 cột cuối · đơn vị cm</p>
            </div>
            <span class="rounded-md bg-violet-50 px-2 py-1 text-[9px] font-bold text-violet-700">Custom</span>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <label class="text-[10px] font-bold text-slate-600">Rộng (cm)
                <input type="number" min="1" max="12" step="0.05" wire:model="headerFooter.signature_width_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-50">
            </label>
            <label class="text-[10px] font-bold text-slate-600">Cao (cm)
                <input type="number" min="1" max="8" step="0.05" wire:model="headerFooter.signature_height_cm" class="mt-1 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-50">
            </label>
        </div>
        <p class="mt-2 text-[10px] text-slate-400">Mặc định 4,00 × 3,60 cm</p>
        @error('headerFooter.signature_width_cm')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        @error('headerFooter.signature_height_cm')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>
@endif

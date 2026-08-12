<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h3 class="text-sm font-black uppercase tracking-wide text-gray-900">Snapshot cấu hình ENV</h3>
            <p class="mt-1 text-xs text-gray-600">
                Snapshot chứa thông tin bí mật và được lưu trong vùng private của ứng dụng. Hệ thống chỉ giữ 5 bản gần nhất cho mỗi loại.
            </p>
            @unless($canUpdate)
                <p class="mt-2 text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem cấu hình ENV.</p>
            @endunless
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                wire:click="createSnapshot('production')"
                wire:confirm="Tạo snapshot Production chứa toàn bộ cấu hình và secret hiện tại?"
                wire:loading.attr="disabled"
                wire:target="createSnapshot('production')"
                @disabled(!$canUpdate)
                class="rounded-lg bg-red-600 px-4 py-2 text-[11px] font-black uppercase text-white shadow disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="createSnapshot('production')">Snapshot Production</span>
                <span wire:loading wire:target="createSnapshot('production')">Đang tạo...</span>
            </button>

            <button
                type="button"
                wire:click="createSnapshot('local')"
                wire:confirm="Tạo snapshot Local chứa toàn bộ cấu hình và secret hiện tại?"
                wire:loading.attr="disabled"
                wire:target="createSnapshot('local')"
                @disabled(!$canUpdate)
                class="rounded-lg bg-gray-800 px-4 py-2 text-[11px] font-black uppercase text-white shadow disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="createSnapshot('local')">Snapshot Local</span>
                <span wire:loading wire:target="createSnapshot('local')">Đang tạo...</span>
            </button>
        </div>
    </div>
</div>

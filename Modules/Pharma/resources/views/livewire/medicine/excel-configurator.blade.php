<div>
@php
    $input = 'mt-1.5 block h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100';
    $selectedOrder = collect($columns)->filter(fn ($key) => $selected[$key] ?? false)->values();
    $excelLetter = function (int $number): string {
        $label = '';
        while ($number > 0) { $number--; $label = chr(65 + $number % 26).$label; $number = intdiv($number, 26); }
        return $label;
    };
@endphp
<button type="button" wire:click="openConfig" class="inline-flex min-h-11 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Cấu hình Excel</button>
@if($open)
<div class="fixed inset-0 z-[90] bg-slate-950/60 p-2 backdrop-blur-[2px] sm:p-4 lg:p-6" role="dialog" aria-modal="true" aria-labelledby="medicine-excel-title">
    <div class="mx-auto flex h-full max-h-[94vh] w-full max-w-[1560px] flex-col overflow-hidden rounded-3xl border border-white/70 bg-white shadow-2xl">
        <header class="flex shrink-0 items-center justify-between border-b border-slate-200 px-5 py-4 lg:px-7">
            <div><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest text-indigo-700">Excel Designer · Medicine Master</span>
                <h2 id="medicine-excel-title" class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">Bố cục xuất Danh mục thuốc</h2>
                <p class="mt-1 text-xs text-slate-500">Thiết kế dữ liệu, Inspector từng cột và trang in. Không thay đổi Import / Export chuẩn.</p>
            </div>
            <button type="button" wire:click="closeConfig" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-xl text-slate-500">×</button>
        </header>
        <div class="shrink-0 border-b border-slate-200 bg-slate-50/80 px-5 py-3 lg:px-7">
            <div class="flex flex-wrap items-end gap-2">
                <label class="min-w-[170px] text-[10px] font-bold uppercase text-slate-500">Profile
                    <select wire:model.live="profileId" class="{{ $input }}"><option value="">Mặc định</option>@foreach($profiles as $profile)<option value="{{ $profile['id'] }}">{{ $profile['name'] }}{{ $profile['is_default'] ? ' · Mặc định' : '' }}</option>@endforeach</select>
                </label>
                <label class="min-w-[180px] flex-1 text-[10px] font-bold uppercase text-slate-500">Tên cấu hình
                    <input wire:model="profileName" class="{{ $input }}">
                </label>
                <label class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold"><input type="checkbox" wire:model="isDefault"> Đặt mặc định</label>
                <button type="button" wire:click="duplicateProfile" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700">Nhân đôi</button>
                <button type="button" wire:click="newProfile" class="h-10 rounded-xl border border-indigo-200 bg-white px-3 text-xs font-bold text-indigo-700">+ Tạo mới</button>
                <button type="button" wire:click="deleteProfile" wire:confirm="Xóa cấu hình này?" @disabled(!$profileId) class="h-10 rounded-xl border border-rose-200 bg-white px-3 text-xs font-bold text-rose-600 disabled:opacity-40">Xóa</button>
            </div>
        </div>
        <div class="medicine-designer-workspace min-h-0 flex-1 overflow-hidden">
            <nav class="medicine-designer-sidebar shrink-0 border-b border-slate-200 bg-slate-50/70 p-3 lg:border-b-0 lg:border-r lg:p-4">
                <div class="flex gap-2 lg:flex-col">
                    @foreach([['brand','1','Thương hiệu'],['columns','2','Cột dữ liệu'],['page','3','Trang in']] as [$section,$number,$title])
                    <button type="button" wire:click="setSection('{{ $section }}')" class="flex flex-1 items-center gap-2 rounded-xl border px-3 py-3 text-left text-xs font-bold lg:flex-none {{ $activeSection === $section ? 'border-indigo-200 bg-white shadow-sm' : 'border-transparent hover:bg-white' }}">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $activeSection === $section ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $number }}</span><span>{{ $title }}</span>
                    </button>
                    @endforeach
                </div>
                <div class="mt-5 hidden rounded-2xl border border-slate-200 bg-white p-4 lg:block">
                    <p class="text-[10px] font-bold uppercase text-slate-400">Profile hiện tại</p>
                    <p class="mt-1 truncate text-sm font-bold">{{ $profileName }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ $selectedOrder->count() }}/{{ count($definitions) }} cột được xuất</p>
                </div>
            </nav>
            <main class="min-h-0 overflow-y-auto p-4 lg:p-6">
                @if($activeSection === 'brand')
                <div class="mx-auto max-w-5xl space-y-5">
                    <div class="flex items-center justify-between gap-3"><div><h3 class="text-lg font-bold">Thương hiệu &amp; nội dung</h3><p class="text-sm text-slate-500">Thông tin xuất hiện trên báo cáo danh mục thuốc.</p></div>
                        <label class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold"><input type="checkbox" wire:model="settings.header_enabled"> Header / Footer</label>
                    </div>
                    <section class="overflow-hidden rounded-2xl border border-slate-200"><h4 class="border-b bg-slate-50 px-4 py-3 text-sm font-bold">Thông tin doanh nghiệp</h4>
                        <div class="grid gap-4 p-4 sm:grid-cols-2">
                            <label class="text-xs font-semibold sm:col-span-2">Tên công ty<input wire:model="settings.company_name" class="{{ $input }}"></label>
                        </div>
                    </section>
                    <section class="overflow-hidden rounded-2xl border border-slate-200"><h4 class="border-b bg-slate-50 px-4 py-3 text-sm font-bold">Nội dung báo cáo</h4>
                        <div class="p-4"><label class="text-xs font-semibold">Tiêu đề<input wire:model="settings.title" class="{{ $input }}"></label>
                        <p class="mt-3 text-xs text-slate-500">Tiêu đề và tên doanh nghiệp được căn giữa trong Excel khi bật Header / Footer.</p></div>
                    </section>
                    <section class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4"><h4 class="text-sm font-bold">Tách biệt dữ liệu</h4><p class="mt-2 text-sm text-slate-600">Đây là mẫu báo cáo riêng. File Export danh mục thuốc chuẩn để import trở lại vẫn giữ nguyên định dạng và chức năng.</p></section>
                </div>
                @elseif($activeSection === 'columns')
                <div class="mx-auto max-w-[1320px]" wire:ignore wire:key="medicine-excel-v4-{{ $designerRevision }}" x-data="{
                    search: '',
                    editing: null,
                    dragging: null,
                    draft: {
                        columns: @js($selectedOrder->all()),
                        headers: @js($headers),
                        widths: @js($widths),
                        alignments: @js($alignments)
                    },
                    labels: @js($definitions),
                    letter(index) {
                        let value = index + 1, result = '';
                        while (value > 0) { value--; result = String.fromCharCode(65 + value % 26) + result; value = Math.floor(value / 26); }
                        return result;
                    },
                    available(key) { return this.draft.columns.includes(key); },
                    add(key) { if (!this.labels[key]) return; if (!this.available(key)) this.draft.columns.push(key); if (!this.draft.headers[key]) this.draft.headers[key] = this.labels[key]; if (!this.draft.widths[key]) this.draft.widths[key] = 130; if (!this.draft.alignments[key]) this.draft.alignments[key] = 'left'; this.editing = key; },
                    remove(key) { this.draft.columns = this.draft.columns.filter(item => item !== key); if (this.editing === key) this.editing = null; },
                    move(key, position) {
                        const old = this.draft.columns.indexOf(key);
                        if (old < 0) return;
                        const target = Math.max(0, Math.min(this.draft.columns.length - 1, Number(position) - 1));
                        this.draft.columns.splice(old, 1);
                        this.draft.columns.splice(target, 0, key);
                    },
                    selectAll() { this.draft.columns = Object.keys(this.labels); },
                    reset() { const chosen = new Set(this.draft.columns); this.draft.columns = Object.keys(this.labels).filter(key => chosen.has(key)); },
                    payload() { return JSON.parse(JSON.stringify(this.draft)); }
                }" x-on:medicine-designer-save.window="$wire.saveDraft(payload())">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><h3 class="text-xl font-extrabold">Thiết kế cột Excel · V4</h3><p class="mt-1 text-xs text-slate-500">Chỉnh sửa tức thì trên trình duyệt. Chỉ gửi dữ liệu khi lưu.</p></div>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="selectAll()" class="rounded-xl border px-3 py-2 text-xs font-bold">Chọn tất cả</button>
                            <button type="button" x-on:click="reset()" class="rounded-xl border px-3 py-2 text-xs font-bold">Thứ tự chuẩn</button>
                            <button type="button" x-on:click="draft.columns = []; editing = null" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600">Bỏ chọn</button>
                        </div>
                    </div>
                    @error('columns')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                    <div class="medicine-designer-columns mt-4">
                        <section class="medicine-designer-pane overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div class="border-b bg-slate-50 p-3"><h4 class="text-sm font-bold">1. Kho dữ liệu</h4><input type="search" x-model.debounce.100ms="search" placeholder="Tìm tên cột..." class="{{ $input }}"></div>
                            <div class="medicine-designer-scroll space-y-1 overflow-y-auto p-3">
                                @foreach(\Modules\Pharma\Services\MedicineExcelProfileService::BASE_COLUMNS as $key => $label)
                                <div x-show="!search || @js(mb_strtolower($label.' '.$key)).includes(search.toLowerCase())" class="flex items-center gap-2 rounded-lg border border-slate-100 px-2 py-2" wire:key="v4-bank-{{ $key }}">
                                    <span class="min-w-0 flex-1 truncate text-xs font-semibold">{{ $label }}</span>
                                    <span x-show="available(@js($key))" class="text-xs text-emerald-600">✓</span>
                                    <button type="button" x-show="!available(@js($key))" x-on:click="add(@js($key))" class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">+ Thêm</button>
                                </div>
                                @endforeach
                                @foreach($relatedGroups as $groupKey => $group)
                                <div class="mt-3 border-t pt-3" wire:key="v4-group-{{ $groupKey }}">
                                    <h5 class="mb-2 text-xs font-bold text-indigo-700">{{ $group['label'] }}</h5>
                                    @foreach($group['fields'] as $key => $label)
                                    <div x-show="!search || @js(mb_strtolower($label.' '.$key)).includes(search.toLowerCase())" class="flex items-center gap-2 rounded-lg border border-slate-100 px-2 py-2" wire:key="v4-related-{{ $key }}">
                                        <span class="min-w-0 flex-1 truncate text-xs">{{ $label }}</span>
                                        <span x-show="available(@js($key))" class="text-xs text-emerald-600">✓</span>
                                        <button type="button" x-show="!available(@js($key))" x-on:click="add(@js($key))" class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">+ Thêm</button>
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </section>
                        <section class="medicine-designer-pane overflow-hidden rounded-2xl border border-indigo-100 bg-white">
                            <div class="border-b bg-indigo-50 p-3"><h4 class="text-sm font-bold">2. Cột sẽ xuất Excel <span class="text-indigo-600" x-text="'(' + draft.columns.length + ')'"></span></h4><p class="mt-1 text-xs text-slate-500">Nhấn bánh răng để thiết lập; nhập vị trí để chuyển nhanh.</p></div>
                            <div class="medicine-designer-scroll space-y-1 overflow-y-auto p-3">
                                <template x-for="(key, index) in draft.columns" :key="key">
                                    <div class="flex items-center gap-2 rounded-lg border p-2" :class="editing === key ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200'" draggable="true" x-on:dragstart="dragging = key; $event.dataTransfer.effectAllowed = 'move'" x-on:dragover.prevent x-on:drop.prevent="if (dragging && dragging !== key) move(dragging, index + 1); dragging = null" x-on:dragend="dragging = null">
                                        <span class="cursor-grab text-slate-400" title="Kéo để đổi vị trí">⠿</span><span class="grid h-7 w-7 shrink-0 place-items-center rounded bg-indigo-600 text-[11px] font-bold text-white" x-text="letter(index)"></span>
                                        <button type="button" x-on:click="editing = key" class="min-w-0 flex-1 truncate text-left text-xs font-semibold" x-text="draft.headers[key] || labels[key]"></button>
                                        <input type="number" min="1" :max="draft.columns.length" :value="index + 1" x-on:change="move(key, $event.target.value)" class="h-8 w-12 rounded border text-center text-xs" title="Chuyển đến vị trí" aria-label="Vị trí cột">
                                        <button type="button" x-on:click="move(key, index)" class="rounded border px-1 text-xs" aria-label="Lên">↑</button>
                                        <button type="button" x-on:click="move(key, index + 2)" class="rounded border px-1 text-xs" aria-label="Xuống">↓</button>
                                        <button type="button" x-on:click="editing = key" class="rounded border px-1 text-xs" aria-label="Thiết lập cột">⚙</button>
                                        <button type="button" x-on:click="remove(key)" class="rounded border px-1 text-xs text-rose-600" aria-label="Bỏ cột">×</button>
                                    </div>
                                </template>
                                <p x-show="draft.columns.length === 0" class="p-5 text-center text-xs text-slate-500">Chưa chọn cột nào.</p>
                            </div>
                        </section>
                        <section class="medicine-designer-pane rounded-2xl border border-slate-200 bg-white p-4">
                            <h4 class="text-sm font-bold">3. Thiết lập cột</h4>
                            <p x-show="!editing" class="mt-4 text-xs text-slate-500">Chọn ⚙ tại cột cần chỉnh. Không tải lại giao diện.</p>
                            <div x-show="editing" x-cloak class="mt-4 space-y-4">
                                <p class="rounded-lg bg-indigo-50 p-3 text-xs font-bold text-indigo-700" x-text="labels[editing] || editing"></p>
                                <label class="block text-xs font-semibold">Tên tiêu đề<input type="text" x-model="draft.headers[editing]" class="{{ $input }}"></label>
                                <label class="block text-xs font-semibold">Độ rộng (px)<input type="number" min="40" max="400" x-model.number="draft.widths[editing]" class="{{ $input }}"></label>
                                <div class="grid grid-cols-5 gap-1">@foreach(['XS'=>60,'S'=>90,'M'=>130,'L'=>190,'XL'=>280] as $size => $pixels)<button type="button" x-on:click="draft.widths[editing] = {{ $pixels }}" class="rounded-lg border py-2 text-[10px] font-bold">{{ $size }}</button>@endforeach</div>
                                <label class="block text-xs font-semibold">Căn lề<select x-model="draft.alignments[editing]" class="{{ $input }}"><option value="left">Trái</option><option value="center">Giữa</option><option value="right">Phải</option></select></label>
                                <button type="button" x-on:click="editing = null" class="rounded-lg border px-3 py-2 text-xs font-bold">Đóng thiết lập</button>
                            </div>
                        </section>
                    </div>
                </div>
                @elseif($activeSection === 'page')
                <div class="mx-auto max-w-5xl space-y-5">
                    <div><h3 class="text-lg font-bold">Trang in</h3><p class="text-sm text-slate-500">Thiết lập khổ giấy, hướng trang và xem trước bố cục.</p></div>
                    <div class="grid gap-5 lg:grid-cols-2">
                        <section class="rounded-2xl border p-4"><h4 class="text-sm font-bold">Khổ giấy &amp; hướng trang</h4><div class="mt-3 grid grid-cols-2 gap-3">
                            <label class="text-xs font-semibold">Khổ giấy<select wire:model="settings.paper_size" class="{{ $input }}"><option>A4</option><option>A3</option><option>LETTER</option></select></label>
                            <label class="text-xs font-semibold">Hướng<select wire:model="settings.orientation" class="{{ $input }}"><option value="landscape">Ngang</option><option value="portrait">Dọc</option></select></label>
                        </div></section>
                        <section class="rounded-2xl border p-4">
                            <h4 class="text-sm font-bold">Kiểu chữ &amp; bảng dữ liệu Excel</h4>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <label class="text-xs font-semibold col-span-2">Font chữ<select wire:model="settings.font_family" class="{{ $input }}"><option>Times New Roman</option><option>Arial</option><option>Calibri</option></select></label>
                                <label class="text-xs font-semibold">Cỡ chữ header<input type="number" min="8" max="20" wire:model="settings.header_font_size" class="{{ $input }}"></label>
                                <label class="text-xs font-semibold">Cỡ chữ dữ liệu<input type="number" min="8" max="20" wire:model="settings.body_font_size" class="{{ $input }}"></label>
                                <label class="text-xs font-semibold col-span-2">Màu nền header<select wire:model="settings.header_fill" class="{{ $input }}"><option value="EFF4FA">Xanh nhạt</option><option value="F1F5F9">Xám nhạt</option><option value="EDE9FE">Tím nhạt</option><option value="FFFFFF">Trắng</option></select></label>
                                <label class="col-span-2 inline-flex items-center gap-2 text-xs font-semibold"><input type="checkbox" wire:model="settings.body_border"> Viền mảnh giữa các ô dữ liệu</label>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Mặc định: Times New Roman · Header 12pt có nền nhạt, wrap text · Dữ liệu 11pt có viền mảnh.</p>
                        </section>
                        <section class="rounded-2xl border p-4"><h4 class="text-sm font-bold">Xem trước</h4><div class="mt-3 rounded-lg border bg-slate-50 p-4 text-center"><p class="text-xs font-bold">{{ $settings['company_name'] ?? '' }}</p><p class="mt-2 text-sm font-extrabold">{{ $settings['title'] ?? '' }}</p><div class="mt-3 grid grid-cols-3 border bg-white text-[10px] font-bold"><span class="border-r p-2">Mã thuốc</span><span class="border-r p-2">Tên thuốc</span><span class="p-2">Loại sản phẩm</span></div></div></section>
                    </div>
                </div>
                @endif
            </main>
        </div>
        @if($saveConfirmationOpen)
        <div class="absolute inset-0 z-[110] flex items-center justify-center bg-slate-950/50 p-4" role="alertdialog" aria-modal="true" aria-labelledby="medicine-excel-saved-title">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-xl font-bold text-emerald-700">✓</div>
                <h3 id="medicine-excel-saved-title" class="mt-3 text-lg font-extrabold text-slate-900">Đã lưu cấu hình Excel</h3>
                <p class="mt-2 text-sm text-slate-600">Cấu hình <strong>{{ $profileName }}</strong> đã được lưu thành công.</p>
                <button type="button" wire:click="dismissSaveConfirmation" class="mt-5 h-10 w-full rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white">Đóng</button>
            </div>
        </div>
        @endif
        <footer class="flex shrink-0 items-center justify-between gap-3 border-t bg-white px-5 py-3 lg:px-7">
            <p class="min-w-0 truncate text-xs text-slate-500"><b>{{ $profileName }}</b> · {{ $selectedOrder->count() }} cột được chọn</p>
            <div class="flex shrink-0 gap-2"><button type="button" wire:click="closeConfig" class="h-10 rounded-xl border px-4 text-sm font-bold">Hủy</button><button type="button" @if($activeSection === 'columns') x-on:click="$dispatch('medicine-designer-save')" @else wire:click="save" @endif class="h-10 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white">Lưu cấu hình</button></div>
        </footer>
    </div>
</div>
@endif
<style>
@media (min-width: 1024px) {
  .medicine-designer-workspace { display:grid !important; grid-template-columns: 190px minmax(0,1fr) !important; }
  .medicine-designer-sidebar { min-width:0; overflow-y:auto; }
}
.medicine-designer-columns { display:grid; grid-template-columns:minmax(0,1fr); gap:14px; align-items:start; }
.medicine-designer-pane { min-width:0; }
.medicine-designer-scroll { max-height:43vh; }
@media (min-width: 1200px) {
  .medicine-designer-columns { grid-template-columns:minmax(200px,.9fr) minmax(280px,1.15fr) minmax(235px,.9fr) !important; }
}
@media (min-width: 1024px) and (max-width: 1199px) {
  .medicine-designer-columns { grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
  .medicine-designer-columns > :last-child { grid-column:1/-1; }
}
</style>
</div>

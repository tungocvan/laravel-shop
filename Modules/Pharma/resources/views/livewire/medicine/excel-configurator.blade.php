<div>
    <button type="button" wire:click="openConfig" class="inline-flex min-h-11 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Cấu hình Excel</button>
    @if($open)
        <div class="fixed inset-0 z-[90] bg-slate-950/60 p-2 sm:p-5" role="dialog" aria-modal="true" aria-labelledby="medicine-excel-title">
            <div class="mx-auto flex h-full max-h-[94vh] max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-4 sm:px-6">
                    <div>
                        <h2 id="medicine-excel-title" class="text-xl font-bold text-slate-900">Cấu hình Excel · Danh mục thuốc</h2>
                        <p class="mt-1 text-xs text-slate-500">Profile độc lập với bảng giá và file Import / Export chuẩn.</p>
                    </div>
                    <button type="button" wire:click="closeConfig" class="rounded-xl border px-4 py-2 text-sm font-semibold">Đóng ×</button>
                </header>
                <div class="grid gap-3 border-b bg-slate-50 p-4 sm:grid-cols-[1fr_1fr_auto]">
                    <label class="text-xs font-semibold">Cấu hình đã lưu
                        <select wire:model.live="profileId" class="mt-1 block h-10 w-full rounded-xl border px-3 text-sm">
                            <option value="">Mặc định</option>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile['id'] }}">{{ $profile['name'] }}{{ $profile['is_default'] ? ' · Mặc định' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold">Tên cấu hình
                        <input wire:model="profileName" class="mt-1 block h-10 w-full rounded-xl border px-3 text-sm">
                        @error('profileName')<span class="text-rose-600">{{ $message }}</span>@enderror
                        @error('name')<span class="text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="flex flex-wrap items-end gap-2">
                        <button type="button" wire:click="newProfile" class="h-10 rounded-xl border px-3 text-xs font-semibold">+ Tạo mới</button>
                        <button type="button" wire:click="deleteProfile" wire:confirm="Xóa cấu hình này?" @disabled(!$profileId) class="h-10 rounded-xl border border-rose-200 px-3 text-xs font-semibold text-rose-600">Xóa</button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_290px]">
                        <section>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div><h3 class="font-bold">Cột dữ liệu</h3><p class="text-xs text-slate-500">Chọn cột, đổi tên, độ rộng và thứ tự xuất.</p></div>
                                <div class="flex gap-2"><button type="button" wire:click="selectAll" class="rounded-lg border px-3 py-2 text-xs">Chọn tất cả</button><button type="button" wire:click="clearAll" class="rounded-lg border px-3 py-2 text-xs">Bỏ chọn</button></div>
                            </div>
                            @error('columns')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            <div class="mt-3 space-y-2">
                                @foreach($columns as $key)
                                    @if(isset($definitions[$key]))
                                        <div wire:key="medicine-excel-{{ $key }}" class="grid grid-cols-[24px_minmax(0,1fr)_56px] items-center gap-2 rounded-xl border p-3 sm:grid-cols-[24px_minmax(0,1fr)_85px_95px_68px]">
                                            <input type="checkbox" wire:model="selected.{{ $key }}" aria-label="Xuất {{ $definitions[$key] }}">
                                            <div class="min-w-0">
                                                <label class="block text-[10px] text-slate-500">{{ $definitions[$key] }}</label>
                                                <input wire:model="headers.{{ $key }}" class="mt-1 w-full rounded-lg border px-2 py-1.5 text-xs" aria-label="Tên cột {{ $definitions[$key] }}">
                                            </div>
                                            <input type="number" min="8" max="80" wire:model="widths.{{ $key }}" class="w-full rounded-lg border px-2 py-1.5 text-xs" title="Độ rộng Excel">
                                            <select wire:model="alignments.{{ $key }}" class="hidden w-full rounded-lg border px-1 py-1.5 text-xs sm:block" aria-label="Căn lề {{ $definitions[$key] }}">
                                                <option value="left">Trái</option><option value="center">Giữa</option><option value="right">Phải</option>
                                            </select>
                                            <div class="col-start-3 flex justify-end gap-1 sm:col-start-5">
                                                <button type="button" wire:click="move('{{ $key }}', -1)" class="rounded border px-1.5 py-1" aria-label="Đưa {{ $definitions[$key] }} lên">↑</button>
                                                <button type="button" wire:click="move('{{ $key }}', 1)" class="rounded border px-1.5 py-1" aria-label="Đưa {{ $definitions[$key] }} xuống">↓</button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                        <aside class="space-y-4">
                            <h3 class="font-bold">Thông tin &amp; trang in</h3>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isDefault"> Đặt làm cấu hình mặc định</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="settings.header_enabled"> Hiển thị tiêu đề doanh nghiệp</label>
                            <label class="block text-xs font-semibold">Tên doanh nghiệp<input wire:model="settings.company_name" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm"></label>
                            <label class="block text-xs font-semibold">Tiêu đề báo cáo<input wire:model="settings.title" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm"></label>
                            <label class="block text-xs font-semibold">Khổ giấy<select wire:model="settings.paper_size" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm"><option>A4</option><option>A3</option><option>LETTER</option></select></label>
                            <label class="block text-xs font-semibold">Hướng giấy<select wire:model="settings.orientation" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm"><option value="landscape">Ngang</option><option value="portrait">Dọc</option></select></label>
                            <p class="rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600">File báo cáo theo cấu hình không thay thế file Export danh mục thuốc chuẩn dùng để import dữ liệu.</p>
                        </aside>
                    </div>
                </div>
                <footer class="flex items-center justify-end gap-3 border-t bg-white px-4 py-3">
                    <button type="button" wire:click="closeConfig" class="rounded-xl border px-4 py-2 text-sm">Hủy</button>
                    <button type="button" wire:click="save" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white">Lưu cấu hình</button>
                </footer>
            </div>
        </div>
    @endif
</div>

@if ($showExportConfigModal)
    <div class="fixed inset-0 z-[110] overflow-hidden bg-black/50 p-2 sm:p-4" wire:click.self="closeExportConfig">
        <div class="mx-auto flex h-full w-full max-w-[1600px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-start justify-between border-b border-gray-200 bg-white px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Cấu hình xuất Excel</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900">Cột dữ liệu + Header/Footer + Logo + Chữ ký</h3>
                    <p class="mt-1 text-xs text-gray-500">Mỗi cấu hình lưu riêng toàn bộ bố cục. File xuất dùng Times New Roman.</p>
                </div>
                <button type="button" wire:click="closeExportConfig" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-500 hover:bg-gray-50">×</button>
            </div>

            <div class="shrink-0 border-b border-gray-200 bg-blue-50/50 px-5 py-4">
                <div class="grid gap-3 xl:grid-cols-[260px_minmax(0,1fr)_auto_auto] xl:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Cấu hình đang mở</label>
                        <select wire:model.live="activeExportProfileId" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm">
                            <option value="">Cấu hình mới</option>
                            @foreach ($exportProfiles as $profile)
                                <option value="{{ $profile['id'] }}">{{ $profile['name'] }}{{ $profile['is_default'] ? ' • Mặc định' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Tên cấu hình</label>
                        <input type="text" wire:model="exportProfileName" maxlength="120" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700">
                        <input type="checkbox" wire:model="exportProfileDefault"> Mặc định
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="newExportProfile" class="rounded-xl border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-700">+ Cấu hình mới</button>
                        <button type="button" wire:click="duplicateExportProfile" @disabled($activeExportProfileId === null) class="rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 disabled:opacity-40">⧉ Nhân đôi</button>
                        <button type="button" wire:click="deleteExportProfile" wire:confirm="Xóa cấu hình xuất Excel đang chọn?" @disabled($activeExportProfileId === null) class="rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 disabled:opacity-40">Xóa</button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-white/80 p-3">
                    <button type="button" wire:click="exportExportConfig" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">⇩ Export cấu hình JSON</button>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">
                        ⇧ Chọn file Import
                        <input type="file" wire:model="exportConfigImportUpload" accept="application/json,.json" class="hidden">
                    </label>
                    @if($exportConfigImportUpload)
                        <span class="max-w-64 truncate text-xs text-gray-600">{{ $exportConfigImportUpload->getClientOriginalName() }}</span>
                        <button type="button" wire:click="importExportConfig" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Import cấu hình</button>
                    @endif
                    @error('exportConfigImportUpload')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    <span class="text-xs text-gray-500">File JSON mang theo cột, Header/Footer, kích thước Logo/Chữ ký và hình ảnh.</span>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5" x-data="{ dragging: null }">
                <div class="mb-5 rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-indigo-950">Header & Footer của file Excel</p>
                            <p class="mt-1 text-xs text-indigo-700">Logo neo tại A1; Logo và Chữ ký dùng đúng Width/Height đã cấu hình.</p>
                        </div>
                        <label class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-800">
                            <input type="checkbox" wire:model.live="exportHeaderFooter.enabled"> Hiển thị Header/Footer
                        </label>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">Thông tin đầu trang</p>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div><label class="mb-1 block text-xs font-semibold">Tên công ty</label><input type="text" wire:model="exportHeaderFooter.company_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-xs font-semibold">Mã số thuế</label><input type="text" wire:model="exportHeaderFooter.tax_code" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-xs font-semibold">Địa chỉ</label><input type="text" wire:model="exportHeaderFooter.address" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-xs font-semibold">Số điện thoại</label><input type="text" wire:model="exportHeaderFooter.phone" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Email</label><input type="email" wire:model="exportHeaderFooter.email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Tiêu đề</label><input type="text" wire:model="exportHeaderFooter.title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Kính gửi</label><input type="text" wire:model="exportHeaderFooter.recipient" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Nội dung giới thiệu</label><textarea wire:model="exportHeaderFooter.intro" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">Logo & khu vực ký</p>
                            <div class="mb-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/40 p-3">
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <label class="text-xs font-bold text-gray-800">Logo công ty</label>
                                        @if($exportLogoPreview)<button type="button" wire:click="clearExportLogo" class="rounded-lg border border-red-200 bg-white px-2 py-1 text-[11px] font-semibold text-red-600">Xóa ảnh</button>@endif
                                    </div>
                                    <div class="flex min-h-32 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white p-2">
                                        @if($exportLogoPreview)
                                            <img src="{{ $exportLogoPreview }}" alt="Logo công ty" class="max-h-28 w-full object-contain">
                                        @else
                                            <div class="text-center text-xs text-gray-400"><div class="text-2xl">▧</div><div class="mt-1">Chưa có logo</div></div>
                                        @endif
                                    </div>
                                    <input type="file" wire:model="exportLogoUpload" accept="image/png,image/jpeg,image/webp" class="mt-3 block w-full text-xs">
                                    <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg border border-blue-100 bg-white p-2">
                                        <div><label class="mb-1 block text-[11px] font-semibold text-gray-600">Width (cm)</label><input type="number" min="0.5" max="15" step="0.01" wire:model.live.debounce.300ms="exportHeaderFooter.logo_width_cm" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs"></div>
                                        <div><label class="mb-1 block text-[11px] font-semibold text-gray-600">Height (cm)</label><input type="number" min="0.5" max="15" step="0.01" wire:model.live.debounce.300ms="exportHeaderFooter.logo_height_cm" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs"></div>
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-500">Mặc định: 2,48 × 3,83 cm. Excel dùng chính xác kích thước đã nhập.</p>
                                    @error('exportLogoUpload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div class="rounded-xl border border-dashed border-violet-200 bg-violet-50/40 p-3">
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <label class="text-xs font-bold text-gray-800">Ảnh chữ ký Giám đốc</label>
                                        @if($exportSignaturePreview)<button type="button" wire:click="clearExportSignature" class="rounded-lg border border-red-200 bg-white px-2 py-1 text-[11px] font-semibold text-red-600">Xóa ảnh</button>@endif
                                    </div>
                                    <div class="flex min-h-32 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white p-2">
                                        @if($exportSignaturePreview)
                                            <img src="{{ $exportSignaturePreview }}" alt="Chữ ký" class="max-h-28 w-full object-contain">
                                        @else
                                            <div class="text-center text-xs text-gray-400"><div class="text-2xl">✎</div><div class="mt-1">Chưa có chữ ký</div></div>
                                        @endif
                                    </div>
                                    <input type="file" wire:model="exportSignatureUpload" accept="image/png,image/jpeg,image/webp" class="mt-3 block w-full text-xs">
                                    <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg border border-violet-100 bg-white p-2">
                                        <div><label class="mb-1 block text-[11px] font-semibold text-gray-600">Width (cm)</label><input type="number" min="0.5" max="15" step="0.01" wire:model.live.debounce.300ms="exportHeaderFooter.signature_width_cm" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs"></div>
                                        <div><label class="mb-1 block text-[11px] font-semibold text-gray-600">Height (cm)</label><input type="number" min="0.5" max="15" step="0.01" wire:model.live.debounce.300ms="exportHeaderFooter.signature_height_cm" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs"></div>
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-500">Mặc định: 4,00 × 2,00 cm. Nên dùng PNG nền trong suốt.</p>
                                    @error('exportSignatureUpload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div><label class="mb-1 block text-xs font-semibold">Địa điểm ký</label><input type="text" wire:model="exportHeaderFooter.footer_location" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-xs font-semibold">Năm</label><input type="text" maxlength="4" wire:model="exportHeaderFooter.footer_year" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Chức danh người ký</label><input type="text" wire:model="exportHeaderFooter.signatory_title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                                <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold">Họ và tên người ký</label><input type="text" wire:model="exportHeaderFooter.signatory_name" placeholder="Ví dụ: Nguyễn Văn A" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4 flex flex-wrap gap-2"><button type="button" wire:click="selectAllExportColumns" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Chọn tất cả cột</button><button type="button" wire:click="clearAllExportColumns" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold">Bỏ chọn tất cả</button><span class="self-center text-xs text-gray-500">Kéo ⋮⋮ để đổi vị trí · Width 40–600 px · Decimal 0–6.</span></div>
                <div class="space-y-2">
                    @foreach($exportColumnOrder as $position => $key)
                        @php($definition=$exportColumnDefinitions[$key] ?? ['label'=>$key,'align'=>'left','width'=>140,'type'=>'auto'])
                        <div wire:key="export-column-{{ $key }}" draggable="true" @dragstart="dragging='{{ $key }}'" @dragover.prevent @drop.prevent="if(dragging && dragging !== '{{ $key }}'){$wire.moveExportColumn(dragging,'{{ $key }}')}" class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 2xl:grid-cols-[40px_48px_160px_minmax(170px,1fr)_130px_85px_110px_100px] 2xl:items-center">
                            <div class="cursor-grab text-center text-lg text-gray-400">⋮⋮</div><div class="text-center text-xs font-bold">{{ $position+1 }}</div>
                            <label class="flex min-w-0 items-center gap-2"><input type="checkbox" wire:model.live="exportSelectedColumns.{{ $key }}"><span class="truncate text-sm font-semibold">{{ $definition['label'] }}</span></label>
                            <input type="text" wire:model="exportHeaders.{{ $key }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                            <select wire:model.live="exportDataTypes.{{ $key }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs"><option value="auto">Auto</option><option value="number">Number</option><option value="string">String</option><option value="date">Date (dd/mm/yyyy)</option></select>
                            @if(($exportDataTypes[$key] ?? $definition['type'])==='number')<input type="number" min="0" max="6" wire:model.live="exportDecimals.{{ $key }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">@else<div class="text-center text-gray-400">—</div>@endif
                            <select wire:model.live="exportAlignments.{{ $key }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs"><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option></select>
                            <input type="number" min="40" max="600" wire:model.live.debounce.300ms="exportWidths.{{ $key }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                <p class="text-xs text-gray-500">Nội dung cuộn độc lập; thanh tiêu đề và nút Lưu luôn hiển thị.</p>
                <div class="flex gap-2"><button type="button" wire:click="closeExportConfig" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold">Đóng</button><button type="button" wire:click="saveExportConfig" wire:loading.attr="disabled" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white">Lưu cấu hình</button></div>
            </div>
        </div>
    </div>
@endif

@if($showExportSavedModal)
    <div class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 p-4" wire:click.self="closeExportSavedModal">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700">✓</div>
            <h3 class="mt-4 text-lg font-bold text-gray-950">Đã lưu cấu hình</h3>
            <p class="mt-2 text-sm text-gray-600">Cấu hình <strong>{{ $exportProfileName }}</strong> đã được lưu và có thể sử dụng cho Admin/PWA khi xuất Excel.</p>
            <button type="button" wire:click="closeExportSavedModal" class="mt-5 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Đã hiểu</button>
        </div>
    </div>
@endif

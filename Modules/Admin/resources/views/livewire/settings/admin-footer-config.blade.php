@php
    $control = 'mt-2 block h-10 w-full rounded-lg border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $toggle = 'h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
    $currentYear = now()->year;
    $startYear = data_get($config, 'footer.copyright.start_year');
    $previewYear = $startYear && (int) $startYear < $currentYear ? $startYear.'–'.$currentYear : (string) $currentYear;
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-5 flex items-center justify-between gap-4">
        <div><p class="text-sm text-slate-500">Footer nên ngắn gọn, dễ đọc và chỉ chứa thông tin có giá trị lâu dài.</p></div>
        <button type="button" wire:click="resetSection" wire:confirm="Khôi phục Footer về mặc định?" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Khôi phục Footer</button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-5">
                        <div><h2 class="text-base font-semibold text-slate-900">Hiển thị Footer</h2><p class="mt-1 text-sm leading-6 text-slate-500">Bật hoặc tắt toàn bộ Footer trên Admin shell.</p></div>
                        <input type="checkbox" wire:model.live="config.layout.show_footer" class="{{ $toggle }}">
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 px-4 py-3 text-xs leading-5 text-slate-600">Footer được thiết kế như một vùng thông tin phụ. Khi tắt, toàn bộ Copyright và Date/Time sẽ không render.</div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Bản quyền</h2><p class="mt-1 text-sm leading-6 text-slate-500">Thông tin sở hữu được quản trị có cấu trúc, không cho nhập HTML tự do.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-800">Hiển thị bản quyền</span><span class="mt-0.5 block text-xs text-slate-500">Hiển thị © và thông tin chủ sở hữu.</span></span><input type="checkbox" wire:model.live="config.footer.copyright.enabled" class="{{ $toggle }}"></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-800">Tên ứng dụng</span><span class="mt-0.5 block text-xs text-slate-500">Dùng tên ứng dụng hiện tại sau năm bản quyền.</span></span><input type="checkbox" wire:model.live="config.footer.show_app_name" class="{{ $toggle }}"></label>
                        <label class="block sm:col-span-2"><span class="text-sm font-medium text-slate-700">Tác giả / đơn vị sở hữu</span><input type="text" wire:model.live.debounce.300ms="config.footer.copyright.owner" maxlength="120" placeholder="Ví dụ: INAFO Pharma" class="{{ $control }}"><span class="mt-1.5 block text-xs text-slate-500">Để trống nếu chỉ muốn hiển thị © năm và tên ứng dụng.</span></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Website tác giả</span><input type="text" wire:model="config.footer.copyright.url" maxlength="255" placeholder="https://example.com" class="{{ $control }}"><span class="mt-1.5 block text-xs text-slate-500">Chấp nhận URL nội bộ hoặc HTTP/HTTPS.</span></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Năm bắt đầu</span><input type="number" min="1900" max="{{ $currentYear }}" wire:model.live="config.footer.copyright.start_year" placeholder="{{ $currentYear }}" class="{{ $control }}"><span class="mt-1.5 block text-xs text-slate-500">Ví dụ 2024 sẽ tự hiển thị © 2024–{{ $currentYear }}.</span></label>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Ngày & thời gian</h2><p class="mt-1 text-sm leading-6 text-slate-500">Thay cho thông tin môi trường. Đồng hồ runtime tự cập nhật, không reload trang.</p></div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-800">Hiển thị ngày</span><span class="mt-0.5 block text-xs text-slate-500">Định dạng cố định dd/mm/yyyy.</span></span><input type="checkbox" wire:model.live="config.footer.datetime.show_date" class="{{ $toggle }}"></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-800">Hiển thị thời gian</span><span class="mt-0.5 block text-xs text-slate-500">Đồng hồ 24 giờ HH:mm:ss.</span></span><input type="checkbox" wire:model.live="config.footer.datetime.show_time" class="{{ $toggle }}"></label>
                    </div>
                    <input type="hidden" wire:model="config.footer.datetime.date_format"><input type="hidden" wire:model="config.footer.datetime.time_format">
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Presentation</h2><p class="mt-1 text-sm leading-6 text-slate-500">Chỉ giữ các lựa chọn thực sự cần thiết để Footer luôn gọn và nhất quán.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block"><span class="text-sm font-medium text-slate-700">Bố cục</span><select wire:model.live="config.footer.presentation.alignment" class="{{ $control }}"><option value="split">Split — nội dung trái, thời gian phải</option><option value="center">Center — căn giữa</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Background</span><select wire:model.live="config.footer.presentation.background" class="{{ $control }}"><option value="system">Theo Design System</option><option value="transparent">Transparent</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Divider</span><select wire:model.live="config.footer.presentation.divider" class="{{ $control }}"><option value="subtle">Subtle</option><option value="none">Không dùng</option></select></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3 sm:self-end"><span><span class="block text-sm font-medium text-slate-800">Compact spacing</span><span class="mt-0.5 block text-xs text-slate-500">Giữ Footer thấp và ít chiếm workspace.</span></span><input type="checkbox" wire:model.live="config.footer.presentation.compact" class="{{ $toggle }}"></label>
                    </div>
                </section>
            </div>

            <aside class="xl:sticky xl:top-24" aria-label="Xem trước Footer">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Footer preview</h2><p class="mt-0.5 text-xs text-slate-500">Cập nhật theo cấu hình hiện tại.</p></div><span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">Live</span></div>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="h-28 bg-slate-50 p-3"><div class="h-2 w-2/3 rounded bg-slate-200"></div><div class="mt-2 h-2 w-1/2 rounded bg-slate-100"></div></div>
                        @if (data_get($config, 'layout.show_footer', false))
                            <div @class(['border-t px-3 text-[10px] text-slate-500', 'py-2' => data_get($config, 'footer.presentation.compact', true), 'py-3.5' => ! data_get($config, 'footer.presentation.compact', true), 'border-slate-200' => data_get($config, 'footer.presentation.divider', 'subtle') === 'subtle', 'border-transparent' => data_get($config, 'footer.presentation.divider') === 'none', 'bg-white' => data_get($config, 'footer.presentation.background', 'system') === 'system', 'bg-transparent' => data_get($config, 'footer.presentation.background') === 'transparent'])>
                                <div @class(['flex gap-2', 'items-center justify-between' => data_get($config, 'footer.presentation.alignment', 'split') === 'split', 'flex-col items-center justify-center text-center' => data_get($config, 'footer.presentation.alignment') === 'center'])>
                                    @if (data_get($config, 'footer.copyright.enabled', true))<div class="min-w-0"><span>© {{ $previewYear }}</span>@if(data_get($config,'footer.show_app_name',true)) <strong class="text-slate-700">{{ config('app.name') }}</strong>@endif @if(trim((string)data_get($config,'footer.copyright.owner')) !== '')<span class="text-slate-400">· Bản quyền:</span> <span class="font-medium text-slate-700">{{ data_get($config,'footer.copyright.owner') }}</span>@endif</div>@endif
                                    @if(data_get($config,'footer.datetime.show_date',true) || data_get($config,'footer.datetime.show_time',true))<div class="shrink-0 tabular-nums">@if(data_get($config,'footer.datetime.show_date',true)){{ now()->format('d/m/Y') }}@endif @if(data_get($config,'footer.datetime.show_date',true) && data_get($config,'footer.datetime.show_time',true)) · @endif @if(data_get($config,'footer.datetime.show_time',true)){{ now()->format('H:i:s') }}@endif</div>@endif
                                </div>
                            </div>
                        @else
                            <div class="border-t border-dashed border-slate-200 px-3 py-3 text-center text-[10px] font-medium text-slate-400">Footer đang tắt</div>
                        @endif
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><dt class="text-slate-400">Layout</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ ucfirst(data_get($config,'footer.presentation.alignment','split')) }}</dd></div><div><dt class="text-slate-400">Spacing</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ data_get($config,'footer.presentation.compact',true) ? 'Compact' : 'Comfortable' }}</dd></div></dl>
                </div>
            </aside>
        </div>

        @if ($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Có cấu hình Footer chưa hợp lệ. Vui lòng kiểm tra lại các trường được nhập.</div>@endif
        <div class="sticky bottom-4 z-20 flex justify-end"><button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/15 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Lưu Footer</button></div>
    </form>
</div>

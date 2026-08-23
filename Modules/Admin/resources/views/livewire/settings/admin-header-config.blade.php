@php
    $spacingOptions = ['0' => '0 px', '1' => '4 px', '2' => '8 px', '3' => '12 px', '4' => '16 px', '5' => '20 px', '6' => '24 px', '8' => '32 px', '10' => '40 px', '12' => '48 px'];
    $heightLabels = ['3.5rem' => '56 px', '4rem' => '64 px', '4.5rem' => '72 px'];
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-5 flex justify-end">
        <button type="button" wire:click="resetSection" wire:confirm="Khôi phục Header về mặc định?" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Khôi phục Header</button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
            <div class="space-y-6">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Brand</h2><p class="mt-1 text-sm text-slate-500">Quản lý logo, title và nhận diện hiển thị ở Header.</p></div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3 md:col-span-2"><span><span class="block text-sm font-medium text-slate-700">Hiển thị Brand</span><span class="mt-0.5 block text-xs text-slate-500">Tắt nếu Header chỉ cần action và UserMenu.</span></span><input type="checkbox" wire:model.live="config.header.brand.enabled" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Logo path / URL</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.logo" placeholder="/images/admin-logo.svg" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><span class="mt-1 block text-xs text-slate-500">Để trống sẽ dùng monogram từ tên ứng dụng.</span></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Logo size</span><select wire:model.live="config.header.brand.logo_size" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">@foreach (['24','28','32','36','40'] as $size)<option value="{{ $size }}">{{ $size }} px</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Title</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.title" placeholder="Tên ứng dụng" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Hiển thị Title</span><input type="checkbox" wire:model.live="config.header.brand.show_title" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Subtitle</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.subtitle" placeholder="Administration" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Hiển thị Subtitle</span><input type="checkbox" wire:model.live="config.header.brand.show_subtitle" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                        <label class="block md:col-span-2"><span class="text-sm font-medium text-slate-700">Brand URL</span><input type="text" wire:model="config.header.brand.url" placeholder="/admin" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Core components</h2><p class="mt-1 text-sm text-slate-500">Các vùng chức năng chính của Header. Thông báo được quản lý cùng Header Actions bên dưới.</p></div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach (['config.header.search' => 'Tìm kiếm trên Header','config.header.user_menu' => 'UserMenu'] as $model => $label)
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model.live="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-4"><div><h2 class="text-base font-semibold text-slate-900">Header Actions</h2><p class="mt-1 text-sm text-slate-500">Quản lý toàn bộ icon/action ở phía phải Header. Thông báo là system action, các action khác có thể thêm/xóa.</p></div><button type="button" wire:click="addHeaderAction" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">+ Thêm action</button></div>

                    <div class="mb-3 flex items-center justify-between gap-4 rounded-lg border border-indigo-200 bg-indigo-50/60 px-4 py-3" data-admin-system-action-settings="notifications">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm ring-1 ring-indigo-100"><i class="fa-regular fa-bell" aria-hidden="true"></i></span>
                            <span class="min-w-0"><span class="block text-sm font-semibold text-slate-800">Thông báo</span><span class="block text-xs text-slate-500">System action · giữ logic thông báo hiện tại.</span></span>
                        </div>
                        <label class="inline-flex shrink-0 items-center gap-2 text-xs font-semibold text-slate-600">Hiển thị <input type="checkbox" wire:model.live="config.header.notifications" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                    </div>

                    <div class="space-y-3">
                        @forelse ((array) data_get($config, 'header.actions.items', []) as $index => $action)
                            <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4" wire:key="header-action-{{ $index }}">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Title</span><input type="text" wire:model="config.header.actions.items.{{ $index }}.title" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"></label>
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Icon</span><select wire:model="config.header.actions.items.{{ $index }}.icon" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">@foreach (['globe','book','help','link','message','calendar','star'] as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></label>
                                    <label class="block lg:col-span-2"><span class="text-xs font-medium text-slate-600">URL</span><input type="text" wire:model="config.header.actions.items.{{ $index }}.url" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"></label>
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Priority</span><select wire:model="config.header.actions.items.{{ $index }}.priority" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"><option value="primary">Primary</option><option value="secondary">Secondary</option></select></label>
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Target</span><select wire:model="config.header.actions.items.{{ $index }}.target" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select></label>
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Badge</span><input type="text" maxlength="4" wire:model="config.header.actions.items.{{ $index }}.badge" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"></label>
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Permission</span><input type="text" wire:model="config.header.actions.items.{{ $index }}.permission" placeholder="optional" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"></label>
                                </div>
                                <div class="mt-3 flex items-center justify-between"><label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" wire:model="config.header.actions.items.{{ $index }}.enabled" class="rounded border-slate-300 text-indigo-600"> Enabled</label><button type="button" wire:click="removeHeaderAction({{ $index }})" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Xóa</button></div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">Chưa có custom action. Bạn có thể thêm Website, Tài liệu, Support hoặc link nhanh khác.</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">UserMenu</h2><p class="mt-1 text-sm text-slate-500">Tùy chỉnh thông tin tài khoản và toàn bộ menu phía trên nút Đăng xuất. Logout luôn do hệ thống quản lý.</p></div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        @foreach (['show_avatar' => 'Avatar','show_name' => 'Tên','show_email' => 'Email','show_role' => 'Vai trò'] as $key => $label)
                            <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model.live="config.header.user_menu_config.{{ $key }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                        @endforeach
                    </div>

                    @if ($importedHeaderMenuItems)
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">Các Menu items hiện tại từ Header Menu cũ đã được đưa vào UserMenu để bạn quản lý tại đây. Chúng chỉ được ghi vào cấu hình Header mới khi bạn bấm <strong>Lưu Header</strong>.</div>
                    @endif

                    <div class="mt-5 flex items-center justify-between"><div><h3 class="text-sm font-semibold text-slate-800">Menu items hiện tại</h3><p class="mt-0.5 text-xs text-slate-500">Sắp xếp bằng trường Order; item không có quyền phù hợp sẽ tự ẩn ở runtime.</p></div><button type="button" wire:click="addUserMenuItem" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">+ Thêm item</button></div>
                    <div class="mt-3 space-y-3">
                        @forelse ((array) data_get($config, 'header.user_menu_config.items', []) as $index => $item)
                            <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-4 md:grid-cols-2 lg:grid-cols-5" wire:key="user-menu-item-{{ $index }}">
                                <input type="text" wire:model="config.header.user_menu_config.items.{{ $index }}.label" placeholder="Label" class="rounded-lg border-slate-300 text-sm">
                                <select wire:model="config.header.user_menu_config.items.{{ $index }}.icon" class="rounded-lg border-slate-300 text-sm">@foreach (['user','gear','lock','key','shield','link'] as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select>
                                <input type="text" wire:model="config.header.user_menu_config.items.{{ $index }}.url" placeholder="/admin/profile" class="rounded-lg border-slate-300 text-sm lg:col-span-2">
                                <input type="number" min="0" max="99" wire:model="config.header.user_menu_config.items.{{ $index }}.order" placeholder="Order" class="rounded-lg border-slate-300 text-sm">
                                <input type="text" wire:model="config.header.user_menu_config.items.{{ $index }}.permission" placeholder="Permission (optional)" class="rounded-lg border-slate-300 text-sm lg:col-span-2">
                                <select wire:model="config.header.user_menu_config.items.{{ $index }}.target" class="rounded-lg border-slate-300 text-sm"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select>
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" wire:model="config.header.user_menu_config.items.{{ $index }}.enabled" class="rounded border-slate-300 text-indigo-600"> Enabled</label>
                                <button type="button" wire:click="removeUserMenuItem({{ $index }})" class="justify-self-start text-xs font-semibold text-rose-600 lg:justify-self-end">Xóa</button>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">Chưa có UserMenu item. Bấm “Thêm item” để tạo link mới.</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Presentation & Responsive</h2><p class="mt-1 text-sm text-slate-500">Điều chỉnh kích thước, khoảng cách, surface và hành vi trên mobile bằng bounded tokens.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="block"><span class="text-sm font-medium text-slate-700">Header height</span><select wire:model.live="config.header.height" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">@foreach ($heightLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Mode</span><select wire:model.live="config.header.presentation.mode" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="balanced">Balanced</option><option value="compact">Compact</option><option value="action-heavy">Action heavy</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Padding ngang</span><select wire:model.live="config.header.presentation.padding_x" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">@foreach ($spacingOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Action gap</span><select wire:model.live="config.header.presentation.action_gap" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">@foreach ($spacingOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Background</span><select wire:model.live="config.header.presentation.background" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="system">Design System</option><option value="white">White</option><option value="transparent">Transparent</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Shadow</span><select wire:model.live="config.header.presentation.shadow" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Divider</span><select wire:model.live="config.header.presentation.divider" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Mobile Brand</span><select wire:model.live="config.header.responsive.mobile_brand" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="logo-only">Logo only</option><option value="logo-title">Logo + title</option><option value="hidden">Hidden</option></select></label>
                        <div class="space-y-2"><label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700">Backdrop blur <input type="checkbox" wire:model.live="config.header.presentation.backdrop_blur" class="rounded border-slate-300 text-indigo-600"></label><label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700">Mobile overflow <input type="checkbox" wire:model.live="config.header.responsive.overflow_secondary_actions" class="rounded border-slate-300 text-indigo-600"></label></div>
                    </div>
                </section>
            </div>

            <aside class="xl:sticky xl:top-24" aria-label="Xem trước Header">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between"><h2 class="text-sm font-semibold text-slate-900">Header preview</h2><span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">Live</span></div>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 p-3">
                        <div class="flex items-center justify-between bg-white px-3 shadow-sm transition-all" style="height: {{ str_replace('rem','',data_get($config,'header.height','4rem')) * 16 }}px; gap: {{ ((int) data_get($config,'header.presentation.action_gap','2')) * 4 }}px;">
                            <div class="flex min-w-0 items-center gap-2">
                                @if (data_get($config,'header.brand.enabled',true))<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-xs font-bold text-indigo-700">{{ mb_substr((string) (data_get($config,'header.brand.title') ?: config('app.name','A')),0,1) }}</div>@endif
                                @if (data_get($config,'header.brand.show_title',true))<div class="min-w-0"><div class="max-w-28 truncate text-xs font-semibold text-slate-800">{{ data_get($config,'header.brand.title') ?: config('app.name','Admin') }}</div>@if (data_get($config,'header.brand.show_subtitle',false))<div class="max-w-28 truncate text-[9px] text-slate-400">{{ data_get($config,'header.brand.subtitle') }}</div>@endif</div>@endif
                            </div>
                            <div class="flex items-center gap-1">@if (data_get($config,'header.notifications',true))<span class="flex h-7 w-7 items-center justify-center rounded-md bg-indigo-50 text-[10px] text-indigo-600"><i class="fa-regular fa-bell"></i></span>@endif @foreach (array_slice((array) data_get($config,'header.actions.items',[]),0,2) as $action)<span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-[10px]">↗</span>@endforeach @if (data_get($config,'header.user_menu',true))<span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold">A</span>@endif</div>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><dt class="text-slate-400">Height</dt><dd class="font-semibold text-slate-700">{{ $heightLabels[data_get($config,'header.height','4rem')] ?? '64 px' }}</dd></div><div><dt class="text-slate-400">Mode</dt><dd class="font-semibold text-slate-700">{{ data_get($config,'header.presentation.mode','balanced') }}</dd></div><div><dt class="text-slate-400">Actions</dt><dd class="font-semibold text-slate-700">{{ count((array) data_get($config,'header.actions.items',[])) + (data_get($config,'header.notifications',true) ? 1 : 0) }}</dd></div><div><dt class="text-slate-400">UserMenu</dt><dd class="font-semibold text-slate-700">{{ count((array) data_get($config,'header.user_menu_config.items',[])) }} items</dd></div></dl>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Preview cập nhật trước khi lưu. Save sẽ reload để Header runtime nhận toàn bộ cấu hình mới.</p>
                </div>
            </aside>
        </div>

        @if ($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Vui lòng kiểm tra lại các trường Header.</div>@endif
        <div class="flex justify-end border-t border-slate-200 pt-5"><button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60"><span wire:loading.remove wire:target="save">Lưu Header</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
    </form>
</div>

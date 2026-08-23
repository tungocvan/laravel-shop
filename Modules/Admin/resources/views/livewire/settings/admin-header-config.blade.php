@php
    $spacingOptions = ['0' => '0 px', '1' => '4 px', '2' => '8 px', '3' => '12 px', '4' => '16 px', '5' => '20 px', '6' => '24 px', '8' => '32 px', '10' => '40 px', '12' => '48 px'];
    $heightLabels = ['3.5rem' => '56 px', '4rem' => '64 px', '4.5rem' => '72 px'];
    $headerActionIcons = ['bell','globe','book','help','link','message','calendar','star'];
    $userMenuIcons = ['user','gear','lock','key','shield','link'];
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-5 flex justify-end">
        <button type="button" wire:click="resetSection" wire:confirm="Khôi phục Header về mặc định?" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="fa-solid fa-rotate-left text-xs" aria-hidden="true"></i> Khôi phục Header
        </button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Brand</h2><p class="mt-1 text-sm text-slate-500">Quản lý logo, title và nhận diện hiển thị ở Header.</p></div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3 md:col-span-2"><span class="text-sm font-medium text-slate-700">Hiển thị Brand</span><input type="checkbox" wire:model.live="config.header.brand.enabled" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Logo path / URL</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.logo" placeholder="/images/admin-logo.svg" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Logo size</span><select wire:model.live="config.header.brand.logo_size" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm">@foreach(['24','28','32','36','40'] as $size)<option value="{{ $size }}">{{ $size }} px</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Title</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.title" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm"><span class="mt-1 block text-xs text-slate-500">Runtime dùng width auto theo nội dung, không ép max-width cố định.</span></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Hiển thị Title</span><input type="checkbox" wire:model.live="config.header.brand.show_title" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Subtitle</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.subtitle" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Hiển thị Subtitle</span><input type="checkbox" wire:model.live="config.header.brand.show_subtitle" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                        <label class="block md:col-span-2"><span class="text-sm font-medium text-slate-700">Brand URL</span><input type="text" wire:model="config.header.brand.url" placeholder="/admin" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Core components</h2><p class="mt-1 text-sm text-slate-500">Thông báo được quản lý cùng Header Actions bên dưới.</p></div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach (['config.header.search' => 'Tìm kiếm trên Header','config.header.user_menu' => 'UserMenu'] as $model => $label)
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model.live="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white shadow-sm" data-admin-header-actions-editor>
                    <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
                        <div><h2 class="text-base font-semibold text-slate-900">Header Actions</h2><p class="mt-1 text-sm text-slate-500">Quản lý các icon/action phía phải Header theo dạng danh sách rõ ràng, dễ mở rộng.</p></div>
                        <button type="button" wire:click="addHeaderAction" class="inline-flex min-h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"><i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i> Thêm action</button>
                    </div>

                    <div class="p-5">
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/50" data-admin-system-action-settings="notifications">
                            <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-[minmax(13rem,1.4fr)_minmax(8rem,.75fr)_minmax(9rem,.8fr)_auto] lg:items-end">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm ring-1 ring-indigo-100"><i class="fa-regular fa-bell" aria-hidden="true"></i></span>
                                    <div class="min-w-0"><div class="flex items-center gap-2"><span class="truncate text-sm font-semibold text-slate-900">Thông báo</span><span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">System</span></div><p class="mt-0.5 text-xs text-slate-500">System action · giữ logic notification khi dùng Dropdown.</p></div>
                                </div>
                                <label class="block"><span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Icon</span><select wire:model.live="config.header.actions.notification.icon" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm">@foreach($headerActionIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></label>
                                <label class="block"><span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Behavior</span><select wire:model.live="config.header.actions.notification.behavior" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"><option value="dropdown">Dropdown / panel</option><option value="link">Link</option></select></label>
                                <label class="inline-flex min-h-10 items-center justify-between gap-3 rounded-lg border border-indigo-200 bg-white px-3 text-xs font-semibold text-slate-600">Hiển thị <input type="checkbox" wire:model.live="config.header.notifications" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                            </div>
                            @if(data_get($config,'header.actions.notification.behavior','dropdown') === 'link')
                                <div class="grid grid-cols-1 gap-3 border-t border-indigo-100 px-4 py-4 md:grid-cols-[minmax(0,1fr)_10rem]">
                                    <label class="block"><span class="text-xs font-medium text-slate-600">URL</span><input type="text" wire:model="config.header.actions.notification.url" placeholder="/admin/notifications" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"></label>
                                    <label class="block"><span class="text-xs font-medium text-slate-600">Target</span><select wire:model="config.header.actions.notification.target" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select></label>
                                </div>
                            @endif
                        </div>

                        <div class="mt-5">
                            <div class="mb-2 hidden grid-cols-[minmax(11rem,1.25fr)_8rem_minmax(12rem,1.5fr)_7rem_6rem_6rem_auto] gap-3 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 lg:grid">
                                <span>Action</span><span>Icon</span><span>URL</span><span>Priority</span><span>Badge</span><span>Order</span><span class="text-right">Thao tác</span>
                            </div>
                            <div class="space-y-2">
                                @forelse((array)data_get($config,'header.actions.items',[]) as $index=>$action)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3 transition hover:border-slate-300 hover:shadow-sm" wire:key="header-action-{{ $index }}">
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(11rem,1.25fr)_8rem_minmax(12rem,1.5fr)_7rem_6rem_6rem_auto] lg:items-center">
                                            <label class="block"><span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Action</span><input wire:model="config.header.actions.items.{{ $index }}.title" placeholder="Title" class="block w-full rounded-lg border-slate-300 text-sm font-medium"></label>
                                            <label><span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Icon</span><select wire:model="config.header.actions.items.{{ $index }}.icon" class="block w-full rounded-lg border-slate-300 text-sm">@foreach($headerActionIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></label>
                                            <label><span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">URL</span><input wire:model="config.header.actions.items.{{ $index }}.url" placeholder="/admin/..." class="block w-full rounded-lg border-slate-300 text-sm"></label>
                                            <label><span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Priority</span><select wire:model="config.header.actions.items.{{ $index }}.priority" class="block w-full rounded-lg border-slate-300 text-sm"><option value="primary">Primary</option><option value="secondary">Secondary</option></select></label>
                                            <label><span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Badge</span><input maxlength="4" wire:model="config.header.actions.items.{{ $index }}.badge" class="block w-full rounded-lg border-slate-300 text-sm"></label>
                                            <label><span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 lg:hidden">Order</span><input type="number" min="0" max="99" wire:model="config.header.actions.items.{{ $index }}.order" class="block w-full rounded-lg border-slate-300 text-sm"></label>
                                            <div class="flex items-center justify-between gap-2 lg:justify-end"><label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" wire:model="config.header.actions.items.{{ $index }}.enabled" class="rounded border-slate-300 text-indigo-600"> Hiện</label><button type="button" wire:click="removeHeaderAction({{ $index }})" title="Xóa action" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 transition hover:bg-rose-50 hover:text-rose-700"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button></div>
                                        </div>
                                        <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 md:grid-cols-[10rem_minmax(0,1fr)]"><label><span class="text-xs font-medium text-slate-500">Target</span><select wire:model="config.header.actions.items.{{ $index }}.target" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select></label><label><span class="text-xs font-medium text-slate-500">Permission</span><input wire:model="config.header.actions.items.{{ $index }}.permission" placeholder="optional" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label></div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 px-4 py-8 text-center"><p class="text-sm font-medium text-slate-700">Chưa có custom action</p><p class="mt-1 text-xs text-slate-500">Thêm Website, tài liệu hoặc link nhanh khác.</p></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white shadow-sm" data-admin-user-menu-editor>
                    <div class="border-b border-slate-200 px-5 py-5"><h2 class="text-base font-semibold text-slate-900">UserMenu</h2><p class="mt-1 text-sm text-slate-500">Tùy chỉnh thông tin tài khoản và toàn bộ menu phía trên nút Đăng xuất. Logout luôn do hệ thống quản lý.</p></div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">@foreach(['show_avatar'=>'Avatar','show_name'=>'Tên','show_email'=>'Email','show_role'=>'Vai trò'] as $key=>$label)<label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model.live="config.header.user_menu_config.{{ $key }}" class="rounded border-slate-300 text-indigo-600"></label>@endforeach</div>
                        @if($importedHeaderMenuItems)<div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800">Các Menu items hiện tại từ Header Menu cũ đã được đưa vào UserMenu. Chúng chỉ được ghi vào cấu hình Header mới khi bạn bấm <strong>Lưu Header</strong>.</div>@endif
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-semibold text-slate-900">Menu items hiện tại</h3><p class="mt-0.5 text-xs text-slate-500">Dạng row giúp theo dõi URL, permission, trạng thái và thứ tự nhanh hơn.</p></div><button type="button" wire:click="addUserMenuItem" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"><i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i> Thêm menu item</button></div>
                        <div class="mt-3 hidden grid-cols-[minmax(10rem,1.1fr)_8rem_minmax(12rem,1.5fr)_10rem_6rem_auto] gap-3 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 lg:grid"><span>Menu item</span><span>Icon</span><span>URL</span><span>Target</span><span>Order</span><span class="text-right">Thao tác</span></div>
                        <div class="mt-2 space-y-2">
                            @forelse((array)data_get($config,'header.user_menu_config.items',[]) as $index=>$item)
                                <div class="rounded-xl border border-slate-200 p-3 transition hover:border-slate-300 hover:shadow-sm" wire:key="user-menu-item-{{ $index }}">
                                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(10rem,1.1fr)_8rem_minmax(12rem,1.5fr)_10rem_6rem_auto] lg:items-center">
                                        <input wire:model="config.header.user_menu_config.items.{{ $index }}.label" placeholder="Label" class="block w-full rounded-lg border-slate-300 text-sm font-medium">
                                        <select wire:model="config.header.user_menu_config.items.{{ $index }}.icon" class="block w-full rounded-lg border-slate-300 text-sm">@foreach($userMenuIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select>
                                        <input wire:model="config.header.user_menu_config.items.{{ $index }}.url" placeholder="/admin/profile" class="block w-full rounded-lg border-slate-300 text-sm">
                                        <select wire:model="config.header.user_menu_config.items.{{ $index }}.target" class="block w-full rounded-lg border-slate-300 text-sm"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select>
                                        <input type="number" min="0" max="99" wire:model="config.header.user_menu_config.items.{{ $index }}.order" class="block w-full rounded-lg border-slate-300 text-sm">
                                        <div class="flex items-center justify-between gap-2 lg:justify-end"><label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" wire:model="config.header.user_menu_config.items.{{ $index }}.enabled" class="rounded border-slate-300 text-indigo-600"> Hiện</label><button type="button" wire:click="removeUserMenuItem({{ $index }})" title="Xóa menu item" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button></div>
                                    </div>
                                    <label class="mt-3 block border-t border-slate-100 pt-3"><span class="text-xs font-medium text-slate-500">Permission</span><input wire:model="config.header.user_menu_config.items.{{ $index }}.permission" placeholder="optional" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 px-4 py-8 text-center text-sm text-slate-500">Chưa có UserMenu item. Bấm “Thêm menu item” để tạo.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Presentation & Responsive</h2><p class="mt-1 text-sm text-slate-500">Điều chỉnh kích thước, khoảng cách, surface và hành vi responsive.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label>Header height<select wire:model.live="config.header.height" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">@foreach($heightLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label>Mode<select wire:model.live="config.header.presentation.mode" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="balanced">Balanced</option><option value="compact">Compact</option><option value="action-heavy">Action heavy</option></select></label>
                        <label>Padding ngang<select wire:model.live="config.header.presentation.padding_x" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">@foreach($spacingOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label>Action gap<select wire:model.live="config.header.presentation.action_gap" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">@foreach($spacingOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label>Background<select wire:model.live="config.header.presentation.background" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="system">Design System</option><option value="white">White</option><option value="transparent">Transparent</option></select></label>
                        <label>Shadow<select wire:model.live="config.header.presentation.shadow" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label>Divider<select wire:model.live="config.header.presentation.divider" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label>Mobile Brand<select wire:model.live="config.header.responsive.mobile_brand" class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="logo-only">Logo only</option><option value="logo-title">Logo + title</option><option value="hidden">Hidden</option></select></label>
                        <div class="space-y-2"><label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm">Backdrop blur<input type="checkbox" wire:model.live="config.header.presentation.backdrop_blur"></label><label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm">Mobile overflow<input type="checkbox" wire:model.live="config.header.responsive.overflow_secondary_actions"></label></div>
                    </div>
                </section>
            </div>

            <aside class="xl:sticky xl:top-24" aria-label="Xem trước Header">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="text-sm font-semibold text-slate-900">Header preview</h2><span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">Live</span></div>
                    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex min-w-0 items-center justify-between gap-2" style="height: {{ data_get($config,'header.height','4rem') }}">
                            <div class="flex min-w-0 w-auto shrink-0 items-center gap-2"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-[10px] font-bold text-indigo-700">{{ mb_substr((string)(data_get($config,'header.brand.title') ?: config('app.name','A')),0,1) }}</span><span class="w-auto whitespace-nowrap text-[11px] font-semibold text-slate-800">{{ data_get($config,'header.brand.title') ?: config('app.name','Admin') }}</span></div>
                            <div class="flex shrink-0 items-center gap-1"><span class="h-7 w-7 rounded-md bg-slate-100"></span><span class="h-7 w-7 rounded-full bg-indigo-100"></span></div>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><dt class="text-slate-400">Height</dt><dd class="font-semibold text-slate-700">{{ $heightLabels[data_get($config,'header.height','4rem')] ?? '64 px' }}</dd></div><div><dt class="text-slate-400">Mode</dt><dd class="font-semibold text-slate-700">{{ data_get($config,'header.presentation.mode','balanced') }}</dd></div><div><dt class="text-slate-400">Actions</dt><dd class="font-semibold text-slate-700">{{ count((array)data_get($config,'header.actions.items',[])) + (data_get($config,'header.notifications',true) ? 1 : 0) }}</dd></div><div><dt class="text-slate-400">UserMenu</dt><dd class="font-semibold text-slate-700">{{ count((array)data_get($config,'header.user_menu_config.items',[])) }} items</dd></div></dl>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Preview cập nhật trước khi lưu. Save sẽ reload để Header runtime nhận toàn bộ cấu hình mới.</p>
                </div>
            </aside>
        </div>

        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Vui lòng kiểm tra lại các trường Header.</div>@endif
        <div class="flex justify-end border-t border-slate-200 pt-5"><button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60"><span wire:loading.remove wire:target="save">Lưu Header</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
    </form>
</div>

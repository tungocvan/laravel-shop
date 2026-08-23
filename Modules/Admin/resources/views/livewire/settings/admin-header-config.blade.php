@php
    $spacingOptions = ['0' => '0 px', '1' => '4 px', '2' => '8 px', '3' => '12 px', '4' => '16 px', '5' => '20 px', '6' => '24 px', '8' => '32 px', '10' => '40 px', '12' => '48 px'];
    $heightLabels = ['3.5rem' => '56 px', '4rem' => '64 px', '4.5rem' => '72 px'];
    $headerActionIcons = ['bell','globe','book','help','link','message','calendar','star'];
    $userMenuIcons = ['user','gear','lock','key','shield','link'];
    $control = 'mt-1.5 block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10';
    $select = $control.' pr-9';
    $fieldLabel = 'text-xs font-semibold text-slate-600';
    $section = 'rounded-xl border border-slate-200 bg-white shadow-sm';
    $sectionHead = 'border-b border-slate-200 px-5 py-4';
@endphp

<div class="mx-auto max-w-7xl" data-admin-header-settings>
    <div class="mb-5 flex items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Thiết lập giao diện</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">Cấu hình Header</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý Brand, các action, UserMenu và presentation theo cùng một design system.</p>
        </div>
        <button type="button" wire:click="resetSection" wire:confirm="Khôi phục Header về mặc định?" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-indigo-500/10">
            Khôi phục Header
        </button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_21rem] xl:items-start">
            <div class="space-y-6">
                <section class="{{ $section }}">
                    <div class="{{ $sectionHead }}"><h2 class="text-base font-semibold text-slate-900">Brand</h2><p class="mt-1 text-sm text-slate-500">Logo, title, subtitle và liên kết thương hiệu trên Header.</p></div>
                    <div class="p-5">
                        <label class="mb-5 flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50/60 px-4 py-3"><span><span class="block text-sm font-semibold text-slate-800">Hiển thị Brand</span><span class="mt-0.5 block text-xs text-slate-500">Ẩn/hiện toàn bộ vùng nhận diện ở Header.</span></span><input type="checkbox" wire:model.live="config.header.brand.enabled" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <label class="block"><span class="{{ $fieldLabel }}">Logo path / URL</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.logo" placeholder="/images/admin-logo.svg" class="{{ $control }}"></label>
                            <label class="block"><span class="{{ $fieldLabel }}">Logo size</span><select wire:model.live="config.header.brand.logo_size" class="{{ $select }}">@foreach(['24','28','32','36','40'] as $size)<option value="{{ $size }}">{{ $size }} px</option>@endforeach</select></label>
                            <label class="block"><span class="{{ $fieldLabel }}">Title</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.title" class="{{ $control }}"><span class="mt-1.5 block text-xs text-slate-500">Runtime dùng width auto theo nội dung.</span></label>
                            <label class="flex min-h-16 items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Hiển thị Title</span><input type="checkbox" wire:model.live="config.header.brand.show_title" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                            <label class="block"><span class="{{ $fieldLabel }}">Subtitle</span><input type="text" wire:model.live.debounce.300ms="config.header.brand.subtitle" class="{{ $control }}"></label>
                            <label class="flex min-h-16 items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Hiển thị Subtitle</span><input type="checkbox" wire:model.live="config.header.brand.show_subtitle" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                            <label class="block md:col-span-2"><span class="{{ $fieldLabel }}">Brand URL</span><input type="text" wire:model="config.header.brand.url" placeholder="/admin" class="{{ $control }}"></label>
                        </div>
                    </div>
                </section>

                <section class="{{ $section }}">
                    <div class="{{ $sectionHead }}"><h2 class="text-base font-semibold text-slate-900">Core components</h2><p class="mt-1 text-sm text-slate-500">Thông báo được quản lý cùng Header Actions bên dưới.</p></div>
                    <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-2">
                        @foreach (['config.header.search' => ['Tìm kiếm trên Header','Hiện ô tìm kiếm nhanh toàn cục.'],'config.header.user_menu' => ['UserMenu','Hiển thị khu vực tài khoản người dùng.']] as $model => $meta)
                            <label class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300"><span><span class="block text-sm font-semibold text-slate-800">{{ $meta[0] }}</span><span class="mt-0.5 block text-xs text-slate-500">{{ $meta[1] }}</span></span><input type="checkbox" wire:model.live="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                        @endforeach
                    </div>
                </section>

                <section class="{{ $section }}" data-admin-header-actions-editor>
                    <div class="{{ $sectionHead }} flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div><h2 class="text-base font-semibold text-slate-900">Header Actions</h2><p class="mt-1 text-sm text-slate-500">Quản lý icon/action phía phải Header theo danh sách rõ ràng, dễ mở rộng.</p></div>
                        <button type="button" wire:click="addHeaderAction" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-500/10">+ Thêm action</button>
                    </div>
                    <div class="space-y-5 p-5">
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/50" data-admin-system-action-settings="notifications">
                            <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-[minmax(14rem,1.35fr)_9rem_11rem_auto] lg:items-end">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-100 text-indigo-700 shadow-sm">@include('Admin::livewire.partials.header.components.action-icon', ['icon' => data_get($config,'header.actions.notification.icon','bell'), 'class' => 'h-5 w-5'])</span>
                                    <div class="min-w-0"><div class="flex items-center gap-2"><span class="text-sm font-semibold text-slate-900">Thông báo</span><span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">System</span></div><p class="mt-1 text-xs leading-5 text-slate-500">Giữ logic notification khi dùng Dropdown / panel.</p></div>
                                </div>
                                <label><span class="{{ $fieldLabel }}">Icon</span><select wire:model.live="config.header.actions.notification.icon" class="{{ $select }}">@foreach($headerActionIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></label>
                                <label><span class="{{ $fieldLabel }}">Behavior</span><select wire:model.live="config.header.actions.notification.behavior" class="{{ $select }}"><option value="dropdown">Dropdown / panel</option><option value="link">Link</option></select></label>
                                <label class="flex h-10 items-center justify-between gap-3 rounded-lg border border-indigo-200 bg-white px-3 text-xs font-semibold text-slate-700">Hiển thị <input type="checkbox" wire:model.live="config.header.notifications" class="h-5 w-5 rounded border-slate-300 text-indigo-600"></label>
                            </div>
                            @if(data_get($config,'header.actions.notification.behavior','dropdown') === 'link')
                                <div class="grid grid-cols-1 gap-4 border-t border-indigo-100 px-4 py-4 md:grid-cols-[minmax(0,1fr)_11rem]"><label><span class="{{ $fieldLabel }}">URL</span><input type="text" wire:model="config.header.actions.notification.url" placeholder="/admin/notifications" class="{{ $control }}"></label><label><span class="{{ $fieldLabel }}">Target</span><select wire:model="config.header.actions.notification.target" class="{{ $select }}"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select></label></div>
                            @endif
                        </div>

                        <div>
                            <div class="mb-2 hidden grid-cols-[minmax(11rem,1.2fr)_8rem_minmax(12rem,1.5fr)_8rem_6rem_5rem_auto] gap-3 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 lg:grid"><span>Action</span><span>Icon</span><span>URL</span><span>Priority</span><span>Badge</span><span>Order</span><span class="text-right">Trạng thái</span></div>
                            <div class="space-y-3">
                                @forelse((array)data_get($config,'header.actions.items',[]) as $index=>$action)
                                    <article class="rounded-xl border border-slate-200 bg-slate-50/40 p-4 transition hover:border-slate-300" wire:key="header-action-{{ $index }}">
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(11rem,1.2fr)_8rem_minmax(12rem,1.5fr)_8rem_6rem_5rem_auto] lg:items-end">
                                            <label><span class="{{ $fieldLabel }} lg:hidden">Action</span><input wire:model="config.header.actions.items.{{ $index }}.title" placeholder="Title" class="{{ $control }} font-medium"></label>
                                            <label><span class="{{ $fieldLabel }} lg:hidden">Icon</span><select wire:model.live="config.header.actions.items.{{ $index }}.icon" class="{{ $select }}">@foreach($headerActionIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></label>
                                            <label><span class="{{ $fieldLabel }} lg:hidden">URL</span><input wire:model="config.header.actions.items.{{ $index }}.url" placeholder="/admin/..." class="{{ $control }}"></label>
                                            <label><span class="{{ $fieldLabel }} lg:hidden">Priority</span><select wire:model="config.header.actions.items.{{ $index }}.priority" class="{{ $select }}"><option value="primary">Primary</option><option value="secondary">Secondary</option></select></label>
                                            <label><span class="{{ $fieldLabel }} lg:hidden">Badge</span><input maxlength="4" wire:model="config.header.actions.items.{{ $index }}.badge" class="{{ $control }}"></label>
                                            <label><span class="{{ $fieldLabel }} lg:hidden">Order</span><input type="number" min="0" max="99" wire:model="config.header.actions.items.{{ $index }}.order" class="{{ $control }}"></label>
                                            <div class="flex h-10 items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 lg:justify-end"><label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600"><input type="checkbox" wire:model="config.header.actions.items.{{ $index }}.enabled" class="rounded border-slate-300 text-indigo-600"> Hiện</label><button type="button" wire:click="removeHeaderAction({{ $index }})" title="Xóa action" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50" aria-label="Xóa action">×</button></div>
                                        </div>
                                        <div class="mt-4 grid grid-cols-1 gap-4 border-t border-slate-200 pt-4 md:grid-cols-[12rem_minmax(0,1fr)]"><label><span class="{{ $fieldLabel }}">Target</span><select wire:model="config.header.actions.items.{{ $index }}.target" class="{{ $select }}"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select></label><label><span class="{{ $fieldLabel }}">Permission</span><input wire:model="config.header.actions.items.{{ $index }}.permission" placeholder="optional" class="{{ $control }}"></label></div>
                                    </article>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center"><p class="text-sm font-semibold text-slate-700">Chưa có custom action</p><p class="mt-1 text-xs text-slate-500">Thêm Website, tài liệu hoặc link nhanh khác.</p></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="{{ $section }}" data-admin-user-menu-editor>
                    <div class="{{ $sectionHead }}"><h2 class="text-base font-semibold text-slate-900">UserMenu</h2><p class="mt-1 text-sm text-slate-500">Tùy chỉnh thông tin tài khoản và toàn bộ menu phía trên nút Đăng xuất. Logout luôn do hệ thống quản lý.</p></div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">@foreach(['show_avatar'=>'Avatar','show_name'=>'Tên','show_email'=>'Email','show_role'=>'Vai trò'] as $key=>$label)<label class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model.live="config.header.user_menu_config.{{ $key }}" class="rounded border-slate-300 text-indigo-600"></label>@endforeach</div>
                        @if($importedHeaderMenuItems)<div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Các Menu items hiện tại đã được import. Bấm <strong>Lưu Header</strong> để ghi cấu hình.</div>@endif
                        <div class="mt-6 flex items-end justify-between gap-4"><div><h3 class="text-sm font-semibold text-slate-900">Menu items hiện tại</h3><p class="mt-1 text-xs text-slate-500">Sắp xếp bằng Order; item không có quyền phù hợp sẽ tự ẩn ở runtime.</p></div><button type="button" wire:click="addUserMenuItem" class="inline-flex h-9 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Thêm menu item</button></div>
                        <div class="mt-3 space-y-3">
                            @forelse((array)data_get($config,'header.user_menu_config.items',[]) as $index=>$item)
                                <article class="rounded-xl border border-slate-200 bg-slate-50/40 p-4" wire:key="user-menu-item-{{ $index }}">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-[minmax(10rem,1fr)_8rem_minmax(12rem,1.4fr)_10rem_5rem_auto] lg:items-end">
                                        <label><span class="{{ $fieldLabel }}">Label</span><input wire:model="config.header.user_menu_config.items.{{ $index }}.label" placeholder="Label" class="{{ $control }}"></label>
                                        <label><span class="{{ $fieldLabel }}">Icon</span><select wire:model="config.header.user_menu_config.items.{{ $index }}.icon" class="{{ $select }}">@foreach($userMenuIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></label>
                                        <label><span class="{{ $fieldLabel }}">URL</span><input wire:model="config.header.user_menu_config.items.{{ $index }}.url" placeholder="/admin/..." class="{{ $control }}"></label>
                                        <label><span class="{{ $fieldLabel }}">Target</span><select wire:model="config.header.user_menu_config.items.{{ $index }}.target" class="{{ $select }}"><option value="_self">Cùng tab</option><option value="_blank">Tab mới</option></select></label>
                                        <label><span class="{{ $fieldLabel }}">Order</span><input type="number" min="0" max="99" wire:model="config.header.user_menu_config.items.{{ $index }}.order" class="{{ $control }}"></label>
                                        <div class="flex h-10 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3"><label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600"><input type="checkbox" wire:model="config.header.user_menu_config.items.{{ $index }}.enabled" class="rounded border-slate-300 text-indigo-600"> Hiện</label><button type="button" wire:click="removeUserMenuItem({{ $index }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" aria-label="Xóa menu item">×</button></div>
                                    </div>
                                    <label class="mt-4 block border-t border-slate-200 pt-4"><span class="{{ $fieldLabel }}">Permission</span><input wire:model="config.header.user_menu_config.items.{{ $index }}.permission" placeholder="optional" class="{{ $control }}"></label>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Chưa có UserMenu item.</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="{{ $section }}">
                    <div class="{{ $sectionHead }}"><h2 class="text-base font-semibold text-slate-900">Presentation & Responsive</h2><p class="mt-1 text-sm text-slate-500">Kích thước, khoảng cách, surface và hành vi responsive bằng bounded tokens.</p></div>
                    <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <label><span class="{{ $fieldLabel }}">Header height</span><select wire:model.live="config.header.height" class="{{ $select }}">@foreach($heightLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label><span class="{{ $fieldLabel }}">Mode</span><select wire:model.live="config.header.presentation.mode" class="{{ $select }}"><option value="balanced">Balanced</option><option value="compact">Compact</option><option value="action-heavy">Action heavy</option></select></label>
                        <label><span class="{{ $fieldLabel }}">Padding ngang</span><select wire:model.live="config.header.presentation.padding_x" class="{{ $select }}">@foreach($spacingOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label><span class="{{ $fieldLabel }}">Action gap</span><select wire:model.live="config.header.presentation.action_gap" class="{{ $select }}">@foreach($spacingOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label><span class="{{ $fieldLabel }}">Background</span><select wire:model.live="config.header.presentation.background" class="{{ $select }}"><option value="system">Design System</option><option value="white">White</option><option value="transparent">Transparent</option></select></label>
                        <label><span class="{{ $fieldLabel }}">Divider</span><select wire:model="config.header.presentation.divider" class="{{ $select }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label><span class="{{ $fieldLabel }}">Shadow</span><select wire:model="config.header.presentation.shadow" class="{{ $select }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label><span class="{{ $fieldLabel }}">Mobile Brand</span><select wire:model.live="config.header.responsive.mobile_brand" class="{{ $select }}"><option value="logo-only">Logo only</option><option value="logo-title">Logo + title</option><option value="hidden">Hidden</option></select></label>
                        <div class="space-y-2 sm:col-span-2 lg:col-span-1"><label class="flex h-10 items-center justify-between rounded-lg border border-slate-200 px-3 text-sm text-slate-700">Backdrop blur<input type="checkbox" wire:model="config.header.presentation.backdrop_blur" class="rounded border-slate-300 text-indigo-600"></label><label class="flex h-10 items-center justify-between rounded-lg border border-slate-200 px-3 text-sm text-slate-700">Overflow action mobile<input type="checkbox" wire:model="config.header.responsive.overflow_secondary_actions" class="rounded border-slate-300 text-indigo-600"></label></div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-24">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="text-base font-semibold text-slate-900">Header preview</h2><span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700">Live</span></div>
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 shadow-sm" style="height: {{ data_get($config,'header.height','4rem') }}">
                            <div class="flex min-w-0 items-center gap-2"><span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xs font-bold text-indigo-700">{{ mb_strtoupper(mb_substr(data_get($config,'header.brand.title') ?: config('app.name'),0,1)) }}</span><span class="min-w-0 truncate text-xs font-semibold text-slate-800">{{ data_get($config,'header.brand.title') ?: config('app.name') }}</span></div>
                            <div class="flex shrink-0 items-center gap-1">@if(data_get($config,'header.notifications',true))<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-100 text-indigo-700">@include('Admin::livewire.partials.header.components.action-icon', ['icon' => data_get($config,'header.actions.notification.icon','bell'), 'class'=>'h-4 w-4'])</span>@endif @foreach(array_slice((array)data_get($config,'header.actions.items',[]),0,2) as $action)<span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-100 text-indigo-700">@include('Admin::livewire.partials.header.components.action-icon', ['icon' => data_get($action,'icon','link'), 'class'=>'h-4 w-4'])</span>@endforeach</div>
                        </div>
                    </div>
                    <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 text-sm"><div><dt class="text-slate-400">Height</dt><dd class="font-semibold text-slate-700">{{ $heightLabels[data_get($config,'header.height','4rem')] ?? '64 px' }}</dd></div><div><dt class="text-slate-400">Mode</dt><dd class="font-semibold text-slate-700">{{ data_get($config,'header.presentation.mode','balanced') }}</dd></div><div><dt class="text-slate-400">Actions</dt><dd class="font-semibold text-slate-700">{{ count((array)data_get($config,'header.actions.items',[])) + (data_get($config,'header.notifications',true) ? 1 : 0) }}</dd></div><div><dt class="text-slate-400">UserMenu</dt><dd class="font-semibold text-slate-700">{{ count((array)data_get($config,'header.user_menu_config.items',[])) }} items</dd></div></dl>
                    <p class="mt-5 text-xs leading-5 text-slate-500">Preview cập nhật trước khi lưu. Save sẽ reload để Header runtime nhận toàn bộ cấu hình mới.</p>
                </section>
                <section class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-5"><h3 class="text-sm font-semibold text-indigo-800">UX guidelines</h3><ul class="mt-3 space-y-2 text-xs leading-5 text-indigo-700"><li>• Input và combobox dùng cùng chiều cao 40px.</li><li>• System action không xóa, chỉ cấu hình.</li><li>• Custom action và UserMenu tách primary/advanced fields rõ ràng.</li></ul></section>
            </aside>
        </div>

        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Vui lòng kiểm tra lại các trường Header.</div>@endif
        <div class="sticky bottom-4 z-20 flex justify-end border-t border-slate-200 bg-slate-50/90 py-4 backdrop-blur"><button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20">Lưu Header</button></div>
    </form>
</div>

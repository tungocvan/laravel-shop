@php
    $adminConfig = app(\Modules\Admin\Support\AdminLayoutManager::class)->config();
    $adminLayoutConfig = $adminConfig['layout'] ?? [];
    $adminSidebarConfig = $adminConfig['sidebar'] ?? [];
    $adminHeaderConfig = $adminConfig['header'] ?? [];
@endphp

<!DOCTYPE html>
<html lang="{{ $adminConfig['locale'] ?? 'vi' }}">
    @include('Admin::layouts.partials.head')

    <body
        class="h-full bg-slate-50"
        x-data="adminLayout({
            persistSidebar: @js((bool) data_get($adminSidebarConfig, 'persist_state', true))
        })"
        x-init="init()"
        @keydown.escape.window="closeOverlays()"
        :class="{ 'overflow-hidden': (!isDesktop && sidebarOpen) || searchOpen }"
    >
        <x-admin::layout.skip-link />

        @include('Admin::layouts.partials.shell')
        @include('Admin::layouts.partials.stacks')
        @include('Admin::layouts.partials.scripts')
    </body>
</html>

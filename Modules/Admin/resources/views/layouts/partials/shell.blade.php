@php
    $sidebarEnabled = (bool) data_get($adminSidebarConfig, 'enabled', true);
    $collapseToggleEnabled = (bool) data_get($adminSidebarConfig, 'desktop_collapsible', true)
        && (bool) data_get($adminSidebarConfig, 'controls.collapse_enabled', true);
    $fullscreenToggleEnabled = (bool) data_get($adminSidebarConfig, 'controls.fullscreen_enabled', true);
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
@endphp

<style>
    [data-admin-shell-workspace] {
        min-width: 0;
    }

    @media (min-width: 1024px) {
        [data-admin-shell] {
            display: grid;
            grid-template-columns: var(--admin-sidebar-track, 0px) minmax(0, 1fr);
            transition: grid-template-columns 300ms ease-out;
        }

        [data-admin-shell-sidebar] {
            position: relative !important;
            inset: auto !important;
            width: 100% !important;
            min-width: 0;
            grid-column: 1;
        }

        [data-admin-shell-workspace] {
            grid-column: 2;
            width: auto !important;
            margin-left: 0 !important;
        }
    }
</style>
@php
    $dashboardUser = auth('admin')->user();
    $canReturnToMuasamcongDashboard = false;

    if ($dashboardUser && method_exists($dashboardUser, 'checkPermissionTo')) {
        try {
            $canReturnToMuasamcongDashboard = $dashboardUser->checkPermissionTo('view_muasamcong', 'admin');
        } catch (\Throwable) {
            $canReturnToMuasamcongDashboard = false;
        }
    }
@endphp

@if ($canReturnToMuasamcongDashboard)
    <a href="{{ route('muasamcong.dashboard') }}"
       class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <span aria-hidden="true">←</span>
        <span>Quay về Dashboard</span>
    </a>
@endif

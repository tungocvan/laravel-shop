@php
    $invoiceDashboardUser = auth('admin')->user();
    $canReturnToInvoiceDashboard = false;

    if ($invoiceDashboardUser) {
        try {
            $canReturnToInvoiceDashboard = (
                method_exists($invoiceDashboardUser, 'hasRole')
                && $invoiceDashboardUser->hasRole('Super Admin', 'admin')
            ) || (
                method_exists($invoiceDashboardUser, 'checkPermissionTo')
                && $invoiceDashboardUser->checkPermissionTo('invoices-list', 'admin')
            );
        } catch (\Throwable) {
            $canReturnToInvoiceDashboard = false;
        }
    }
@endphp

@if ($canReturnToInvoiceDashboard)
    <a href="{{ route('admin.invoices.dashboard') }}"
       class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <span aria-hidden="true">←</span>
        <span>Quay về Dashboard</span>
    </a>
@endif

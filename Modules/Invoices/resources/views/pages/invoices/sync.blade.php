@extends('Admin::layouts.master')

@section('title', 'Đồng bộ hóa đơn')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <header class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="max-w-4xl">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices · GDT operations</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">Đồng bộ hóa đơn GDT</h1>
                    <p class="mt-2 text-sm leading-6 text-gray-500">Điểm thu nhận dữ liệu GDT canonical của hệ thống. Khi đồng bộ, header hóa đơn được ghi vào danh sách hóa đơn và RAW header + detail được lưu để Inventory cùng các nghiệp vụ downstream tái sử dụng mà không gọi GDT lần hai.</p>
                </div>

                <nav aria-label="Điều hướng hóa đơn" class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 rounded-xl border border-gray-200 bg-gray-50 p-1.5 xl:w-auto">
                    <a href="{{ route('admin.invoices.source-data') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-gray-200 hover:bg-indigo-50 sm:text-sm">
                        Dữ liệu nguồn GDT
                    </a>
                    <a href="{{ route('admin.invoices.hoadon-list') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white hover:shadow-sm sm:text-sm">
                        Danh sách hóa đơn
                    </a>
                    <span class="hidden h-5 w-px bg-gray-300 sm:block"></span>
                    <div class="[&>a]:min-h-9 [&>a]:rounded-lg [&>a]:border-0 [&>a]:bg-transparent [&>a]:px-3 [&>a]:py-2 [&>a]:text-xs [&>a]:shadow-none hover:[&>a]:bg-white sm:[&>a]:text-sm">
                        @include('Invoices::partials.dashboard-return-link')
                    </div>
                </nav>
            </div>
        </header>

        <section aria-label="Kết nối GDT">
            <livewire:invoices.quick-gdt-connect />
        </section>

        <section aria-label="Đồng bộ và quản lý file hóa đơn">
            @livewire('invoices.search-hoadon')
        </section>

        <details class="group rounded-2xl border border-gray-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Backup nâng cao</p>
                    <h2 class="mt-1 text-base font-semibold text-gray-900">Local ↔ Google Drive</h2>
                    <p class="mt-1 text-sm text-gray-500">Mở khi cần chủ động sao chép file giữa kho local và thư mục backup trên Drive.</p>
                </div>
                <span class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-600 group-open:hidden">Mở</span>
                <span class="hidden rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-600 group-open:inline-flex">Thu gọn</span>
            </summary>
            <div class="border-t border-gray-100 p-4 sm:p-6">
                @livewire('invoices.invoice-drive-sync-panel')
            </div>
        </details>

        <details class="group rounded-2xl border border-violet-100 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Backup tự động</p>
                    <h2 class="mt-1 text-base font-semibold text-gray-900">Automatic Invoice Backup</h2>
                    <p class="mt-1 text-sm text-gray-500">Cấu hình lịch, email nhận, trạng thái và lịch sử backup. Mặc định thu gọn để workspace tập trung vào nghiệp vụ đồng bộ.</p>
                </div>
                <span class="rounded-xl border border-violet-100 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 group-open:hidden">Mở</span>
                <span class="hidden rounded-xl border border-violet-100 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 group-open:inline-flex">Thu gọn</span>
            </summary>
            <div class="border-t border-violet-100 p-4 sm:p-6">
                @livewire('invoices.automatic-backup-panel')
            </div>
        </details>
    </div>
@endsection

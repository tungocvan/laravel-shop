@extends('Admin::layouts.master')

@section('title', 'Quản lý công nợ')

@section('content')
@php
    $sections = [
        'dashboard' => ['Tổng quan', 'Dashboard', 'Toàn cảnh công nợ phải thu và phải trả'],
        'partners' => ['Nghiệp vụ', 'Đối tác', 'Đối tác và tình trạng công nợ'],
        'documents' => ['Nghiệp vụ', 'Chứng từ', 'Hóa đơn và chứng từ phát sinh công nợ'],
        'ledger' => ['Nghiệp vụ', 'Chi tiết công nợ', 'Sổ công nợ theo từng đối tác'],
        'summary' => ['Báo cáo', 'Tổng hợp', 'Số dư đầu kỳ, phát sinh và cuối kỳ'],
        'aging' => ['Báo cáo', 'Tuổi nợ', 'Phân tích các khoản công nợ đến hạn và quá hạn'],
        'settings' => ['Thiết lập', 'Cài đặt', 'Quy tắc theo dõi và đối chiếu công nợ'],
    ];
    $current = $sections[$section];
@endphp
<div class="w-full px-2 py-4 sm:px-4" x-data="{ debtMenuOpen: false }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
        <aside class="w-full shrink-0 rounded-xl border border-slate-200 bg-white lg:sticky lg:top-4 lg:w-64" aria-label="Điều hướng quản lý công nợ">
            <div class="flex items-center justify-between border-b border-slate-200 p-4">
                <div>
                    <p class="text-base font-bold text-slate-900">Quản lý công nợ</p>
                    <p class="text-xs text-slate-500">Phải thu · Phải trả · Tuổi nợ</p>
                </div>
                <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:hidden" x-on:click="debtMenuOpen = !debtMenuOpen" x-bind:aria-expanded="debtMenuOpen.toString()" aria-controls="finance-debt-navigation">Menu</button>
            </div>
            <nav id="finance-debt-navigation" class="space-y-3 p-3" x-bind:class="debtMenuOpen ? 'block' : 'hidden lg:block'">
                @foreach (['Tổng quan', 'Nghiệp vụ', 'Báo cáo', 'Thiết lập'] as $group)
                    <div>
                        <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $group }}</p>
                        @foreach ($sections as $key => $item)
                            @if ($item[0] === $group)
                                <a href="{{ route('admin.finance.debts.index', ['section' => $key]) }}"
                                   @if ($section === $key) aria-current="page" @endif
                                   class="block rounded-lg px-3 py-2 text-sm font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-500 {{ $section === $key ? 'bg-orange-100 text-orange-900' : 'text-slate-700 hover:bg-slate-100' }}">
                                    {{ $item[1] }}
                                    @if(!in_array($key, ['documents', 'dashboard', 'partners', 'ledger'], true))
                                        <span class="ml-1 text-xs font-normal text-amber-700">(Đang xây dựng)</span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endforeach
                <div class="border-t border-slate-200 pt-3">
                    <a href="{{ route('admin.finance.index') }}" class="block rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">← Quay lại Finance</a>
                </div>
            </nav>
        </aside>
        <main class="min-w-0 flex-1 space-y-4">
            <header class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Finance / Công nợ</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ $current[1] }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $current[2] }}</p>
            </header>
            @if ($section === 'dashboard')
                <livewire:finance.debt-dashboard />
            @elseif ($section === 'partners')
                <livewire:finance.debt-partners />
            @elseif ($section === 'ledger')
                <livewire:finance.debt-ledger />
            @elseif ($section === 'documents')
                <livewire:finance.debt-workspace />
            @else
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5" aria-label="Trạng thái triển khai">
                <h2 class="text-base font-semibold text-amber-950">Không gian công nợ đang được xây dựng</h2>
                <p class="mt-2 text-sm leading-6 text-amber-900">
                    Mục này hiện chỉ là giao diện định hướng, chưa có chức năng nghiệp vụ hoặc báo cáo để kiểm thử. Chưa có dữ liệu công nợ được đồng bộ từ Invoices. Công nợ thủ công được quản lý tại mục Chứng từ.
                    Các số dư, biểu đồ và báo cáo sẽ chỉ xuất hiện sau khi hợp đồng tích hợp hóa đơn và quy tắc ghi nhận công nợ được kiểm thử.
                </p>
                <p class="mt-2 text-sm text-amber-900">Thu/chi hiện tại vẫn do Finance quản lý; không tạo chứng từ hoặc thay đổi sổ quỹ từ màn hình này.</p>
                <a href="{{ route('admin.finance.debts.index', ['section' => 'documents']) }}" class="mt-3 inline-flex min-h-10 items-center rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white">Mở công nợ thủ công để kiểm thử →</a>
            </section>
            @endif
        </main>
    </div>
</div>
@endsection

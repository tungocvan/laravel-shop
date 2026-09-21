@extends('Admin::layouts.master')

@section('title', 'Danh mục thuốc chuẩn')
@section('admin_container', 'full')

@section('content')
    <div class="w-full space-y-4">
        <nav aria-label="Điều hướng Pharma" class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.pharma.dashboard') }}"
               class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <span aria-hidden="true">←</span>
                Quay về Dashboard Pharma
            </a>
            <a href="{{ route('admin.pharma.medicines.import.index') }}"
               class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Import danh mục thuốc chuẩn
            </a>
        </nav>

        @livewire('pharma.medicine.index')
    </div>
@endsection

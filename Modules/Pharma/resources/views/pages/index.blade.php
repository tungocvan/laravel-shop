@extends('Admin::layouts.master')

@section('title', 'Pharma')

@section('content')
    <div class="container-fluid space-y-4">
        <nav aria-label="Điều hướng Pharma" class="flex items-center">
            <a href="{{ route('admin.pharma.dashboard') }}"
               class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <span aria-hidden="true">←</span>
                Quay về Dashboard Pharma
            </a>
        </nav>

        @livewire('pharma.medicine.index')
    </div>
@endsection

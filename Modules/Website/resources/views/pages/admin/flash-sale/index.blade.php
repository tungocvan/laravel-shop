@extends('Admin::layouts.master')

@section('content')
    @if(request('from') === 'homepage')
        <div class="mx-auto mb-4 max-w-7xl">
            <a href="{{ route('admin.home.settings') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                ← Quay lại bố cục
            </a>
        </div>
    @endif

    @livewire('website.admin.flash-sale.flash-sale-manager')
@endsection

@extends('Admission::layouts.auth')

@section('content')
    @php
        $schoolSettings = app(\Modules\Admission\Services\SchoolSettingService::class)->all();
    @endphp
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6">
                <div class="flex items-center gap-4 mb-8 border-b pb-6">
                    <img src="{{ asset('storage/admission/img/logo.png') }}" alt="Logo"
                        class="w-16 h-16 object-contain rounded-xl border border-gray-200 shadow-sm">

                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-blue-900 tracking-tight">
                            TRA CỨU THÔNG TIN NHẬP HỌC NĂM {{ $schoolSettings['school_year'] }}
                        </h1>

                        <p class="text-gray-600 mt-1 text-sm sm:text-base">
                            Vui lòng nhập Mã định danh và ngày tháng năm sinh để tra cứu thông tin nhập học.
                        </p>
                    </div>
                </div>

                @if (session('warning'))
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ session('warning') }}
                    </div>
                @endif

                <livewire:admission.search />
            </div>
        </div>
    </div>
@endsection

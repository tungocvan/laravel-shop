@extends('Admission::layouts.auth')

@section('content')
    @php
        $schoolSettings = app(\Modules\Admission\Services\SchoolSettingService::class)->all();
    @endphp
    <div class="py-12 bg-gray-100 min-h-screen">
         @php
            $siteLogo = \Modules\Admin\Models\Setting::getValue('site_logo');
        @endphp
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6">

                {{-- HEADER --}}
                <div class="flex items-center gap-4 mb-8 border-b pb-6">

                    {{-- LOGO --}}
                    <img src="{{ $siteLogo ? asset('storage/' . $siteLogo) : asset('storage/admission/img/logo.png') }}" alt="Logo"
                        class="w-16 h-16 object-contain rounded-xl border border-gray-200 shadow-sm">

                    {{-- TITLE --}}
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-blue-900 tracking-tight">
                            TRA CỨU THÔNG TIN NHẬP HỌC NĂM {{ $schoolSettings['school_year'] }}
                        </h1>

                        <p class="text-gray-600 mt-1 text-sm sm:text-base">
                            Vui lòng nhập Mã định danh và ngày tháng năm sinh để tra cứu thông tin nhập học.
                        </p>
                    </div>
                </div>

                {{-- FORM --}}
                <livewire:admission.search :ma_dinh_danh="$ma_dinh_danh" :password="$password" />

            </div>

        </div>
    </div>
@endsection

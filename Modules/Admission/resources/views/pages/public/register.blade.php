@extends('Admin::layouts.master')

@section('content')
<div class="py-12 bg-gray-100 min-h-screen">
    @php
        $siteLogo = \Modules\Admin\Models\Setting::getValue('site_logo');
    @endphp
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6">

            {{-- HEADER --}}
            <div class="flex items-center gap-4 mb-8 border-b pb-6">

                {{-- LOGO --}}
                <img src="{{ $siteLogo ? asset('storage/' . $siteLogo) : asset('storage/admission/img/logo.png') }}"
                     alt="Logo"
                     class="w-16 h-16 object-contain rounded-xl border border-gray-200 shadow-sm">

                {{-- TITLE --}}
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-blue-900 tracking-tight">
                        CỔNG ĐĂNG KÝ NHẬP HỌC TRỰC TUYẾN
                    </h1>

                    <p class="text-gray-600 mt-1 text-sm sm:text-base">
                        Vui lòng điền đầy đủ và chính xác thông tin để làm hồ sơ nhập học cho con.
                    </p>
                </div>
            </div>

            {{-- FORM --}}
            @livewire('admission.public.registration-form')

        </div>

    </div>
</div>
@endsection

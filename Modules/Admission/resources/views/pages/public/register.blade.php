@extends('Admin::layouts.master')

@section('content')
<div class="py-12 bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6">

            @include('Admission::pages.public.partials.branding-header', [
                'title' => 'CỔNG ĐĂNG KÝ NHẬP HỌC TRỰC TUYẾN',
                'description' => 'Vui lòng điền đầy đủ và chính xác thông tin để làm hồ sơ nhập học cho con.',
            ])

            {{-- FORM --}}
            @livewire('admission.public.registration-form')

        </div>

    </div>
</div>
@endsection

@extends('Admission::layouts.auth')

@section('content')
    @php
        $schoolSettings = app(\Modules\Admission\Services\SchoolSettingService::class)->all();
    @endphp
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6">
                @include('Admission::pages.public.partials.branding-header', [
                    'title' => 'TRA CỨU THÔNG TIN NHẬP HỌC NĂM '.$schoolSettings['school_year'],
                    'description' => 'Vui lòng nhập Mã định danh và ngày tháng năm sinh để tra cứu thông tin nhập học.',
                ])

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

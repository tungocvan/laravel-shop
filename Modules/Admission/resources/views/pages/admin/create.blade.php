@extends('Admin::layouts.master')

@section('content')
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Đăng ký Nhập học</h2>
            @can('view_admin')
                <a href="{{ route('admin.admission.create') }}"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow transition">
                    + Thêm đơn mới
                </a>
            @endcan   
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            @livewire('admission.public.registration-form', ['id' => $id ?? null])
        </div>
    </div>
@endsection

@extends('Admin::layouts.master')

@section('title', 'Tra cứu thuốc trúng thầu')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tra cứu thuốc trúng thầu</h1>
            <p class="mt-1 text-sm text-gray-500">Tra cứu cơ sở dữ liệu đơn giá trúng thầu trên Hệ thống mạng đấu thầu quốc gia.</p>
        </div>

        @livewire('muasamcong.tracuu-thuoctrungthau')
    </div>
@endsection

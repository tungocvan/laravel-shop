@extends('Admin::layouts.master')

@section('title', 'Quản lý đối tác')

@section('content')
    <div class="container-fluid">
        @livewire('partner.partner.index', [
            'legalType' => request()->query('legalType', ''),
        ])
    </div>
@endsection

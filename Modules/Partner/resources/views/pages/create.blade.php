@extends('Admin::layouts.master')

@section('title', 'Thêm đối tác')

@section('content')
    <div class="container-fluid">
        @livewire('partner.partner.form', [
            'legal_type' => request()->query('legal_type', 'company'),
        ])
    </div>
@endsection

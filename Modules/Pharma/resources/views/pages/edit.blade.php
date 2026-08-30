@extends('Admin::layouts.master')

@section('title', 'Pharma')

@section('content')
    <div class="container-fluid">
        @include('Pharma::pages.partials.dashboard-back')

        @livewire('pharma.medicine.form', ['id' => $id])
    </div>
@endsection

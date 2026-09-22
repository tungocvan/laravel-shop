@extends('Admin::layouts.master')

@section('admin_container', 'full')

@section('title', 'Pharma')

@section('content')
    <div class="container-fluid">
        @include('Pharma::pages.partials.dashboard-back')

        @livewire('pharma.drug-bid-award.index')
    </div>
@endsection

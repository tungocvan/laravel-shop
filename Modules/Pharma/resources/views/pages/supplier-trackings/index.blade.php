@extends('Admin::layouts.master')

@section('title', 'Pharma')
@section('admin_container', 'full')

@section('content')
    <div class="w-full">
        @include('Pharma::pages.partials.dashboard-back')

        @livewire('pharma.supplier-trackings.index')
    </div>
@endsection

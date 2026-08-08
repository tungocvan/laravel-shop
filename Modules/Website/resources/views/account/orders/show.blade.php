@extends('Website::layouts.account')

@section('content-account')
    @livewire('website.account.order-detail', ['code' => $code])
@endsection

@extends('Admin::layouts.master')
@section('title', 'Cập nhật mã giảm giá')
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css"> <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
@section('content')
    <livewire:admin.marketing.coupon-form :id="$id" />
@endsection
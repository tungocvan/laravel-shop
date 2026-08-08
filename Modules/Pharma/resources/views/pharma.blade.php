@extends('Admin::layouts.master')

@section('title', 'Pharma')

@section('content')
    <div class="space-y-4">
        <h1 class="text-2xl font-semibold text-slate-900">Pharma module</h1>
        <p class="text-sm text-slate-500">Loai module: domain.</p>

        @include('Pharma::components.placeholder')
    </div>
@endsection
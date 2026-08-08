@extends('Admin::layouts.master')

@section('title', 'Category')

@section('content')
    <div class="space-y-4">
        <h1 class="text-2xl font-semibold text-slate-900">Category module</h1>
        <p class="text-sm text-slate-500">Loai module: support.</p>

        @include('Category::components.placeholder')
    </div>
@endsection
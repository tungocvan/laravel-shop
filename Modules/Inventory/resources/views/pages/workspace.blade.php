@extends('Admin::layouts.master')

@section('title', 'Inventory')

@section('content')
    <livewire:inventory.admin-workspace :workspace="$workspace" />
@endsection

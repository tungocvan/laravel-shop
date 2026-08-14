@extends('Admin::layouts.master')

@section('title', 'Ebook Knowledge Base')

@section('content')
    @livewire('ebook.ebook-viewer', ['documentId' => $documentId])
@endsection

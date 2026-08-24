@extends('Admin::layouts.master')
@section('title', __('Request::request.versions'))
@section('content')
<div class="p-4 sm:p-6"><h1 class="text-2xl font-semibold">{{ $type->name }} — {{ __('Request::request.versions') }}</h1><div class="mt-6 space-y-3">@foreach($type->versions as $version)<div class="rounded-xl border border-gray-200 bg-white p-4"><strong>v{{ $version->version_number }}</strong> · {{ $version->status->value }} @if($version->canonical_checksum)<code class="ml-2 text-xs">{{ $version->canonical_checksum }}</code>@endif</div>@endforeach</div></div>
@endsection

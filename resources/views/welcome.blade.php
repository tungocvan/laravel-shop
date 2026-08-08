@extends('Auth::layouts.auth')

@section('title', 'HOME')

@section('content')
<div class="container">
    <h2>HOME PAGE</h2>
    @livewire('chat.chat.chat-widget')
</div>

@endsection

{{-- @section('css')
@stack('styles') --}}
    {{-- Sử dụng ở component @push(css)<style>...</style>@endpush ở cuối file --}}
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}

{{-- @stop

@section('js')
@stack('scripts') --}}
     {{-- <script>
        document.addEventListener("DOMContentLoaded", () => {
            console.log("Lắng nghe sự kiện DOMContentLoaded được gọi trước jquery");
        })
     </script>
@stop --}}

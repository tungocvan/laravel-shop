@extends('Website::layouts.frontend')

@section('content')
    <div class="bg-gray-50 min-h-[calc(100vh-theme(spacing.16))] py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                <div class="lg:col-span-1">

                    @include('Website::partials.account-sidebar')
                </div>

                <div class="lg:col-span-3">
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content-account')
                    @endisset
                </div>

            </div>
        </div>
    </div>
@endsection

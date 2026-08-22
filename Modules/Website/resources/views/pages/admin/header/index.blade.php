@extends('Admin::layouts.master')

@section('title', 'Quản lý Header & Menu')

@section('content')
    @livewire('website.admin.header.header-settings-hub')
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-toast', (event) => {
            const data = event[0] ?? event;

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: data.type || 'success',
                title: data.message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
</script>
@endsection

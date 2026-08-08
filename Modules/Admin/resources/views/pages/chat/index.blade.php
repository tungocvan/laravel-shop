@extends('Admin::layouts.master')

@section('title', 'Quản lý Chat Realtime')

@section('content')
<div class="container-fluid px-6 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Hệ thống hỗ trợ trực tuyến</h2>
            <p class="text-sm text-gray-500">Quản lý và phản hồi tin nhắn từ khách hàng realtime.</p>
        </div>

        <div class="flex items-center gap-2 bg-green-50 px-3 py-1 rounded-full border border-green-200">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            <span class="text-xs font-medium text-green-700">Socket Connected</span>
        </div>
    </div>

    @livewire('admin.chat.chat-manager')
</div>
@endsection

@push('scripts')
<script>
    // Hàm cuộn xuống cuối
    function scrollToBottom() {
        const chatWindow = document.getElementById('chat-window');
        if (chatWindow) {
            chatWindow.scrollTo({
                top: chatWindow.scrollHeight,
                behavior: 'smooth'
            });
        }
    }

    // Lắng nghe sự kiện "scroll-chat" phát ra từ Livewire Component
    window.addEventListener('scroll-chat-to-bottom', () => {
        setTimeout(scrollToBottom, 100);
    });

    // Tự động cuộn khi trang vừa load xong
    document.addEventListener('DOMContentLoaded', scrollToBottom);
</script>
@endpush

<div x-data="{ open: @entangle('isOpen') }" class="fixed bottom-6 right-6 z-[9999]">
    <button @click="open = !open"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 shadow-lg hover:scale-110 transition-transform text-white">
        <svg x-show="!open" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
            </path>
        </svg>
        <svg x-show="open" x-cloak class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        class="absolute bottom-20 right-0 w-80 sm:w-96 h-[500px] bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden">

        <div class="bg-blue-600 p-4 text-white flex items-center gap-3">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            <h3 class="font-bold text-sm">Hỗ trợ trực tuyến</h3>
        </div>

        @if ($step == 'auth')
            <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <p class="text-gray-500 text-sm mb-4">Chào bạn! Chúng tôi có thể giúp gì cho bạn?</p>
                <button wire:click="startChat"
                    class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold text-sm shadow-md hover:bg-blue-700 transition-all">
                    Bắt đầu Chat ngay
                </button>
            </div>
        @else
            <div id="chat-content" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 custom-scrollbar">
                @foreach ($messages as $msg)
                    <div wire:key="msg-{{ $msg->id }}"
                        class="flex {{ $msg->sender_type != 'admin' ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[85%] p-3 rounded-2xl text-sm shadow-sm
                            {{ $msg->sender_type != 'admin' ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white text-gray-700 border border-gray-100 rounded-bl-none' }}">
                            {{ $msg->message }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-3 bg-white border-t border-gray-100">
                <form wire:submit.prevent="send" class="flex gap-2">
                    <input wire:model="message" type="text" placeholder="Nhập tin nhắn..."
                        class="flex-1 bg-gray-100 border-none rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="text-blue-600 hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 rotate-90" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z">
                            </path>
                        </svg>
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            const chatContent = document.getElementById('chat-content');
            const scroll = () => {
                if (chatContent) chatContent.scrollTop = chatContent.scrollHeight;
            };
            window.addEventListener('scroll-bottom', () => setTimeout(scroll, 50));
            scroll();

            const initializeChatRealtime = () => {

            const initialSessionId = @js($chatSessionId);

            const joinChatSession = (sessionId) => {
                if (!sessionId) return;

                console.log("🚪 Client join session:", sessionId);

                if (window.socket.connected) {
                    window.joinSession(sessionId);
                } else {
                    window.socket.once("connect", () => {
                        window.joinSession(sessionId);
                    });
                }
            };

            joinChatSession(initialSessionId);

            Livewire.on("chat-session-ready", (event) => {
                joinChatSession(event.sessionId);
            });


            window.socket.onAny((eventName, data) => {
                if (eventName === 'MessageSent') {
                    console.log("📡 Widget nhận tín hiệu, đang làm mới UI...");

                    // 'refresh-widget' là listener đã khai báo trong ChatWidget.php
                    Livewire.dispatch('refresh-widget');

                    setTimeout(() => {
                        if (chatContent) chatContent.scrollTop = chatContent.scrollHeight;
                    }, 300);
                }
                // Trường hợp 2: Có tin nhắn bị xóa (Cần bổ sung)
                if (eventName === 'MessageDeleted') {
                    console.log("🗑️ Phát hiện tin nhắn bị xóa, đang cập nhật UI...");

                    // Ép Livewire gọi lại hàm render() để lấy danh sách tin nhắn mới từ DB
                    Livewire.dispatch('refresh-chat');

                    // Hoặc nếu muốn mượt hơn, bạn có thể dùng JS xóa trực tiếp phần tử DOM
                    // const el = document.querySelector(`[wire\\:key="msg-${data.message_id}"]`);
                    // if(el) el.remove();
                }
                // Nếu nhận được lệnh xóa tất cả
                if (eventName === 'AllMessagesDeleted') {
                    console.log("⚠️ Toàn bộ tin nhắn đã bị xóa bởi quản trị viên.");

                    // Ép Livewire làm mới (lúc này DB đã trống, UI sẽ sạch tin nhắn)
                    Livewire.dispatch('refresh-widget');

                    // Hoặc có thể dùng JS để xóa nhanh DOM nếu không muốn đợi Livewire
                    // document.getElementById('chat-content').innerHTML = '';
                }
            });

            };

            if (window.socket) {
                initializeChatRealtime();
            } else {
                window.addEventListener('realtime:ready', initializeChatRealtime, { once: true });
            }
        });
    </script>
@endpush

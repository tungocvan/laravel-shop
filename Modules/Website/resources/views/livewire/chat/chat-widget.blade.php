@php
    $chatPosition = $position ?? 'bottom-right';
    $chatOnLeft = $chatPosition === 'bottom-left';
    $chatMiddleRight = $chatPosition === 'right-middle';
@endphp
<div class="fixed z-[9999] {{ $chatMiddleRight ? 'right-6 top-1/2 -translate-y-1/2' : 'bottom-6 '.($chatOnLeft ? 'left-6' : 'right-6') }}">
    <button wire:click="toggleChat"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 shadow-lg hover:scale-110 transition-transform text-white"
        aria-label="{{ $isOpen ? 'Đóng chat hỗ trợ' : 'Mở chat hỗ trợ' }}">
        @if (! $isOpen)
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                </path>
            </svg>
        @else
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        @endif
    </button>

    @if ($isOpen)
        <div class="absolute w-80 sm:w-96 h-[500px] max-h-[80vh] bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden
            {{ $chatMiddleRight ? 'right-20 top-1/2 -translate-y-1/2' : 'bottom-20 '.($chatOnLeft ? 'left-0' : 'right-0') }}">

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
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            const componentId = @js($this->getId());
            const component = Livewire.find(componentId);

            if (!component) {
                return;
            }

            const scroll = () => {
                const chatContent = document.getElementById('chat-content');
                if (chatContent) chatContent.scrollTop = chatContent.scrollHeight;
            };

            window.addEventListener('scroll-bottom', () => setTimeout(scroll, 50));
            scroll();

            const initializeChatRealtime = () => {
                if (!window.socket) {
                    return;
                }

                const joinChatSession = (sessionId) => {
                    if (!sessionId) return;

                    if (window.socket.connected) {
                        window.joinSession(sessionId);
                    } else {
                        window.socket.once('connect', () => window.joinSession(sessionId));
                    }
                };

                joinChatSession(component.chatSessionId);

                Livewire.on('chat-session-ready', (event) => {
                    joinChatSession(event.sessionId);
                });

                window.socket.onAny((eventName) => {
                    if (eventName === 'MessageSent') {
                        Livewire.dispatch('refresh-widget');
                        setTimeout(scroll, 300);
                    }
                    if (eventName === 'MessageDeleted') {
                        Livewire.dispatch('refresh-chat');
                    }
                    if (eventName === 'AllMessagesDeleted') {
                        Livewire.dispatch('refresh-widget');
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

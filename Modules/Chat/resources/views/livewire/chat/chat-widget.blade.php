<div class="fixed bottom-6 right-6 z-[9999]">
    <button wire:click="toggleChat"
        class="group flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-r from-blue-600 to-indigo-600 shadow-2xl shadow-blue-500/30 hover:scale-105 active:scale-95 transition-all text-white"
        aria-label="{{ $isOpen ? 'Đóng chat hỗ trợ' : 'Mở chat hỗ trợ' }}">
        @if (! $isOpen)
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
        @else
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        @endif
    </button>

    @if ($isOpen)
        <div class="fixed bottom-24 right-6 w-[380px] max-w-[calc(100vw-24px)] h-[650px] max-h-[calc(100vh-120px)] rounded-[28px] overflow-hidden bg-white border border-gray-200 shadow-[0_20px_80px_rgba(0,0,0,0.15)] flex flex-col z-[99999]">
            <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-5 text-white">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/20"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 rounded-full bg-white/10"></div>
                </div>

                <div class="relative flex items-center gap-4">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center font-bold text-lg">CS</div>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-400 border-2 border-white animate-pulse"></div>
                    </div>

                    <div>
                        <div class="font-bold text-base">Hỗ trợ trực tuyến</div>
                        <div class="text-xs text-blue-100 mt-1">Phản hồi trong vài phút</div>
                    </div>
                </div>
            </div>

            @if ($step == 'auth')
                <div class="flex-1 flex flex-col items-center justify-center px-8 text-center bg-gradient-to-b from-white to-gray-50">
                    <div class="w-24 h-24 rounded-[28px] bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center justify-center text-white text-4xl shadow-2xl shadow-blue-500/20">💬</div>

                    <h2 class="mt-8 text-2xl font-bold text-gray-900">Chào bạn 👋</h2>
                    <p class="mt-3 text-sm leading-relaxed text-gray-500 max-w-[260px]">Chúng tôi luôn sẵn sàng hỗ trợ bạn realtime</p>

                    <button wire:click="startChat"
                        class="mt-8 w-full rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 hover:scale-[1.02] transition">
                        Bắt đầu trò chuyện
                    </button>
                </div>
            @else
                <div id="chat-content" class="flex-1 overflow-y-auto px-5 py-6 bg-gray-50 space-y-5">
                    @foreach ($messages as $index => $msg)
                        @php
                            $senderType = is_object($msg) ? $msg->sender_type : $msg['sender_type'] ?? '';
                            $messageContent = is_object($msg) ? $msg->message : $msg['message'] ?? ($msg['content'] ?? '');
                            $msgId = is_object($msg) ? $msg->id : $msg['id'] ?? $index;

                            if (is_object($msg) && isset($msg->created_at)) {
                                $time = $msg->created_at->format('H:i');
                            } else {
                                $createdAt = $msg['created_at'] ?? now();
                                $time = $createdAt instanceof \Carbon\Carbon
                                    ? $createdAt->format('H:i')
                                    : date('H:i', strtotime($createdAt));
                            }

                            $isMine = $senderType !== 'admin';
                        @endphp

                        <div wire:key="msg-{{ $msgId }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%]">
                                <div class="px-4 py-3 rounded-3xl text-sm leading-relaxed shadow-sm {{ $isMine ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-br-md' : 'bg-white border border-gray-100 text-gray-700 rounded-bl-md' }}">
                                    {{ $messageContent }}
                                </div>
                                <div class="mt-1 px-1 text-[11px] text-gray-400 {{ $isMine ? 'text-right' : 'text-left' }}">{{ $time }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 bg-white p-4">
                    <form wire:submit.prevent="send" class="flex items-end gap-3">
                        <div class="flex-1">
                            <textarea wire:model.live="message" data-chat-input rows="1" placeholder="Nhập tin nhắn..."
                                class="w-full resize-none rounded-2xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition"></textarea>
                        </div>

                        <button type="submit"
                            class="h-[54px] w-[54px] rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/20 hover:scale-105 transition">
                            <svg class="w-5 h-5 rotate-45" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.94 2.94a1.5 1.5 0 011.635-.326l12 5a1.5 1.5 0 010 2.772l-12 5A1.5 1.5 0 012.5 14V10.5l7-1-7-1V4a1.5 1.5 0 01.44-1.06z" />
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

            if (!component || !window.socket) {
                return;
            }

            const joinActiveSession = () => {
                const sessionId = component.activeSessionId;

                if (sessionId) {
                    window.socket.emit('join-session', sessionId);
                }
            };

            window.socket.on('connect', joinActiveSession);
            joinActiveSession();

            window.addEventListener('chat-session-selected', (event) => {
                const sessionId = event.detail?.sessionId;

                if (sessionId) {
                    window.socket.emit('join-session', sessionId);
                }
            });

            window.socket.off('MessageSent');
            window.socket.on('MessageSent', (data) => {
                component.call('appendMessage', data);
            });

            document.addEventListener('input', (event) => {
                if (!event.target.matches(`[wire\\:id="${componentId}"] [data-chat-input]`)) {
                    return;
                }

                const sessionId = component.activeSessionId;

                if (sessionId) {
                    window.socket.emit('typing', { session_id: sessionId });
                }
            });
        });
    </script>
@endpush

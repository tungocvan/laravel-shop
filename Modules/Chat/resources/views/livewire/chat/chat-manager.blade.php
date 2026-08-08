<div
    wire:id="{{ $this->getId() }}"
    x-data="chatManager('{{ $this->getId() }}')"
    x-init="init()" 
    class="h-[calc(100vh-90px)] overflow-hidden rounded-3xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-800 shadow-sm">
    <div class="flex h-full">

        {{-- ========================================= --}}
        {{-- SIDEBAR --}}
        {{-- ========================================= --}}
        <aside
            class="w-[360px] border-r border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 flex flex-col">

            {{-- HEADER --}}
            <div
                class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Live Support
                        </h2>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Quản lý hội thoại khách hàng realtime
                        </p>
                    </div>

                    <div class="flex items-center gap-2">

                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Online
                        </span>

                    </div>

                </div>

                {{-- SEARCH --}}
                <div class="mt-4">
                    <div class="relative">

                        <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>

                        <input type="text" placeholder="Tìm kiếm hội thoại..."
                            class="w-full pl-11 pr-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                </div>
            </div>

            {{-- SESSION LIST --}}
            <div class="flex-1 overflow-y-auto">

                @forelse ($this->sessions as $session)
                    @php
                        $active = $activeSessionId === $session->id;
                    @endphp

                    <button wire:key="session-{{ $session->id }}" wire:click="selectSession({{ $session->id }})"
                        class="w-full px-5 py-4 border-b border-gray-100 dark:border-gray-800 transition-all duration-200
                        {{ $active ? 'bg-white dark:bg-gray-900' : 'hover:bg-white dark:hover:bg-gray-900/60' }}">

                        <div class="flex items-start gap-3">

                            {{-- AVATAR --}}
                            <div class="relative shrink-0">

                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow">
                                    {{ strtoupper(substr($session->guest_name ?: 'G', 0, 1)) }}
                                </div>

                                <div
                                    class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-gray-900 bg-emerald-500">
                                </div>

                            </div>

                            {{-- CONTENT --}}
                            <div class="flex-1 min-w-0">

                                <div class="flex items-center justify-between gap-2">

                                    <div class="font-semibold text-sm truncate text-gray-900 dark:text-white">
                                        {{ $session->guest_name ?: 'Guest User' }}
                                    </div>

                                    <div class="text-[11px] text-gray-400 whitespace-nowrap">
                                        {{ $session->last_message_at?->diffForHumans() }}
                                    </div>

                                </div>

                                <div class="mt-1 flex items-center justify-between gap-2">

                                    <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $session->latestMessage?->message ?: 'Chưa có tin nhắn' }}
                                    </div>

                                    @if (!$active)
                                        <div
                                            class="min-w-[20px] h-5 px-1.5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-semibold">
                                            1
                                        </div>
                                    @endif

                                </div>

                            </div>

                        </div>

                    </button>

                @empty

                    <div class="h-full flex items-center justify-center p-10">

                        <div class="text-center">

                            <div
                                class="w-16 h-16 mx-auto rounded-3xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                💬
                            </div>

                            <div class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Chưa có hội thoại
                            </div>

                            <div class="text-xs text-gray-400 mt-1">
                                Hội thoại mới sẽ xuất hiện realtime
                            </div>

                        </div>

                    </div>
                @endforelse

            </div>

        </aside>

        {{-- ========================================= --}}
        {{-- CHAT AREA --}}
        {{-- ========================================= --}}
        <section class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-950">

            @if ($this->activeSession)

                {{-- HEADER --}}
                <header
                    class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-white/90 dark:bg-gray-900/90 backdrop-blur">

                    <div class="flex items-center justify-between">

                        <div class="flex items-center gap-4">

                            <div class="relative">

                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold shadow">
                                    {{ strtoupper(substr($this->activeSession->guest_name ?: 'G', 0, 1)) }}
                                </div>

                                <div
                                    class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-900">
                                </div>

                            </div>

                            <div>

                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $this->activeSession->guest_name ?: 'Guest User' }}
                                </div>

                                <div class="text-xs text-emerald-500">
                                    Đang hoạt động
                                </div>

                            </div>

                        </div>

                        {{-- ACTIONS --}}
                        <div class="flex items-center gap-2">

                            <button
                                class="w-10 h-10 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center justify-center transition">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 10l4.553-4.553a2.121 2.121 0 00-3-3L12 7m0 0L7.447 2.447a2.121 2.121 0 10-3 3L9 10m3-3v10" />
                                </svg>
                            </button>

                        </div>

                    </div>

                </header>

                {{-- MESSAGES --}}
                <main id="chat-window" class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    @foreach ($messages as $msg)
                        @php
                            $isAdmin = $msg['sender_type'] === 'admin';
                        @endphp

                        <div wire:key="msg-{{ $msg['id'] }}"
                            class="flex {{ $isAdmin ? 'justify-end' : 'justify-start' }}">

                            <div class="max-w-[75%]">

                                {{-- BUBBLE --}}
                                <div
                                    class="px-5 py-3 rounded-3xl shadow-sm text-sm leading-relaxed
                                    {{ $isAdmin
                                        ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-br-md'
                                        : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200 rounded-bl-md' }}">

                                    {{ $msg['message'] }}

                                </div>

                                {{-- META --}}
                                <div
                                    class="mt-1 px-1 flex items-center gap-2 text-[11px]
                                    {{ $isAdmin ? 'justify-end text-gray-400' : 'justify-start text-gray-400' }}">

                                    <span>
                                        {{ \Carbon\Carbon::parse($msg['created_at'])->format('H:i') }}
                                    </span>

                                    @if ($isAdmin)
                                        <span class="text-blue-500">
                                            ✓✓
                                        </span>
                                    @endif

                                </div>

                            </div>

                        </div>
                    @endforeach

                </main>

                {{-- TYPING --}}
                <div x-show="typingUser" x-transition class="px-6 py-2 text-xs text-gray-400">
                    Đang nhập tin nhắn...
                </div>

                {{-- INPUT --}}
                <footer class="p-5 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

                    <form wire:submit.prevent="send" class="flex items-end gap-3">

                        {{-- INPUT --}}
                        <div class="flex-1">

                            <div class="relative">

                                <textarea wire:model.live="message" x-on:input="typing" rows="1" placeholder="Nhập tin nhắn..."
                                    class="w-full resize-none rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-5 py-4 pr-14 text-sm text-gray-800 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>

                                {{-- EMOJI --}}
                                <button type="button"
                                    class="absolute right-4 bottom-4 text-gray-400 hover:text-blue-500">
                                    😊
                                </button>

                            </div>

                        </div>

                        {{-- SEND --}}
                        <button type="submit" wire:loading.attr="disabled"
                            class="h-[54px] px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium shadow-lg shadow-blue-500/20 hover:scale-[1.02] active:scale-[0.98] transition">
                            Gửi
                        </button>

                    </form>

                </footer>
            @else
                {{-- EMPTY --}}
                <div class="flex-1 flex items-center justify-center">

                    <div class="text-center">

                        <div
                            class="w-28 h-28 mx-auto rounded-[2rem] bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-4xl shadow-2xl shadow-blue-500/20">
                            💬
                        </div>

                        <h3 class="mt-6 text-2xl font-bold text-gray-800 dark:text-white">
                            Live Chat Support
                        </h3>

                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Chọn một hội thoại để bắt đầu hỗ trợ khách hàng
                        </p>

                    </div>

                </div>

            @endif

        </section>

    </div>
</div>

@push('scripts')
<script>
    function chatManager(componentId) {

        return {

            typingUser: false,

            typingTimeout: null,

            currentRoom: null,

            init() {

                if (!window.socket) {
                    window.addEventListener(
                        'realtime:ready',
                        () => this.init(),
                        { once: true }
                    );
                    return;
                }

                console.log(
                    '✅ Chat initialized:',
                    componentId
                );

                /**
                 * Scroll event từ Livewire
                 */
                Livewire.on(
                    'scroll-bottom',
                    () => {

                        this.scrollBottom();
                    }
                );

                /**
                 * Chọn session
                 */
                window.addEventListener(
                    'chat-session-selected',
                    (event) => {

                        const sessionId =
                            event.detail.sessionId;

                        this.joinRoom(
                            sessionId
                        );
                    }
                );

                /**
                 * SOCKET: MESSAGE RECEIVED
                 */
                window.socket.off(
                    'MessageSent'
                );

                window.socket.on(
                    'MessageSent',
                    (data) => {

                        console.log(
                            '📨 Socket Message:',
                            data
                        );

                        /**
                         * gọi trực tiếp method PHP
                         */
                        Livewire
                            .find(componentId)

                            .call(
                                'appendMessage',
                                data
                            );
                    }
                );

                /**
                 * CUSTOMER TYPING
                 */
                window.socket.off(
                    'display-typing'
                );

                window.socket.on(
                    'display-typing',
                    () => {

                        this.typingUser =
                            true;

                        clearTimeout(
                            this.typingTimeout
                        );

                        this.typingTimeout =
                            setTimeout(
                                ()=>{

                                    this.typingUser =
                                        false;

                                },
                                2000
                            );
                    }
                );

                /**
                 * CUSTOMER STOP
                 */
                window.socket.off(
                    'hide-typing'
                );

                window.socket.on(
                    'hide-typing',
                    ()=>{

                        this.typingUser =
                            false;
                    }
                );
            },

            /**
             * JOIN ROOM
             */
            joinRoom(
                sessionId
            ) {

                if(
                    this.currentRoom
                ){

                    window.socket.emit(
                        'leave-session',
                        this.currentRoom
                    );
                }

                this.currentRoom =
                    sessionId;

                console.log(
                    '🚪 Join:',
                    sessionId
                );

                window.socket.emit(
                    'join-session',
                    sessionId
                );
            },

            /**
             * ADMIN TYPING
             */
            typing() {

                if(
                    !@this.activeSessionId
                ){
                    return;
                }

                window.socket.emit(
                    'typing',
                    {

                        session_id:
                            @this.activeSessionId,

                        sender_id:
                            {{ Auth::id() }}

                    }
                );
            },

            /**
             * SCROLL
             */
            scrollBottom() {

                this.$nextTick(
                    ()=>{

                        const el=
                            document.getElementById(
                                'chat-window'
                            );

                        if(
                            !el
                        ){
                            return;
                        }

                        el.scrollTo({

                            top:
                                el.scrollHeight,

                            behavior:
                                'smooth'

                        });

                    }
                );
            }

        }
    }
</script>
@endpush

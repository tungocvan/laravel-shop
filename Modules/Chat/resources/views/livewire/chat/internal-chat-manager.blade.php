<div
    class="h-[calc(100vh-110px)] overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

    <div class="flex h-full">

        {{-- ========================================= --}}
        {{-- SIDEBAR --}}
        {{-- ========================================= --}}
        <aside
            class="w-[340px] border-r border-zinc-200 bg-zinc-50/70 backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950/60 hidden md:flex flex-col">

            {{-- HEADER --}}
            <div
                class="px-5 py-5 border-b border-zinc-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Internal Chat
                        </h2>

                        <p class="text-xs text-zinc-500 mt-1">
                            Admin realtime communication
                        </p>
                    </div>

                    <div
                        class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600
                               flex items-center justify-center text-white shadow-lg">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z" />
                        </svg>
                    </div>

                </div>

                {{-- SEARCH --}}
                <div class="mt-5">

                    <div class="relative">

                        <input type="text" placeholder="Search admin..."
                            class="w-full rounded-2xl border border-zinc-200 dark:border-zinc-700
                                   bg-white dark:bg-zinc-800
                                   px-4 py-3 pl-11 text-sm
                                   text-zinc-700 dark:text-zinc-100
                                   placeholder:text-zinc-400
                                   focus:border-blue-500 focus:ring-4
                                   focus:ring-blue-500/10 outline-none transition">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>

                    </div>

                </div>

            </div>

            {{-- USERS --}}
            <div class="flex-1 overflow-y-auto">

                @forelse($users as $user)
                    @php
                        $active = $selectedUserId == $user->id;
                    @endphp

                    <button wire:click="selectUser({{ $user->id }})" wire:key="user-{{ $user->id }}"
                        class="w-full px-4 py-4 transition-all duration-200
                               border-b border-zinc-100 dark:border-zinc-800/70
                               hover:bg-white dark:hover:bg-zinc-900
                               {{ $active ? 'bg-white dark:bg-zinc-900' : '' }}">

                        <div class="flex items-center gap-3">

                            {{-- AVATAR --}}
                            <div class="relative shrink-0">

                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br
                                           from-blue-500 to-indigo-600
                                           flex items-center justify-center
                                           text-sm font-semibold text-white shadow">

                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>

                                {{-- ONLINE DOT --}}
                                <div
                                    class="absolute -bottom-0.5 -right-0.5
                                           w-3.5 h-3.5 rounded-full
                                           bg-emerald-500 border-2
                                           border-white dark:border-zinc-900">
                                </div>

                            </div>

                            {{-- CONTENT --}}
                            <div class="flex-1 min-w-0 text-left">

                                <div class="flex items-center justify-between gap-2">

                                    <div
                                        class="font-medium truncate
                                               text-zinc-800 dark:text-zinc-100">

                                        {{ $user->name }}

                                    </div>

                                    <div class="text-[11px] text-zinc-400 shrink-0">

                                        now

                                    </div>

                                </div>

                                <div class="mt-1 flex items-center justify-between gap-2">

                                    <div
                                        class="text-xs truncate
                                               text-zinc-500 dark:text-zinc-400">

                                        Online

                                    </div>

                                    {{-- UNREAD --}}
                                    <div
                                        class="min-w-[20px] h-[20px]
                                               rounded-full bg-blue-600
                                               text-white text-[10px]
                                               font-medium flex items-center justify-center px-1">

                                        2

                                    </div>

                                </div>

                            </div>

                        </div>

                    </button>

                @empty

                    <div class="h-full flex flex-col items-center justify-center p-10">

                        <div
                            class="w-16 h-16 rounded-3xl bg-zinc-100 dark:bg-zinc-800
                                   flex items-center justify-center">

                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-zinc-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5V4H2v16h5m10 0v-4a3 3 0 00-3-3H10a3 3 0 00-3 3v4m10 0H7" />
                            </svg>

                        </div>

                        <div class="mt-5 text-sm text-zinc-500 text-center">

                            Không có admin online

                        </div>

                    </div>
                @endforelse

            </div>

        </aside>

        {{-- ========================================= --}}
        {{-- CHAT --}}
        {{-- ========================================= --}}
        <section class="flex-1 flex flex-col min-w-0">

            @if ($selectedUserId)

                {{-- HEADER --}}
                <header
                    class="h-[78px] px-6 border-b border-zinc-200 dark:border-zinc-800
                           bg-white/80 dark:bg-zinc-900/80
                           backdrop-blur-xl flex items-center justify-between">

                    <div class="flex items-center gap-4">

                        <div class="relative">

                            <div
                                class="w-12 h-12 rounded-2xl
                                       bg-gradient-to-br from-blue-500 to-indigo-600
                                       flex items-center justify-center
                                       text-white font-semibold shadow-lg">

                                {{ strtoupper(substr($selectedUser?->name ?? 'A', 0, 1)) }}

                            </div>

                            <div
                                class="absolute bottom-0 right-0
                                       w-3.5 h-3.5 rounded-full
                                       bg-emerald-500 border-2
                                       border-white dark:border-zinc-900">
                            </div>

                        </div>

                        <div>

                            <div class="font-semibold text-zinc-900 dark:text-white">

                                {{ $selectedUser?->name }}

                            </div>

                            <div class="text-xs text-emerald-500 mt-0.5">

                                Online now

                            </div>

                        </div>

                    </div>

                    {{-- ACTIONS --}}
                    <div class="flex items-center gap-2">

                        <button
                            class="w-10 h-10 rounded-xl
                                   hover:bg-zinc-100 dark:hover:bg-zinc-800
                                   flex items-center justify-center transition">

                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-zinc-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M15 10l4.553-4.553a1 1 0 00-1.414-1.414L13.586 8.586m0 0L10 12.172m3.586-3.586L10 5m3.586 3.586L17 12" />
                            </svg>

                        </button>

                    </div>

                </header>

                {{-- MESSAGES --}}
                <div id="chat-window"
                    class="flex-1 overflow-y-auto px-5 py-6 bg-gradient-to-b
                           from-zinc-50 to-white
                           dark:from-zinc-950 dark:to-zinc-900
                           space-y-5">

                    @foreach ($messages as $msg)
                        @php
                            $isMine = $msg['from_id'] == auth('admin')->id();
                        @endphp

                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">

                            <div class="max-w-[78%] lg:max-w-[65%]">

                                {{-- MESSAGE --}}
                                <div
                                    class="px-5 py-3 rounded-3xl text-sm leading-relaxed shadow-sm
                                    {{ $isMine
                                        ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-br-md'
                                        : 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-100 rounded-bl-md' }}">

                                    {{ $msg['message'] }}

                                </div>

                                {{-- META --}}
                                <div
                                    class="mt-2 px-1 flex items-center gap-2
                                           text-[11px]
                                           {{ $isMine ? 'justify-end text-zinc-400' : 'justify-start text-zinc-400' }}">

                                    <span>
                                        {{ \Carbon\Carbon::parse($msg['created_at'])->format('H:i') }}
                                    </span>

                                    @if ($isMine)
                                        <span class="text-blue-500">
                                            ✓✓
                                        </span>
                                    @endif

                                </div>

                            </div>

                        </div>
                    @endforeach

                    {{-- TYPING --}}
                    <div class="flex justify-start">

                        <div
                            class="px-4 py-3 rounded-3xl rounded-bl-md
                                   bg-white dark:bg-zinc-800
                                   border border-zinc-200 dark:border-zinc-700
                                   shadow-sm">

                            <div class="flex items-center gap-1">

                                <span class="w-2 h-2 rounded-full bg-zinc-400 animate-bounce"></span>

                                <span
                                    class="w-2 h-2 rounded-full bg-zinc-400 animate-bounce [animation-delay:0.15s]"></span>

                                <span
                                    class="w-2 h-2 rounded-full bg-zinc-400 animate-bounce [animation-delay:0.3s]"></span>

                            </div>

                        </div>

                    </div>

                </div>

                {{-- INPUT --}}
                <div
                    class="border-t border-zinc-200 dark:border-zinc-800
                           bg-white/90 dark:bg-zinc-900/90
                           backdrop-blur-xl p-5">

                    <form wire:submit.prevent="send" class="flex items-end gap-3">

                        {{-- INPUT --}}
                        <div class="flex-1 relative">

                            <input type="text" wire:model="message" placeholder="Nhập tin nhắn..."
                                class="w-full rounded-2xl border border-zinc-200
                                       dark:border-zinc-700
                                       bg-zinc-50 dark:bg-zinc-800
                                       px-5 py-4 pr-14
                                       text-sm
                                       text-zinc-800 dark:text-zinc-100
                                       placeholder:text-zinc-400
                                       outline-none transition
                                       focus:border-blue-500
                                       focus:ring-4 focus:ring-blue-500/10">

                            <button type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2
                                       w-9 h-9 rounded-xl
                                       hover:bg-zinc-200 dark:hover:bg-zinc-700
                                       flex items-center justify-center transition">

                                😊

                            </button>

                        </div>

                        {{-- SEND --}}
                        <button type="submit"
                            class="h-[56px] px-6 rounded-2xl
                                   bg-gradient-to-r from-blue-600 to-indigo-600
                                   hover:from-blue-500 hover:to-indigo-500
                                   text-white font-medium shadow-lg shadow-blue-500/20
                                   transition-all duration-200 active:scale-95">

                            Gửi

                        </button>

                    </form>

                </div>
            @else
                {{-- EMPTY --}}
                <div
                    class="flex-1 flex items-center justify-center
                           bg-gradient-to-b
                           from-zinc-50 to-white
                           dark:from-zinc-950 dark:to-zinc-900">

                    <div class="text-center px-6">

                        <div
                            class="w-24 h-24 rounded-[30px]
                                   bg-gradient-to-br
                                   from-blue-500 to-indigo-600
                                   mx-auto flex items-center justify-center
                                   shadow-2xl shadow-blue-500/20">

                            <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4-.8L3 20l1.2-3.2A7.965 7.965 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>

                        </div>

                        <h3
                            class="mt-6 text-2xl font-semibold
                                   text-zinc-800 dark:text-white">

                            Internal Chat

                        </h3>

                        <p class="mt-2 text-sm text-zinc-500 max-w-sm">

                            Chọn một admin bên trái để bắt đầu cuộc trò chuyện realtime.

                        </p>

                    </div>

                </div>

            @endif

        </section>

    </div>

</div>
@push('scripts')
    <script>
        document.addEventListener(
            'livewire:init',
            () => {

                /**
                 * =====================================
                 * PREVENT DUPLICATE
                 * =====================================
                 */
                if (
                    window.internalChatInitialized
                ) {
                    return;
                }

                window.internalChatInitialized =
                    true;

                /**
                 * =====================================
                 * AUTH ID
                 * =====================================
                 */
                const authId =
                    @json(auth('admin')->id());

                /**
                 * =====================================
                 * ADMIN ONLINE
                 * =====================================
                 */
                window.socket.emit(
                    'admin-online', {
                        user_id: authId,
                    }
                );

                /**
                 * =====================================
                 * ONLINE ADMINS
                 * =====================================
                 */
                window.socket.off(
                    'online-admins'
                );

                window.socket.on(
                    'online-admins',
                    (users) => {

                        Livewire.dispatch(
                            'setOnlineUsers', {
                                users: users,
                            }
                        );
                    }
                );

                /**
                 * =====================================
                 * JOIN ROOM
                 * =====================================
                 */
                Livewire.on(
                    'join-room',
                    (event) => {

                        window.socket.emit(
                            'join-dm-room', {
                                room: String(
                                    event.room
                                ).trim(),
                            }
                        );
                    }
                );

                /**
                 * =====================================
                 * REALTIME MESSAGE
                 * =====================================
                 */
                window.socket.off(
                    'InternalMessageSent'
                );

                window.socket.on(
                    'InternalMessageSent',
                    (message) => {

                        console.log(
                            '📨 REALTIME:',
                            message
                        );

                        Livewire.dispatch(
                            'appendMessage', {
                                message: message
                            }
                        );
                    }
                );

                console.log(
                    '✅ INTERNAL CHAT READY'
                );
            }
        );
    </script>
@endpush

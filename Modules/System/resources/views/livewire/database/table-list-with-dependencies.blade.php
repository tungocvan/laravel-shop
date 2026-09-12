<div class="space-y-4">
    @if ($moduleFilter !== '' && $moduleFilter !== 'Unknown')
        <div wire:key="module-backup-scope-{{ $moduleFilter }}" x-data="{ backupScopeOpen: true }">
            <div x-show="backupScopeOpen" x-cloak
                x-transition.opacity
                @keydown.escape.window="backupScopeOpen = false"
                class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm"
                role="dialog" aria-modal="true" aria-labelledby="module-backup-scope-title">
                <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="backupScopeOpen = false">
                    <div class="border-b border-gray-100 bg-indigo-50 px-5 py-4 sm:px-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Module Snapshot</p>
                                <h2 id="module-backup-scope-title" class="mt-1 text-lg font-bold text-gray-950">
                                    Xác nhận phạm vi Backup Module {{ $moduleFilter }}
                                </h2>
                            </div>
                            <button type="button" @click="backupScopeOpen = false"
                                class="rounded-lg p-2 text-gray-400 hover:bg-white hover:text-gray-700"
                                aria-label="Đóng thông báo">✕</button>
                        </div>
                    </div>

                    <div class="space-y-4 p-5 sm:p-6">
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 text-sm leading-6 text-indigo-950">
                            Snapshot sẽ backup <strong>toàn bộ bảng thuộc ownership của Module {{ $moduleFilter }}</strong>.
                            Bộ lọc tìm kiếm và checkbox bảng đang hiển thị không làm thay đổi phạm vi Module Snapshot.
                        </div>

                        @if ($moduleDependencies !== [])
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <p class="text-sm font-bold text-amber-900">DEPENDENCY WARNING</p>
                                <p class="mt-1 text-sm leading-6 text-amber-800">
                                    {{ $moduleFilter }} phụ thuộc: <strong>{{ implode(' · ', $moduleDependencies) }}</strong>.
                                    Các Module này không được tự động backup hoặc restore cùng snapshot.
                                </p>
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                                Module Registry hiện không khai báo Module phụ thuộc cho {{ $moduleFilter }}.
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Backup Module</p>
                                <p class="mt-1 text-sm text-gray-700">Tạo snapshot an toàn tại kho Local của Module.</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Backup & Upload Drive</p>
                                <p class="mt-1 text-sm text-gray-700">
                                    @if ($moduleDriveConnected)
                                        Tạo bản Local rồi đồng bộ lên Google Drive.
                                    @else
                                        Google Drive hiện chưa kết nối; chỉ Backup Local khả dụng.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <p class="text-xs leading-5 text-gray-500">
                            Đây là bước thông báo phạm vi. Sau khi đóng modal, chọn hành động Backup phù hợp ở khu vực Module Snapshot.
                        </p>
                    </div>

                    <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-5 py-4 sm:px-6">
                        <button type="button" @click="backupScopeOpen = false"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-500">
                            Đã hiểu, tiếp tục
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($moduleFilter !== '' && $moduleFilter !== 'Unknown' && $moduleDependencies !== [])
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-4 shadow-sm sm:p-5" role="alert">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-600 px-2.5 py-1 text-xs font-bold tracking-wide text-white">DEPENDENCY WARNING</span>
                        <h2 class="text-sm font-bold text-amber-950">{{ $moduleFilter }} có Module phụ thuộc</h2>
                    </div>

                    <p class="mt-2 text-sm leading-6 text-amber-900">
                        <span class="font-semibold">{{ $moduleFilter }} phụ thuộc:</span>
                        {{ implode(' · ', $moduleDependencies) }}
                    </p>

                    <p class="mt-1 max-w-4xl text-sm leading-6 text-amber-800">
                        Snapshot này chỉ backup các bảng thuộc ownership của Module {{ $moduleFilter }}.
                        Các Module phụ thuộc không được tự động backup hoặc restore cùng snapshot này.
                        Nên tạo snapshot riêng cho các Module phụ thuộc trước các thao tác phục hồi quan trọng.
                    </p>
                </div>

                <div class="shrink-0 rounded-xl border border-amber-200 bg-white/80 px-3 py-2 text-xs font-semibold text-amber-800">
                    Metadata dependency được lưu trong manifest.json
                </div>
            </div>
        </section>
    @endif

    @include('System::livewire.database.table-list')
</div>

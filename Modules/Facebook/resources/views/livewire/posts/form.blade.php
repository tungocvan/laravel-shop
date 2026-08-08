<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <form class="space-y-6" wire:submit.prevent="saveDraft">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-gray-700">Fanpage</label>
                <select wire:model.live="state.facebook_page_id" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Chọn Fanpage</option>
                    @foreach ($pageOptions as $page)
                        <option value="{{ $page['id'] }}">{{ $page['name'] }}</option>
                    @endforeach
                </select>
                @error('state.facebook_page_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Loại bài</label>
                <select wire:model.live="state.post_type" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700">Tiêu đề nội bộ</label>
                <input type="text" wire:model.live="state.title" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Dùng để quản trị, không gửi lên Facebook">
                @error('state.title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700">Nội dung</label>
                <textarea rows="8" wire:model.live="state.message" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Nhập nội dung bài đăng"></textarea>
                @error('state.message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            @if ($state['post_type'] === 'link')
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Link</label>
                    <input type="url" wire:model.live="state.link_url" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="https://...">
                    @error('state.link_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
            @if ($state['post_type'] === 'photo')
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Ảnh</label>
                    <input type="file" wire:model.live="image" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
            <div>
                <label class="text-sm font-medium text-gray-700">Lịch đăng</label>
                <input type="datetime-local" wire:model.live="scheduledAt" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('scheduledAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('admin.facebook.posts.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">Quay lại</a>
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                <span wire:loading.remove wire:target="saveDraft">Lưu nháp</span>
                <span wire:loading wire:target="saveDraft">Đang lưu...</span>
            </button>
            <button type="button" wire:click="schedulePost" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">Lên lịch</button>
            <button type="button" wire:click="publishNow" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Đăng ngay</button>
        </div>
    </form>
</div>

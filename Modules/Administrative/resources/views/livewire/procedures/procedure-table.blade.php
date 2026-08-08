<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold tracking-tight text-gray-900">Thủ tục hành chính</h1><p class="mt-1 text-sm text-gray-500">Quản lý hướng dẫn, biểu mẫu và cấu hình hồ sơ.</p></div>
        @if(auth('admin')->user()?->can('administrative.procedure.create'))
            <a href="{{ route('admin.administrative.procedures.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Thêm thủ tục</a>
        @endif
    </div>

    @if (session('success')) <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div> @endif
    @error('archive') <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div> @enderror

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-4 border-b border-gray-200 p-4 md:grid-cols-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Tìm mã, tên hoặc slug..." class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            <select wire:model.live="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Tất cả trạng thái</option><option value="active">Đang hoạt động</option><option value="inactive">Ngừng hoạt động</option></select>
            <select wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@foreach($perPageOptions as $option)<option value="{{ $option }}">Hiển thị {{ $option }}</option>@endforeach</select>
        </div>

        <div wire:loading.delay class="w-full px-4 py-3 text-sm text-indigo-600">Đang tải dữ liệu...</div>
        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-semibold text-gray-700">Mã / Thủ tục</th><th class="px-4 py-3 text-left font-semibold text-gray-700">Cấu hình file</th><th class="px-4 py-3 text-left font-semibold text-gray-700">Hồ sơ</th><th class="px-4 py-3 text-left font-semibold text-gray-700">Trạng thái</th><th class="px-4 py-3 text-right font-semibold text-gray-700">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($procedures as $procedure)
                    <tr><td class="px-4 py-4"><div class="font-semibold text-gray-900">{{ $procedure->name }}</div><div class="text-xs text-gray-500">{{ $procedure->code }} · {{ $procedure->slug }}</div></td><td class="px-4 py-4 text-gray-600">{{ $procedure->max_files }} file · {{ number_format($procedure->max_file_size_kb / 1024, 0) }} MB</td><td class="px-4 py-4 text-gray-700">{{ $procedure->submissions_count }}</td><td class="px-4 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $procedure->is_active ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-gray-100 text-gray-600' }}">{{ $procedure->is_active ? 'Hoạt động' : 'Tạm ngưng' }}</span></td>
                    <td class="px-4 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.administrative.procedures.edit', $procedure->id) }}" class="rounded-lg border border-gray-300 px-3 py-2 font-medium text-gray-700 hover:bg-gray-50">Sửa</a><button wire:click="setActive({{ $procedure->id }}, {{ $procedure->is_active ? 'false' : 'true' }})" class="rounded-lg border border-gray-300 px-3 py-2 font-medium text-gray-700">{{ $procedure->is_active ? 'Tạm ngưng' : 'Kích hoạt' }}</button><button wire:click="requestArchive({{ $procedure->id }})" class="rounded-lg px-3 py-2 font-medium text-red-600 hover:bg-red-50">Lưu trữ</button></div></td></tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center"><h3 class="font-semibold text-gray-900">Chưa có thủ tục</h3><p class="mt-1 text-sm text-gray-500">Hãy thêm thủ tục đầu tiên để bắt đầu.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($perPage !== 'All' && $procedures->hasPages()) <div class="border-t border-gray-200 px-4 py-4">{{ $procedures->links() }}</div> @endif
    </div>

    @if($pendingArchiveId)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5"><p class="font-semibold text-red-800">Xác nhận lưu trữ thủ tục chưa có hồ sơ?</p><div class="mt-4 flex gap-3"><button wire:click="archive" wire:loading.attr="disabled" class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white">Xác nhận</button><button wire:click="$set('pendingArchiveId', null)" class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700">Hủy</button></div></div>
    @endif
</div>

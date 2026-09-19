@extends('Admin::layouts.master')

@section('title', 'HSSP thuốc')

@section('content')
<div class="container-fluid space-y-5" x-data="{ deleteOpen: false, deleteUrl: '', deleteName: '', deleting: false }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Hồ sơ sản phẩm</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">HSSP thuốc</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Chỉ quản lý hồ sơ sản phẩm đã gắn với một thuốc trong Medicine Master. Thuốc chưa có HSSP vẫn tồn tại và được các module khác tham chiếu bình thường.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pharma.medicines.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Danh mục thuốc chuẩn</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="GET" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <label class="block text-sm font-medium text-slate-700">Tìm kiếm</label>
                <input name="search" value="{{ $search }}" type="search" placeholder="Tên thuốc, mã thuốc, GPLH..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-slate-700">Trạng thái HSSP</label>
                <select name="status" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Mỗi trang</label>
                <select name="per_page" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    @foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-end lg:col-span-1">
                <button class="min-h-11 w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lọc</button>
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-950">Hồ sơ hiện hành</h2>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($profiles->total()) }} HSSP hiện hành</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">GPLH</th><th class="px-4 py-3">Phiên bản</th><th class="px-4 py-3">Nguồn</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Thao tác</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($profiles as $profile)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-4 py-4"><div class="font-semibold text-slate-950">{{ $profile->medicine->name }}</div><div class="mt-1 font-mono text-xs text-slate-500">{{ $profile->medicine->medicine_code ?: 'Chưa có mã' }}</div></td>
                        <td class="px-4 py-4 font-mono text-xs">{{ $profile->medicine->registration_number ?: '—' }}</td>
                        <td class="px-4 py-4">v{{ $profile->profile_version }}</td>
                        <td class="px-4 py-4">{{ $profile->source ?: '—' }}</td>
                        <td class="px-4 py-4 text-xs text-slate-600">{{ $profile->effective_from?->format('d/m/Y') ?: '—' }} → {{ $profile->effective_to?->format('d/m/Y') ?: '—' }}</td>
                        <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $statusOptions[$profile->profile_status] ?? $profile->profile_status }}</span></td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.pharma.hssp.edit', [$profile->medicine_id, $profile->id]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cập nhật HSSP</a>
                                <button type="button" @click="deleteUrl = @js(route('admin.pharma.hssp.destroy', [$profile->medicine_id, $profile->id])); deleteName = @js($profile->medicine->name); deleteOpen = true" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Xóa HSSP</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Chưa có HSSP phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($profiles->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $profiles->links() }}</div>@endif
    </section>

    <div x-cloak x-show="deleteOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="if (!deleting) deleteOpen = false">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" @click.outside="if (!deleting) deleteOpen = false">
            <div class="flex items-start gap-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">!</div>
                <div>
                    <h3 class="text-lg font-bold text-slate-950">Xóa toàn bộ HSSP?</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Hồ sơ của <strong x-text="deleteName"></strong> sẽ được xóa khỏi dữ liệu HSSP. File Local và file Google Drive do hồ sơ quản lý cũng được dọn bằng queue <strong>pharma</strong>. Thuốc trong Medicine Master không bị xóa.</p>
                    <p class="mt-2 text-xs font-medium text-amber-700">Nếu Google Drive tạm thời lỗi, job sẽ retry và dữ liệu hồ sơ được giữ để không mất dấu file cần dọn.</p>
                </div>
            </div>
            <form method="POST" :action="deleteUrl" class="mt-6 flex justify-end gap-3" @submit="deleting = true">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteOpen = false" :disabled="deleting" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">Hủy</button>
                <button type="submit" :disabled="deleting" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"><span x-text="deleting ? 'Đang đưa vào queue...' : 'Xóa HSSP'"></span></button>
            </form>
        </div>
    </div>
</div>
@endsection

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 class="text-2xl font-bold text-gray-900">Hồ sơ hành chính</h1><p class="mt-1 text-sm text-gray-500">Theo dõi và xử lý hồ sơ phụ huynh đã nộp.</p></div><a href="{{ route('admin.administrative.procedures.index') }}" class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700">Danh mục thủ tục</a></div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">@foreach([''=>'Tổng hồ sơ','pending'=>'Chờ duyệt','need_supplement'=>'Chờ bổ sung','approved'=>'Đã duyệt','rejected'=>'Bị từ chối'] as $key=>$label)<button wire:click="setStatus('{{ $key }}')" class="rounded-2xl border bg-white p-5 text-left shadow-sm {{ $status === $key ? 'border-indigo-500 ring-2 ring-indigo-100' : 'border-gray-200' }}"><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold text-gray-900">{{ $key === '' ? $stats['total'] : $stats[$key] }}</div></button>@endforeach</div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-4 border-b border-gray-200 p-4 md:grid-cols-2 lg:grid-cols-7">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Mã, người nộp, học sinh..." class="rounded-xl border border-gray-300 px-4 py-3 text-sm lg:col-span-2">
            <select wire:model.live="status" class="rounded-xl border border-gray-300 px-4 py-3 text-sm"><option value="">Tất cả trạng thái</option><option value="pending">Chờ duyệt</option><option value="need_supplement">Yêu cầu bổ sung</option><option value="approved">Đã duyệt</option><option value="rejected">Bị từ chối</option></select>
            <select wire:model.live="procedure_id" class="rounded-xl border border-gray-300 px-4 py-3 text-sm"><option value="">Tất cả thủ tục</option>@foreach($procedures as $procedure)<option value="{{ $procedure->id }}">{{ $procedure->code }} - {{ $procedure->name }}</option>@endforeach</select>
            <input wire:model.live="date_from" type="date" class="rounded-xl border border-gray-300 px-4 py-3 text-sm"><input wire:model.live="date_to" type="date" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
            <select wire:model.live="perPage" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">@foreach($perPageOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>
            <button wire:click="resetFilters" class="text-sm font-semibold text-indigo-600 lg:col-span-7 lg:justify-self-end">Xóa bộ lọc</button>
        </div>

        @if(auth('admin')->user()?->can('administrative.submission.delete'))
            <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><span class="text-sm text-gray-600">Đã chọn: <strong>{{ count($selectedIds) }}</strong> hồ sơ</span><button wire:click="requestDelete" @disabled($selectedIds === []) class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Xóa hồ sơ đã chọn</button></div>
        @endif

        <div wire:loading.delay class="px-4 py-3 text-sm text-indigo-600">Đang tải dữ liệu...</div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr>
            @if(auth('admin')->user()?->can('administrative.submission.delete'))<th class="w-12 px-4 py-3"><input type="checkbox" wire:click="toggleSelectPage({{ Illuminate\Support\Js::from($submissions->pluck('id')->values()) }})" @checked($selectAll) aria-label="Chọn tất cả hồ sơ trên trang"></th>@endif
            @foreach(['Mã hồ sơ','Thủ tục','Người nộp / Học sinh','Ngày nộp','Trạng thái','Xử lý',''] as $heading)<th class="px-4 py-3 text-left font-semibold text-gray-700">{{ $heading }}</th>@endforeach
        </tr></thead><tbody class="divide-y divide-gray-200 bg-white">
        @forelse($submissions as $item)@php($s=$item->status->value)<tr>
            @if(auth('admin')->user()?->can('administrative.submission.delete'))<td class="px-4 py-4"><input type="checkbox" wire:model.live="selectedIds" value="{{ $item->id }}" aria-label="Chọn hồ sơ {{ $item->submission_code }}"></td>@endif
            <td class="whitespace-nowrap px-4 py-4 font-semibold text-indigo-700">{{ $item->submission_code }}</td><td class="max-w-xs px-4 py-4"><div class="font-medium text-gray-900">{{ $item->procedure->name }}</div><div class="text-xs text-gray-500">{{ $item->procedure->code }}</div></td><td class="px-4 py-4"><div>{{ $item->applicant_name }}</div><div class="text-xs text-gray-500">HS: {{ $item->student_name }} · {{ $item->phone }}</div></td><td class="whitespace-nowrap px-4 py-4 text-gray-600">{{ $item->submitted_at->format('d/m/Y H:i') }}</td><td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium {{ $s === 'pending' ? 'bg-amber-50 text-amber-700' : ($s === 'approved' ? 'bg-green-50 text-green-700' : ($s === 'need_supplement' ? 'bg-orange-50 text-orange-700' : 'bg-red-50 text-red-700')) }}">{{ ['pending'=>'Chờ duyệt','need_supplement'=>'Chờ bổ sung','approved'=>'Đã duyệt','rejected'=>'Bị từ chối'][$s] }}</span></td><td class="px-4 py-4 text-gray-600">{{ $item->processor?->name ?? '-' }}</td><td class="px-4 py-4 text-right"><a href="{{ route('admin.administrative.submissions.show', $item->id) }}" class="font-semibold text-indigo-600">Chi tiết</a></td>
        </tr>@empty<tr><td colspan="{{ auth('admin')->user()?->can('administrative.submission.delete') ? 8 : 7 }}" class="p-8 text-center"><h3 class="font-semibold text-gray-900">Không có hồ sơ</h3><p class="mt-1 text-sm text-gray-500">Thử thay đổi bộ lọc tìm kiếm.</p></td></tr>@endforelse
        </tbody></table></div>
        @if($perPage !== 'All' && $submissions->hasPages())<div class="border-t border-gray-200 px-4 py-4">{{ $submissions->links() }}</div>@endif
    </div>

    @if($confirmingDelete)<div class="rounded-2xl border border-red-200 bg-red-50 p-5"><h2 class="font-semibold text-red-900">Xác nhận xóa {{ count($selectedIds) }} hồ sơ?</h2><p class="mt-1 text-sm text-red-700">Hồ sơ sẽ được đưa vào lưu trữ bằng soft delete; file và lịch sử vẫn được giữ lại.</p><div class="mt-4 flex gap-3"><button wire:click="deleteSelected" wire:loading.attr="disabled" class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white">Xác nhận xóa</button><button wire:click="$set('confirmingDelete', false)" class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700">Hủy</button></div></div>@endif
</div>

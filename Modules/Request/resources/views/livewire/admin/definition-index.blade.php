<div class="space-y-6">
    @if(session('request_success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>
    @endif

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Quản lý định nghĩa</div>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Quản lý loại đề nghị</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-600">Tạo nhóm và loại đề nghị, theo dõi trạng thái bản nháp/phát hành và mở đúng công cụ để chỉnh sửa hoặc xem lịch sử phiên bản.</p>
    </header>

    <section aria-labelledby="definition-create-heading" class="space-y-3">
        <div><h2 id="definition-create-heading" class="text-lg font-bold text-slate-900">Khởi tạo định nghĩa</h2><p class="text-sm text-slate-600">Tạo nhóm trước, sau đó tạo loại đề nghị thuộc nhóm phù hợp.</p></div>
        <div class="grid gap-6 lg:grid-cols-2">
            <form wire:submit="createGroup" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">Tạo nhóm đề nghị</h3>
                <p class="mt-1 text-xs text-slate-500">Nhóm dùng để tổ chức các loại đề nghị theo nghiệp vụ.</p>
                <label class="mt-4 block text-sm font-medium text-slate-700">{{ __('Request::request.code') }}<input wire:model="groupCode" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                @error('groupCode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <label class="mt-4 block text-sm font-medium text-slate-700">{{ __('Request::request.name') }}<input wire:model="groupName" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                @error('groupName')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="mt-4 min-h-11 rounded-xl border border-indigo-300 bg-white px-4 py-2 font-semibold text-indigo-700" wire:loading.attr="disabled">Tạo nhóm đề nghị</button>
            </form>

            <form wire:submit="createType" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">Tạo loại đề nghị</h3>
                <p class="mt-1 text-xs text-slate-500">Loại mới bắt đầu ở trạng thái bản nháp và được hoàn thiện trong Designer.</p>
                <label class="mt-4 block text-sm font-medium text-slate-700">{{ __('Request::request.groups.title') }}<select wire:model="requestGroupId" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="">— Chọn nhóm —</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                @error('requestGroupId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <label class="mt-4 block text-sm font-medium text-slate-700">{{ __('Request::request.code') }}<input wire:model="typeCode" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                @error('typeCode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <label class="mt-4 block text-sm font-medium text-slate-700">{{ __('Request::request.name') }}<input wire:model="typeName" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
                @error('typeName')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="mt-4 min-h-11 rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white" wire:loading.attr="disabled">Tạo loại đề nghị</button>
            </form>
        </div>
    </section>

    @if($duplicateSourcePublicId)
        <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5" aria-labelledby="duplicate-type-heading">
            <h2 id="duplicate-type-heading" class="text-lg font-bold text-slate-900">Nhân bản thành loại đề nghị mới</h2>
            <p class="mt-1 text-sm text-slate-600">Bản sao luôn bắt đầu ở v1 bản nháp và không tự phát hành.</p>
            @if($errors->hasAny(['duplicateGroupId', 'duplicateCode', 'duplicateName', 'duplicateAudience', 'duplicateType']))
                <div class="mt-3 rounded-xl border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800" role="alert">
                    Không thể tạo bản sao. Vui lòng kiểm tra các trường được đánh dấu bên dưới.
                </div>
            @endif
            <form wire:submit="duplicateType" class="mt-4 grid gap-3 md:grid-cols-2">
                <label class="text-sm font-medium">Nhóm<select wire:model="duplicateGroupId" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>@error('duplicateGroupId')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
                <label class="text-sm font-medium">Mã mới<input wire:model="duplicateCode" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">@error('duplicateCode')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
                <label class="text-sm font-medium md:col-span-2">Tên mới<input wire:model="duplicateName" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">@error('duplicateName')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
                @if(auth('admin')->user()?->can('request.type.audience.manage'))<label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" wire:model="duplicateAudience" class="rounded border-slate-300"> Sao chép đối tượng được phép tạo đề nghị</label>@endif
                <div class="flex gap-2 md:col-span-2"><button type="submit" wire:loading.attr="disabled" wire:target="duplicateType" class="min-h-11 rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white disabled:opacity-60"><span wire:loading.remove wire:target="duplicateType">Tạo bản sao</span><span wire:loading wire:target="duplicateType">Đang tạo…</span></button><button type="button" wire:click="$set('duplicateSourcePublicId', null)" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 font-semibold">Hủy</button></div>
            </form>
        </section>
    @endif

    <section aria-labelledby="definition-filter-heading" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="definition-filter-heading" class="text-lg font-bold text-slate-900">Tìm kiếm & lọc</h2><p class="text-sm text-slate-600">Tìm theo mã hoặc tên và thu hẹp theo trạng thái vận hành.</p></div><button type="button" wire:click="resetFilters" class="min-h-11 rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Đặt lại</button></div>
        <div class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem]">
            <label class="text-sm font-medium text-slate-700">{{ __('Request::request.search') }}<input wire:model.live.debounce.300ms="search" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3" placeholder="Mã hoặc tên loại đề nghị"></label>
            <label class="text-sm font-medium text-slate-700">Trạng thái<select wire:model.live="status" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="">Tất cả trạng thái</option><option value="draft">Bản nháp</option><option value="published">Đang phát hành</option><option value="retired">Ngừng sử dụng</option></select></label>
        </div>
    </section>

    <section aria-labelledby="definition-list-heading" class="space-y-3">
        <div><h2 id="definition-list-heading" class="text-lg font-bold text-slate-900">Danh sách loại đề nghị</h2><p class="text-sm text-slate-600">Mỗi loại hiển thị trạng thái và phiên bản để bạn chọn đúng hành động quản trị.</p></div>
        <div class="grid gap-3">
            @forelse($types as $type)
                @php($statusValue = $type->status->value)
                @php($statusMeta = match($statusValue) {'draft' => ['Bản nháp', 'border-amber-200 bg-amber-50 text-amber-800'], 'published' => ['Đang phát hành', 'border-emerald-200 bg-emerald-50 text-emerald-800'], 'retired' => ['Ngừng sử dụng', 'border-slate-200 bg-slate-100 text-slate-700'], default => [$statusValue, 'border-slate-200 bg-slate-50 text-slate-700']})
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs text-slate-500">{{ $type->code }}</span><span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span></div>
                            <h3 class="mt-2 text-base font-bold text-slate-900">{{ $type->name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">Nhóm: {{ $type->group?->name ?? '—' }}</p>
                            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs font-medium text-slate-500">Bản nháp hiện tại</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $type->activeDraft ? 'v'.$type->activeDraft->version_number : 'Chưa có' }}</dd></div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs font-medium text-slate-500">Phiên bản hiện hành</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $type->currentPublishedVersion ? 'v'.$type->currentPublishedVersion->version_number : 'Chưa phát hành' }}</dd></div>
                            </dl>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row lg:flex-col lg:min-w-44">
                            <a class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('request.admin.types.designer', $type->public_id) }}">Mở Designer</a>
                            <a class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" href="{{ route('request.admin.types.versions', $type->public_id) }}">Lịch sử phiên bản</a>
                            @can('create', Modules\Request\Models\RequestType::class)<button type="button" wire:click="prepareDuplicate('{{ $type->public_id }}')" class="min-h-11 rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700">Nhân bản</button>@endcan
                            @can('delete', $type)<button type="button" wire:click="deleteType('{{ $type->public_id }}')" wire:confirm="Xóa vĩnh viễn loại đề nghị chưa phát hành này?" class="min-h-11 rounded-xl border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700">Xóa loại</button>@endcan
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><div class="font-semibold text-slate-800">Không có loại đề nghị phù hợp.</div><p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa hoặc trạng thái lọc.</p></div>
            @endforelse
        </div>
        <div>{{ $types->links() }}</div>
    </section>
</div>

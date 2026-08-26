<div class="space-y-5" aria-labelledby="request-designer-title">
    @php($fieldCount = collect($sections)->sum(fn ($section) => count((array) ($section['fields'] ?? []))))
    @php($hasMetadata = trim((string) $title) !== '')
    @php($hasForm = count($sections) > 0 && $fieldCount > 0)
    @php($hasApproval = $approvalReady)
    @php($hasAudience = $audienceReady)
    @php($readyCount = collect([$hasMetadata, $hasForm, $hasApproval, $hasAudience])->filter()->count())

    @if(session('request_success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>
    @endif

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Trình thiết kế quản trị</div>
                <h1 id="request-designer-title" class="mt-1 text-2xl font-bold text-slate-900">Thiết kế loại đề nghị</h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">Cấu hình bản nháp trước, kiểm tra biểu mẫu và luồng phê duyệt, sau đó mới phát hành phiên bản bất biến cho runtime.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-800">Bản nháp đang chỉnh sửa</span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-slate-700">Schema v{{ $schemaVersion }}</span>
            </div>
        </div>
    </header>

    <div class="grid gap-5 xl:grid-cols-[14rem_minmax(0,1fr)_19rem]">
        <nav class="rounded-xl border border-slate-200 bg-white p-3 xl:sticky xl:top-4 xl:self-start" aria-label="Các phần của trình thiết kế">
            <div class="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Cấu hình</div>
            <a href="#request-designer-metadata" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Thông tin chung</a>
            <a href="#request-designer-form" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Biểu mẫu</a>
            <a href="#request-designer-approval" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Phê duyệt & SLA</a>
            <a href="#request-designer-audience" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Đối tượng</a>
        </nav>

        <main class="min-w-0 space-y-5">
            <section id="request-designer-metadata" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-metadata-title">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="request-metadata-title" class="text-lg font-semibold text-slate-900">Thông tin chung</h2><p class="mt-1 text-sm text-slate-600">Tên và hướng dẫn mà người gửi sẽ nhìn thấy khi tạo đề nghị.</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $hasMetadata ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $hasMetadata ? 'Đã cấu hình' : 'Cần hoàn thiện' }}</span></div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">Tiêu đề<input wire:model="title" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                    <label class="block text-sm font-medium text-slate-700">Mô tả<textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></textarea></label>
                    <label class="block text-sm font-medium text-slate-700">Hướng dẫn người gửi<textarea wire:model="requesterGuidance" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></textarea></label>
                </div>
            </section>

            <section id="request-designer-form" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-form-title">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="request-form-title" class="text-lg font-semibold text-slate-900">Thiết kế biểu mẫu</h2><p class="text-sm text-slate-600">Mã phần và mã trường được giữ ổn định. Dùng các nút di chuyển để sắp xếp.</p><p class="mt-1 text-xs leading-5 text-amber-700">Thay đổi “Bắt buộc” chỉ có hiệu lực sau khi lưu, phát hành phiên bản mới và tạo đề nghị mới; đề nghị đã tạo luôn giữ nguyên phiên bản cũ.</p></div><div class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ count($sections) }} phần · {{ $fieldCount }} trường</span><button type="button" wire:click="addSection" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700">Thêm phần</button></div></div>
                <div class="mt-4 space-y-4">
                    @forelse($sections as $sectionIndex => $section)
                        <article wire:key="section-{{ $sectionIndex }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start gap-3"><div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2"><label class="text-sm font-medium text-slate-700">Mã phần<input wire:model="sections.{{ $sectionIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm"></label><label class="text-sm font-medium text-slate-700">Nhãn hiển thị<input wire:model="sections.{{ $sectionIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label></div><div class="flex gap-1"><button type="button" wire:click="moveSection({{ $sectionIndex }}, -1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white">↑</button><button type="button" wire:click="moveSection({{ $sectionIndex }}, 1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white">↓</button><button type="button" wire:click="removeSection({{ $sectionIndex }})" wire:confirm="Xóa phần này và toàn bộ trường bên trong?" class="min-h-11 rounded-lg border border-red-200 bg-white px-3 text-sm text-red-700">Xóa</button></div></div>
                            <div class="mt-4 space-y-3">
                                @foreach((array)($section['fields'] ?? []) as $fieldIndex => $field)
                                    <div wire:key="field-{{ $sectionIndex }}-{{ $fieldIndex }}" class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                            <label class="text-sm font-medium text-slate-700">Mã trường<input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></label>
                                            <label class="text-sm font-medium text-slate-700">Nhãn hiển thị<input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                            <label class="text-sm font-medium text-slate-700">Kiểu dữ liệu<select wire:model.live="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.type" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">@foreach(['text','textarea','integer','decimal','currency','date','datetime','boolean','select','multiselect','user','role','attachment','computed_display'] as $fieldType)<option value="{{ $fieldType }}">{{ $fieldType }}</option>@endforeach</select></label>
                                            <label class="text-sm font-medium text-slate-700">Phân loại dữ liệu<select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.classification" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="public_internal">Nội bộ công khai</option><option value="internal">Nội bộ</option><option value="confidential">Bảo mật</option></select></label>
                                            <label class="text-sm font-medium text-slate-700">Độ rộng hiển thị<select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.width" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="auto">Tự động</option><option value="full">Toàn dòng</option><option value="half">Một nửa dòng</option><option value="third">Một phần ba dòng</option></select></label>
                                            <div class="grid gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                                <label class="flex min-h-8 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.required"> Bắt buộc khi gửi</label>
                                                <label class="flex min-h-8 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.offline_draft"> Cho phép lưu cục bộ</label>
                                                @if(($field['type'] ?? null) === 'date')
                                                    <label class="flex min-h-8 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.default_today"> Mặc định ngày hôm nay</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2"><button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, -1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển lên</button><button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, 1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển xuống</button><button type="button" wire:click="removeField({{ $sectionIndex }}, {{ $fieldIndex }})" wire:confirm="Xóa trường này khỏi biểu mẫu?" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Xóa trường</button></div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" wire:click="addField({{ $sectionIndex }})" class="mt-3 min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700">Thêm trường</button>
                        </article>
                    @empty<div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">Chưa có phần biểu mẫu.</div>@endforelse
                </div>
            </section>

            <section id="request-designer-approval" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-approval-title">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="request-approval-title" class="text-lg font-semibold text-slate-900">Các cấp phê duyệt & SLA</h2><p class="text-sm text-slate-600">Mỗi cấp duyệt có thời hạn riêng. Quá hạn chỉ cảnh báo/tạm dừng theo cấu hình, không tự duyệt hoặc từ chối.</p></div><div class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ count($stages) }} cấp</span><button type="button" wire:click="addStage" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700">Thêm cấp duyệt</button></div></div>
                <div class="mt-4 space-y-4">
                    @forelse($stages as $stageIndex => $stage)
                        <article wire:key="stage-{{ $stageIndex }}" class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3"><div><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cấp {{ $stageIndex + 1 }}</div><div class="font-semibold text-slate-900">{{ $stage['name'] ?? 'Cấp phê duyệt' }}</div></div><div class="flex gap-1"><button type="button" wire:click="moveStage({{ $stageIndex }}, -1)" class="min-h-10 min-w-10 rounded-lg border border-slate-300">↑</button><button type="button" wire:click="moveStage({{ $stageIndex }}, 1)" class="min-h-10 min-w-10 rounded-lg border border-slate-300">↓</button><button type="button" wire:click="removeStage({{ $stageIndex }})" wire:confirm="Xóa cấp phê duyệt này?" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Xóa cấp</button></div></div>
                            <div class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-sm font-medium text-slate-700">Mã cấp duyệt<input wire:model.blur="stages.{{ $stageIndex }}.stage_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></label><label class="text-sm font-medium text-slate-700">Tên<input wire:model.live="stages.{{ $stageIndex }}.name" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2"></label><label class="text-sm font-medium text-slate-700">Chế độ<select wire:model.live="stages.{{ $stageIndex }}.mode" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="single">Một người duyệt</option><option value="parallel_all">Song song - tất cả</option><option value="parallel_any">Song song - bất kỳ</option></select></label><label class="text-sm font-medium text-slate-700">Bộ phân giải<select wire:model.live="stages.{{ $stageIndex }}.resolver_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="fixed_users">Người dùng cố định</option><option value="role_members">Thành viên vai trò</option><option value="form_user_field">Người dùng từ trường biểu mẫu</option></select></label>
                                @if(($stage['resolver_key'] ?? 'fixed_users') === 'fixed_users')<label class="text-sm font-medium text-slate-700 md:col-span-2">Người được phê duyệt<select wire:model="stages.{{ $stageIndex }}.resolver_user_ids" multiple size="6" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">@foreach($approverUsers as $approverUser)<option value="{{ $approverUser->id }}">{{ $approverUser->name }} · {{ $approverUser->email }}</option>@endforeach</select><span class="mt-1 block text-xs font-normal text-slate-500">Giữ Ctrl/Cmd để chọn nhiều người. Với chế độ “Một người duyệt”, chỉ chọn một tài khoản.</span></label>@error('stages.'.$stageIndex.'.resolver_user_ids')<p class="text-sm text-red-600 md:col-span-2">Vui lòng chọn ít nhất một người phê duyệt.</p>@enderror
                                @else<details class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-3"><summary class="cursor-pointer text-sm font-semibold text-slate-700">Cấu hình nâng cao · Bộ phân giải JSON</summary><label class="mt-3 block text-sm font-medium text-slate-700">JSON<textarea wire:model="stages.{{ $stageIndex }}.resolver_config_json" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm"></textarea></label></details>@error('stages.'.$stageIndex.'.resolver_config_json')<p class="text-sm text-red-600 md:col-span-2">{{ $message }}</p>@enderror @endif
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Hướng dẫn<textarea wire:model="stages.{{ $stageIndex }}.instructions" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea></label><label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="stages.{{ $stageIndex }}.allow_reassignment" class="h-4 w-4 rounded border-slate-300"> Cho phép giao lại</label>
                            </div>
                            <fieldset class="mt-5 min-w-0 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                                <legend class="px-2 text-sm font-semibold text-indigo-900">SLA & quá hạn</legend>
                                <p class="mb-4 text-xs leading-5 text-slate-600">Thời gian được tính trong backend theo UTC và hiển thị theo {{ config('app.timezone') }}. Thời lượng tối đa là 365 ngày.</p>

                                @php($timeoutAction = $stage['timeout_action'] ?? 'notify_only')
                                @php($warningConfigured = is_numeric($stage['warning_value'] ?? null) && (float) $stage['warning_value'] > 0)
                                <div class="grid min-w-0 gap-4 md:grid-cols-2">
                                    @foreach([
                                        ['key' => 'sla', 'label' => 'Thời hạn xử lý', 'required' => true],
                                        ['key' => 'warning', 'label' => 'Cảnh báo trước hạn', 'required' => false],
                                        ['key' => 'grace', 'label' => 'Thời gian gia hạn', 'required' => false],
                                    ] as $slaField)
                                        @php($unit = $stage[$slaField['key'].'_unit'] ?? 'hours')
                                        @php($maxValue = match ($unit) { 'days' => 365, 'hours' => 8760, default => 525600 })
                                        @php($disabled = $slaField['key'] === 'grace' && $timeoutAction !== 'suspend')
                                        <label class="min-w-0 text-sm font-medium text-slate-700">
                                            {{ $slaField['label'] }}{{ $slaField['required'] ? ' *' : '' }}
                                            <div class="mt-1 grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(5rem,auto)] gap-2">
                                                <input
                                                    wire:model.blur="stages.{{ $stageIndex }}.{{ $slaField['key'] }}_value"
                                                    type="number"
                                                    min="0"
                                                    max="{{ $maxValue }}"
                                                    step="1"
                                                    @disabled($disabled)
                                                    class="min-h-11 min-w-0 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 disabled:bg-slate-100"
                                                >
                                                <select
                                                    wire:model.live="stages.{{ $stageIndex }}.{{ $slaField['key'] }}_unit"
                                                    @disabled($disabled)
                                                    class="min-h-11 min-w-20 rounded-lg border border-slate-300 bg-white px-2 py-2 disabled:bg-slate-100"
                                                >
                                                    <option value="minutes">phút</option>
                                                    <option value="hours">giờ</option>
                                                    <option value="days">ngày</option>
                                                </select>
                                            </div>
                                            @error('stages.'.$stageIndex.'.'.$slaField['key'].'_value')
                                                <span class="mt-1 block text-xs font-medium text-red-600">
                                                    @if($slaField['key'] === 'sla') Thời hạn xử lý phải lớn hơn 0 và không vượt quá 365 ngày.
                                                    @elseif($slaField['key'] === 'warning') Cảnh báo phải từ 0 đến thời hạn xử lý.
                                                    @else Thời gian gia hạn phải từ 0 đến 365 ngày.
                                                    @endif
                                                </span>
                                            @enderror
                                            @if($slaField['key'] === 'warning')<span class="mt-1 block text-xs font-normal text-slate-500">Để trống hoặc nhập 0 nếu không cần cảnh báo trước hạn.</span>@endif
                                        </label>
                                    @endforeach
                                </div>

                                <label class="mt-4 block text-sm font-medium text-slate-700">
                                    Hành vi sau khi quá hạn
                                    <select wire:model.live="stages.{{ $stageIndex }}.timeout_action" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                        <option value="notify_only">Chỉ đánh dấu quá hạn, vẫn cho phép xử lý</option>
                                        <option value="suspend">Tạm dừng sau khi hết thời gian gia hạn</option>
                                    </select>
                                    @error('stages.'.$stageIndex.'.timeout_action')<span class="mt-1 block text-xs font-medium text-red-600">Hãy chọn một hành vi quá hạn hợp lệ.</span>@enderror
                                </label>

                                <div class="mt-4 rounded-xl border border-slate-200 bg-white/80 p-3">
                                    <div class="text-sm font-semibold text-slate-800">Thông báo email</div>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">Khi tắt email, thông báo trong ứng dụng vẫn được tạo.</p>
                                    <div class="mt-2 grid gap-2 md:grid-cols-2">
                                        <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model.live="stages.{{ $stageIndex }}.email_on_assignment" class="h-4 w-4 rounded border-slate-300"> Khi đến lượt duyệt</label>
                                        <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model.live="stages.{{ $stageIndex }}.email_on_decision" class="h-4 w-4 rounded border-slate-300"> Kết quả xử lý cho người đề nghị</label>
                                        <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700 md:col-span-2"><input type="checkbox" wire:model.live="stages.{{ $stageIndex }}.email_on_sla_warning" @disabled(! $warningConfigured) class="h-4 w-4 rounded border-slate-300 disabled:opacity-50"> Cảnh báo SLA cho người duyệt</label>
                                    </div>
                                </div>

                                @error('stages')<p class="mt-3 text-xs font-medium text-red-600">Cấu hình SLA chưa hợp lệ. Hãy kiểm tra thời hạn, cảnh báo và hành vi quá hạn.</p>@enderror
                                <p class="mt-3 text-xs font-medium text-indigo-800">SLA không tự động phê duyệt hoặc từ chối đề nghị.</p>
                            </fieldset>
                        </article>
                    @empty<div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">Chưa có cấp phê duyệt.</div>@endforelse
                </div>
            </section>

            <section id="request-designer-audience" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-audience-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="request-audience-title" class="text-lg font-semibold text-slate-900">Đối tượng được phép tạo đề nghị</h2>
                        <p class="mt-1 text-sm text-slate-600">Chọn chính xác người dùng được nhìn thấy và tạo loại đề nghị này sau khi phát hành.</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $hasAudience ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $hasAudience ? 'Đã phân quyền' : 'Chưa phân quyền' }}</span>
                </div>

                @if($canManageAudience)
                    <div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-indigo-950">Phân quyền tạo đề nghị theo người dùng</div>
                                <p class="mt-1 text-xs leading-5 text-indigo-800">Chỉ những tài khoản được chọn mới nhận loại đề nghị này trong Danh mục đề nghị.</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-800">Đã chọn {{ count($audienceUserIds) }} người</span>
                        </div>

                        <label class="mt-4 block text-sm font-medium text-slate-700">
                            Tìm người dùng
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="audienceSearch"
                                placeholder="Nhập tên hoặc email"
                                class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                            >
                        </label>

                        <div class="mt-3 grid max-h-80 gap-2 overflow-y-auto pr-1 sm:grid-cols-2" role="group" aria-label="Người dùng được phép tạo đề nghị">
                            @forelse($audienceUsers as $audienceUser)
                                <label wire:key="request-audience-user-{{ $audienceUser->id }}" class="flex min-h-14 cursor-pointer items-start gap-3 rounded-lg border p-3 {{ $audienceUser->unavailable ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white hover:border-indigo-300' }}">
                                    <input type="checkbox" value="{{ $audienceUser->id }}" wire:model.live="audienceUserIds" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $audienceUser->name }}</span>
                                        <span class="mt-0.5 block truncate text-xs {{ $audienceUser->unavailable ? 'text-amber-800' : 'text-slate-500' }}">{{ $audienceUser->unavailable ? 'Bỏ chọn để gỡ quyền cũ' : ($audienceUser->email ?? 'Không có email') }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="rounded-lg border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600 sm:col-span-2">Không tìm thấy người dùng đang hoạt động.</p>
                            @endforelse
                        </div>
                        @error('audienceUserIds')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4" role="note">
                        <div class="text-sm font-semibold text-amber-900">Bạn chỉ có quyền xem danh sách này</div>
                        <p class="mt-1 text-xs leading-5 text-amber-800">Cần quyền “Quản lý đối tượng tạo đề nghị” để thêm hoặc gỡ người dùng.</p>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @forelse($audienceUsers as $audienceUser)
                            <div wire:key="request-audience-readonly-user-{{ $audienceUser->id }}" class="rounded-lg border p-3 {{ $audienceUser->unavailable ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-slate-50' }}">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $audienceUser->name }}</div>
                                <div class="mt-0.5 truncate text-xs {{ $audienceUser->unavailable ? 'text-amber-800' : 'text-slate-500' }}">{{ $audienceUser->unavailable ? 'Cần quản trị có quyền gỡ phân quyền cũ' : ($audienceUser->email ?? 'Không có email') }}</div>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600 sm:col-span-2">Chưa có người dùng nào được phân quyền trực tiếp.</p>
                        @endforelse
                    </div>
                @endif

                @if($preservedAudienceCount > 0)
                    <p class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs leading-5 text-slate-600">Có {{ $preservedAudienceCount }} quy tắc đối tượng theo vai trò hoặc khả năng khám phá đang được giữ nguyên bởi hệ thống.</p>
                @endif
            </section>
        </main>

        <aside class="rounded-xl border border-slate-200 bg-white p-4 xl:sticky xl:top-4 xl:self-start" aria-label="Trạng thái và thao tác thiết kế">
            <h2 class="font-semibold text-slate-900">Sẵn sàng phát hành</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Hoàn thiện các phần chính trước khi phát hành phiên bản mới.</p>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-indigo-600" style="width: {{ ($readyCount / 4) * 100 }}%"></div></div>
            <div class="mt-2 text-xs font-semibold text-slate-600">{{ $readyCount }}/4 nhóm cấu hình chính</div>
            <ul class="mt-4 space-y-2 text-sm"><li class="flex items-center justify-between gap-2"><span>Thông tin chung</span><strong class="{{ $hasMetadata ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasMetadata ? 'Sẵn sàng' : 'Chưa đủ' }}</strong></li><li class="flex items-center justify-between gap-2"><span>Biểu mẫu</span><strong class="{{ $hasForm ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasForm ? 'Sẵn sàng' : 'Chưa đủ' }}</strong></li><li class="flex items-center justify-between gap-2"><span>Phê duyệt & SLA</span><strong class="{{ $hasApproval ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasApproval ? 'Sẵn sàng' : 'Chưa đủ' }}</strong></li><li class="flex items-center justify-between gap-2"><span>Đối tượng tạo đề nghị</span><strong class="{{ $hasAudience ? 'text-emerald-700' : 'text-amber-700' }}">{{ $hasAudience ? 'Sẵn sàng' : 'Chưa đủ' }}</strong></li></ul>
            <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Phiên bản khóa</dt><dd class="font-mono">{{ $lockVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Schema</dt><dd>v{{ $schemaVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Số phần</dt><dd>{{ count($sections) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Số trường</dt><dd>{{ $fieldCount }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Cấp duyệt</dt><dd>{{ count($stages) }}</dd></div></dl>
            @error('lock_version')<div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" role="alert">Bản nháp đã thay đổi trên máy chủ. Hãy tải lại và kiểm tra trước khi lưu tiếp.</div>@enderror
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3"><div class="text-sm font-semibold text-amber-900">Phát hành có tác động runtime</div><p class="mt-1 text-xs leading-5 text-amber-800">Sau khi phát hành, phiên bản trở thành bất biến và các đề nghị mới có thể sử dụng cấu hình này. Hãy lưu và kiểm tra bản nháp trước.</p></div>
            <div class="mt-4 grid gap-2"><button type="button" wire:click="save" wire:loading.attr="disabled" class="min-h-11 rounded-lg border border-indigo-300 bg-white px-4 py-2 font-medium text-indigo-700">Lưu bản nháp</button><button type="button" wire:click="publish" wire:confirm="{{ __('Request::request.publish_confirm') }}" wire:loading.attr="disabled" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">Phát hành phiên bản</button><a href="{{ route('request.admin.types.versions', $type->public_id) }}" class="flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Xem lịch sử phiên bản</a></div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Bản nháp trên máy chủ là nguồn dữ liệu chính thức. Phiên bản đã phát hành là bất biến; SLA được snapshot khi task kích hoạt.</p>
        </aside>
    </div>

    @if($showValidationModal)
        <div
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="request-validation-modal-title"
            aria-describedby="request-validation-modal-description"
            wire:click.self="closeValidationModal"
            wire:keydown.escape.window="closeValidationModal"
        >
            <div class="w-full max-w-lg rounded-2xl border border-red-200 bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-xl font-bold text-red-700" aria-hidden="true">!</div>
                    <div class="min-w-0">
                        <h2 id="request-validation-modal-title" class="text-lg font-bold text-slate-900">{{ $validationModalTitle }}</h2>
                        <p id="request-validation-modal-description" class="mt-1 text-sm leading-6 text-slate-600">
                            {{ str_contains($validationModalTitle, 'phát hành') ? 'Phiên bản chưa được phát hành.' : 'Bản nháp chưa được lưu.' }} Hãy hoàn thiện các mục sau rồi thử lại:
                        </p>
                    </div>
                </div>
                <ul class="mt-4 max-h-64 list-disc space-y-2 overflow-y-auto rounded-xl border border-red-100 bg-red-50 p-4 pl-9 text-sm leading-6 text-red-800">
                    @foreach($validationModalMessages as $message)
                        <li wire:key="request-validation-message-{{ $loop->index }}">{{ $message }}</li>
                    @endforeach
                </ul>
                <div class="mt-5 flex justify-end">
                    <button type="button" wire:click="closeValidationModal" class="min-h-11 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Quay lại chỉnh sửa</button>
                </div>
            </div>
        </div>
    @endif
</div>

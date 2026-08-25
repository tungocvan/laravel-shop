<div class="space-y-5" aria-labelledby="request-designer-title">
    @if(session('request_success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[14rem_minmax(0,1fr)_18rem]">
        <nav class="rounded-xl border border-slate-200 bg-white p-3 xl:sticky xl:top-4 xl:self-start" aria-label="Các phần của trình thiết kế">
            <a href="#request-designer-metadata" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Thông tin chung</a>
            <a href="#request-designer-form" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Biểu mẫu</a>
            <a href="#request-designer-approval" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Phê duyệt & SLA</a>
            <a href="#request-designer-audience" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Đối tượng</a>
        </nav>

        <main class="min-w-0 space-y-5">
            <section id="request-designer-metadata" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-designer-title">
                <h2 id="request-designer-title" class="text-lg font-semibold text-slate-900">Thiết kế loại đề nghị</h2>
                <p class="mt-1 text-sm text-slate-600">Bản nháp trên máy chủ là nguồn dữ liệu chính thức. Xem trước và lưu cục bộ không tự phát hành phiên bản.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">Tiêu đề<input wire:model="title" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                    <label class="block text-sm font-medium text-slate-700">Mô tả<textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></textarea></label>
                    <label class="block text-sm font-medium text-slate-700">Hướng dẫn người gửi<textarea wire:model="requesterGuidance" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></textarea></label>
                </div>
            </section>

            <section id="request-designer-form" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-form-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 id="request-form-title" class="text-lg font-semibold text-slate-900">Thiết kế biểu mẫu</h2><p class="text-sm text-slate-600">Mã phần và mã trường được giữ ổn định. Dùng các nút di chuyển để sắp xếp.</p></div>
                    <button type="button" wire:click="addSection" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700">Thêm phần</button>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse($sections as $sectionIndex => $section)
                        <article wire:key="section-{{ $sectionIndex }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2">
                                    <label class="text-sm font-medium text-slate-700">Mã phần<input wire:model="sections.{{ $sectionIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm"></label>
                                    <label class="text-sm font-medium text-slate-700">Nhãn hiển thị<input wire:model="sections.{{ $sectionIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                                </div>
                                <div class="flex gap-1"><button type="button" wire:click="moveSection({{ $sectionIndex }}, -1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white">↑</button><button type="button" wire:click="moveSection({{ $sectionIndex }}, 1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white">↓</button><button type="button" wire:click="removeSection({{ $sectionIndex }})" wire:confirm="Xóa phần này và toàn bộ trường bên trong?" class="min-h-11 rounded-lg border border-red-200 bg-white px-3 text-sm text-red-700">Xóa</button></div>
                            </div>
                            <div class="mt-4 space-y-3">
                                @foreach((array)($section['fields'] ?? []) as $fieldIndex => $field)
                                    <div wire:key="field-{{ $sectionIndex }}-{{ $fieldIndex }}" class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                            <label class="text-sm font-medium text-slate-700">Mã trường<input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></label>
                                            <label class="text-sm font-medium text-slate-700">Nhãn hiển thị<input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                            <label class="text-sm font-medium text-slate-700">Kiểu dữ liệu<select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.type" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">@foreach(['text','textarea','integer','decimal','currency','date','datetime','boolean','select','multiselect','user','role','attachment','computed_display'] as $fieldType)<option value="{{ $fieldType }}">{{ $fieldType }}</option>@endforeach</select></label>
                                            <label class="text-sm font-medium text-slate-700">Phân loại dữ liệu<select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.classification" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="public_internal">Nội bộ công khai</option><option value="internal">Nội bộ</option><option value="confidential">Bảo mật</option></select></label>
                                            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.required"> Bắt buộc</label>
                                            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.offline_draft"> Cho phép lưu bản nháp cục bộ</label>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2"><button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, -1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển lên</button><button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, 1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển xuống</button><button type="button" wire:click="removeField({{ $sectionIndex }}, {{ $fieldIndex }})" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Xóa trường</button></div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" wire:click="addField({{ $sectionIndex }})" class="mt-3 min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700">Thêm trường</button>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">Chưa có phần biểu mẫu.</div>
                    @endforelse
                </div>
            </section>

            <section id="request-designer-approval" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-approval-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 id="request-approval-title" class="text-lg font-semibold text-slate-900">Các cấp phê duyệt & SLA</h2><p class="text-sm text-slate-600">Mỗi cấp duyệt có thời hạn riêng. Khi hết SLA, hệ thống cảnh báo, cho phép thời gian gia hạn và có thể tạm dừng tác vụ.</p></div>
                    <button type="button" wire:click="addStage" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700">Thêm cấp duyệt</button>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse($stages as $stageIndex => $stage)
                        <article wire:key="stage-{{ $stageIndex }}" class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3"><div><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cấp {{ $stageIndex + 1 }}</div><div class="font-semibold text-slate-900">{{ $stage['name'] ?? 'Cấp phê duyệt' }}</div></div><div class="flex gap-1"><button type="button" wire:click="moveStage({{ $stageIndex }}, -1)" class="min-h-10 min-w-10 rounded-lg border border-slate-300">↑</button><button type="button" wire:click="moveStage({{ $stageIndex }}, 1)" class="min-h-10 min-w-10 rounded-lg border border-slate-300">↓</button></div></div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <label class="text-sm font-medium text-slate-700">Mã cấp duyệt<input wire:model="stages.{{ $stageIndex }}.stage_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Tên<input wire:model.live="stages.{{ $stageIndex }}.name" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="text-sm font-medium text-slate-700">Chế độ<select wire:model="stages.{{ $stageIndex }}.mode" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="single">Một người duyệt</option><option value="parallel_all">Song song - tất cả</option><option value="parallel_any">Song song - bất kỳ</option></select></label>
                                <label class="text-sm font-medium text-slate-700">Bộ phân giải<select wire:model="stages.{{ $stageIndex }}.resolver_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="fixed_users">Người dùng cố định</option><option value="fixed_role">Vai trò cố định</option></select></label>
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Cấu hình bộ phân giải (JSON)<textarea wire:model="stages.{{ $stageIndex }}.resolver_config_json" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></textarea></label>
                                @error('stages.'.$stageIndex.'.resolver_config_json')<p class="text-sm text-red-600 md:col-span-2">{{ $message }}</p>@enderror
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Hướng dẫn<textarea wire:model="stages.{{ $stageIndex }}.instructions" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea></label>
                                <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="stages.{{ $stageIndex }}.allow_reassignment" class="h-4 w-4 rounded border-slate-300"> Cho phép giao lại</label>
                            </div>

                            <fieldset class="mt-5 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                                <legend class="px-2 text-sm font-semibold text-indigo-900">SLA & quá hạn</legend>
                                <p class="mb-4 text-xs leading-5 text-slate-600">Thời gian được tính trong backend theo UTC. Người dùng nhìn thấy thời gian theo Giờ Việt Nam (GMT+7).</p>
                                <div class="grid gap-4 lg:grid-cols-3">
                                    @foreach([
                                        ['key' => 'sla', 'label' => 'Thời hạn xử lý', 'help' => 'Thời gian tối đa để cấp này xử lý.'],
                                        ['key' => 'warning', 'label' => 'Cảnh báo trước hạn', 'help' => 'Thời điểm hệ thống bắt đầu cảnh báo.'],
                                        ['key' => 'grace', 'label' => 'Thời gian gia hạn', 'help' => 'Vẫn cho phép xử lý sau khi quá hạn.'],
                                    ] as $duration)
                                        <div>
                                            <label class="text-sm font-medium text-slate-700">{{ $duration['label'] }}</label>
                                            <div class="mt-1 grid grid-cols-[minmax(0,1fr)_7rem] gap-2">
                                                <input type="number" min="0" step="1" wire:model="stages.{{ $stageIndex }}.{{ $duration['key'] }}_value" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2" aria-label="{{ $duration['label'] }}">
                                                <select wire:model="stages.{{ $stageIndex }}.{{ $duration['key'] }}_unit" class="min-h-11 rounded-lg border border-slate-300 bg-white px-2 py-2"><option value="minutes">Phút</option><option value="hours">Giờ</option><option value="days">Ngày</option></select>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500">{{ $duration['help'] }}</p>
                                            @error('stages.'.$stageIndex.'.'.$duration['key'].'_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <label class="text-sm font-medium text-slate-700">Sau khi hết thời gian gia hạn<select wire:model="stages.{{ $stageIndex }}.timeout_action" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="notify_only">Chỉ cảnh báo, không tự khóa</option><option value="suspend">Tạm dừng tác vụ</option></select></label>
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-xs leading-5 text-slate-600"><strong class="block text-slate-800">Nguyên tắc an toàn</strong>Hết hạn không tự động phê duyệt hoặc từ chối. Với “Tạm dừng”, quản trị viên phải xử lý tiếp bằng cơ chế resume/reassign/escalate.</div>
                                </div>

                                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900">Thông báo email</h4>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">Chọn các sự kiện cần gửi email tự động cho cấp phê duyệt này. Thông báo nội bộ của hệ thống vẫn hoạt động độc lập.</p>
                                    </div>
                                    <div class="mt-3 grid gap-3">
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                            <input type="checkbox" wire:model="stages.{{ $stageIndex }}.email_on_assignment" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                                            <span><span class="block text-sm font-medium text-slate-800">Gửi email khi giao phê duyệt</span><span class="mt-0.5 block text-xs text-slate-500">Người được phân công phê duyệt sẽ nhận email khi tác vụ được kích hoạt.</span></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                            <input type="checkbox" wire:model="stages.{{ $stageIndex }}.email_on_decision" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                                            <span><span class="block text-sm font-medium text-slate-800">Gửi email khi có quyết định</span><span class="mt-0.5 block text-xs text-slate-500">Người gửi sẽ nhận email khi đề nghị được Phê duyệt, Từ chối hoặc Trả lại.</span></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                            <input type="checkbox" wire:model="stages.{{ $stageIndex }}.email_on_sla_warning" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                                            <span><span class="block text-sm font-medium text-slate-800">Gửi email cảnh báo SLA</span><span class="mt-0.5 block text-xs text-slate-500">Người đang phụ trách sẽ nhận email khi tác vụ bước vào thời gian cảnh báo trước hạn.</span></span>
                                        </label>
                                    </div>
                                </div>

                                @if(($stage['sla_value'] ?? '') !== '')<div class="mt-4 rounded-lg bg-white px-3 py-2 text-xs text-slate-600"><strong>Cấu hình hiện tại:</strong> SLA {{ $stage['sla_value'] }} {{ ['minutes'=>'phút','hours'=>'giờ','days'=>'ngày'][$stage['sla_unit'] ?? 'hours'] ?? '' }} · cảnh báo trước {{ $stage['warning_value'] ?? 0 }} {{ ['minutes'=>'phút','hours'=>'giờ','days'=>'ngày'][$stage['warning_unit'] ?? 'hours'] ?? '' }} · gia hạn {{ $stage['grace_value'] ?? 0 }} {{ ['minutes'=>'phút','hours'=>'giờ','days'=>'ngày'][$stage['grace_unit'] ?? 'hours'] ?? '' }}</div>@endif
                            </fieldset>

                            <div class="mt-4 flex justify-end"><button type="button" wire:click="removeStage({{ $stageIndex }})" wire:confirm="Xóa cấp duyệt này?" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Xóa cấp duyệt</button></div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">Chưa có cấp phê duyệt.</div>
                    @endforelse
                </div>
            </section>

            <section id="request-designer-audience" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-audience-title">
                <h2 id="request-audience-title" class="text-lg font-semibold text-slate-900">Đối tượng sử dụng</h2>
                <p class="mt-1 text-sm text-slate-600">Mỗi mục sử dụng actor_type, actor_id và capability.</p>
                <textarea wire:model="audiencesJson" rows="8" class="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm"></textarea>
                @error('audiencesJson')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </section>
        </main>

        <aside class="rounded-xl border border-slate-200 bg-white p-4 xl:sticky xl:top-4 xl:self-start" aria-label="Trạng thái và thao tác thiết kế">
            <h2 class="font-semibold text-slate-900">Trạng thái bản nháp</h2>
            <dl class="mt-3 space-y-2 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Phiên bản khóa</dt><dd class="font-mono">{{ $lockVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Schema</dt><dd>v{{ $schemaVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Số phần</dt><dd>{{ count($sections) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Cấp duyệt</dt><dd>{{ count($stages) }}</dd></div></dl>
            @error('lock_version')<div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" role="alert">Bản nháp đã thay đổi trên máy chủ. Hãy tải lại và kiểm tra trước khi lưu tiếp.</div>@enderror
            <div class="mt-4 grid gap-2"><button type="button" wire:click="save" wire:loading.attr="disabled" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 font-medium text-indigo-700">{{ __('Request::request.save') }}</button><button type="button" wire:click="publish" wire:confirm="{{ __('Request::request.publish_confirm') }}" wire:loading.attr="disabled" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white">{{ __('Request::request.publish') }}</button><a href="{{ route('request.admin.types.versions', $type->public_id) }}" class="flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Xem lịch sử phiên bản</a></div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Phiên bản đã phát hành là bất biến. SLA chỉ áp dụng cho task được tạo từ phiên bản đó và được snapshot khi kích hoạt.</p>
        </aside>
    </div>
</div>

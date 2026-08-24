<div class="space-y-5" aria-labelledby="request-designer-title">
    @if(session('request_success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[14rem_minmax(0,1fr)_18rem]">
        <nav class="rounded-xl border border-slate-200 bg-white p-3 xl:sticky xl:top-4 xl:self-start" aria-label="Các phần của trình thiết kế">
            <a href="#request-designer-metadata" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Thông tin chung</a>
            <a href="#request-designer-form" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Biểu mẫu</a>
            <a href="#request-designer-approval" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Phê duyệt</a>
            <a href="#request-designer-audience" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Đối tượng</a>
        </nav>

        <main class="min-w-0 space-y-5">
            <section id="request-designer-metadata" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-designer-title">
                <h2 id="request-designer-title" class="text-lg font-semibold text-slate-900">Thiết kế loại đề nghị</h2>
                <p class="mt-1 text-sm text-slate-600">Bản nháp trên máy chủ là nguồn dữ liệu chính thức. Xem trước và lưu cục bộ không tự phát hành phiên bản.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">Tiêu đề
                        <input wire:model="title" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Mô tả
                        <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Hướng dẫn người gửi
                        <textarea wire:model="requesterGuidance" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </label>
                </div>
            </section>

            <section id="request-designer-form" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-form-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="request-form-title" class="text-lg font-semibold text-slate-900">Thiết kế biểu mẫu</h2>
                        <p class="text-sm text-slate-600">Mã phần và mã trường được giữ ổn định. Dùng các nút di chuyển để sắp xếp bằng bàn phím.</p>
                    </div>
                    <button type="button" wire:click="addSection" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Thêm phần</button>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse($sections as $sectionIndex => $section)
                        <article wire:key="section-{{ $sectionIndex }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2">
                                    <label class="text-sm font-medium text-slate-700">Mã phần
                                        <input wire:model="sections.{{ $sectionIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm">
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">Nhãn hiển thị
                                        <input wire:model="sections.{{ $sectionIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                    </label>
                                </div>
                                <div class="flex gap-1" aria-label="Sắp xếp phần">
                                    <button type="button" wire:click="moveSection({{ $sectionIndex }}, -1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white" aria-label="Di chuyển phần lên">↑</button>
                                    <button type="button" wire:click="moveSection({{ $sectionIndex }}, 1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white" aria-label="Di chuyển phần xuống">↓</button>
                                    <button type="button" wire:click="removeSection({{ $sectionIndex }})" wire:confirm="Xóa phần này và toàn bộ trường bên trong?" class="min-h-11 rounded-lg border border-red-200 bg-white px-3 text-sm text-red-700">Xóa</button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach((array)($section['fields'] ?? []) as $fieldIndex => $field)
                                    <div wire:key="field-{{ $sectionIndex }}-{{ $fieldIndex }}" class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                            <label class="text-sm font-medium text-slate-700">Mã trường
                                                <input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">
                                            </label>
                                            <label class="text-sm font-medium text-slate-700">Nhãn hiển thị
                                                <input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2">
                                            </label>
                                            <label class="text-sm font-medium text-slate-700">Kiểu dữ liệu
                                                <select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.type" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                                    @foreach(['text','textarea','integer','decimal','currency','date','datetime','boolean','select','multiselect','user','role','attachment','computed_display'] as $fieldType)
                                                        <option value="{{ $fieldType }}">{{ $fieldType }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="text-sm font-medium text-slate-700">Phân loại dữ liệu
                                                <select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.classification" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                                    <option value="public_internal">Nội bộ công khai</option>
                                                    <option value="internal">Nội bộ</option>
                                                    <option value="confidential">Bảo mật</option>
                                                </select>
                                            </label>
                                            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.required" class="h-4 w-4 rounded border-slate-300"> Bắt buộc</label>
                                            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.offline_draft" class="h-4 w-4 rounded border-slate-300"> Cho phép lưu bản nháp cục bộ</label>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, -1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển lên</button>
                                            <button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, 1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển xuống</button>
                                            <button type="button" wire:click="removeField({{ $sectionIndex }}, {{ $fieldIndex }})" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Xóa trường</button>
                                        </div>
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
                    <div><h2 id="request-approval-title" class="text-lg font-semibold text-slate-900">Các cấp phê duyệt</h2><p class="text-sm text-slate-600">Chỉ sử dụng danh sách có thứ tự; MR-08 không bổ sung sơ đồ workflow.</p></div>
                    <button type="button" wire:click="addStage" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700">Thêm cấp duyệt</button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($stages as $stageIndex => $stage)
                        <article wire:key="stage-{{ $stageIndex }}" class="rounded-xl border border-slate-200 p-4">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="text-sm font-medium text-slate-700">Mã cấp duyệt<input wire:model="stages.{{ $stageIndex }}.stage_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Tên<input wire:model="stages.{{ $stageIndex }}.name" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="text-sm font-medium text-slate-700">Chế độ<select wire:model="stages.{{ $stageIndex }}.mode" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="single">Một người duyệt</option><option value="parallel_all">Song song - tất cả</option><option value="parallel_any">Song song - bất kỳ</option></select></label>
                                <label class="text-sm font-medium text-slate-700">Bộ phân giải<select wire:model="stages.{{ $stageIndex }}.resolver_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="fixed_user">Người dùng cố định</option><option value="fixed_role">Vai trò cố định</option></select></label>
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Cấu hình bộ phân giải (JSON)<textarea wire:model="stages.{{ $stageIndex }}.resolver_config_json" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></textarea></label>
                                @error('stages.'.$stageIndex.'.resolver_config_json')<p class="text-sm text-red-600 md:col-span-2">{{ $message }}</p>@enderror
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Hướng dẫn<textarea wire:model="stages.{{ $stageIndex }}.instructions" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea></label>
                                <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="stages.{{ $stageIndex }}.allow_reassignment" class="h-4 w-4 rounded border-slate-300"> Cho phép giao lại</label>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" wire:click="moveStage({{ $stageIndex }}, -1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển lên</button>
                                <button type="button" wire:click="moveStage({{ $stageIndex }}, 1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Di chuyển xuống</button>
                                <button type="button" wire:click="removeStage({{ $stageIndex }})" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Xóa cấp duyệt</button>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">Chưa có cấp phê duyệt.</div>
                    @endforelse
                </div>
            </section>

            <section id="request-designer-audience" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-audience-title">
                <h2 id="request-audience-title" class="text-lg font-semibold text-slate-900">Đối tượng sử dụng</h2>
                <p class="mt-1 text-sm text-slate-600">MR-08 vẫn dùng trình chỉnh sửa JSON theo ID ổn định; Request không truy vấn trực tiếp bảng định danh.</p>
                <textarea wire:model="audiencesJson" rows="8" class="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm" aria-describedby="request-audience-help"></textarea>
                <p id="request-audience-help" class="mt-2 text-xs text-slate-500">Mỗi mục sử dụng actor_type, actor_id và capability.</p>
                @error('audiencesJson')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </section>
        </main>

        <aside class="rounded-xl border border-slate-200 bg-white p-4 xl:sticky xl:top-4 xl:self-start" aria-label="Trạng thái và thao tác thiết kế">
            <h2 class="font-semibold text-slate-900">Trạng thái bản nháp</h2>
            <dl class="mt-3 space-y-2 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Phiên bản khóa</dt><dd class="font-mono">{{ $lockVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Schema</dt><dd>v{{ $schemaVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Số phần</dt><dd>{{ count($sections) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Cấp duyệt</dt><dd>{{ count($stages) }}</dd></div></dl>
            @error('lock_version')<div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" role="alert">Bản nháp đã thay đổi trên máy chủ. Hãy tải lại và kiểm tra trước khi lưu tiếp.</div>@enderror
            <div class="mt-4 grid gap-2">
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 font-medium text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Request::request.save') }}</button>
                <button type="button" wire:click="publish" wire:confirm="{{ __('Request::request.publish_confirm') }}" wire:loading.attr="disabled" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Request::request.publish') }}</button>
                <a href="{{ route('request.admin.types.versions', $type->public_id) }}" class="flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Xem lịch sử phiên bản</a>
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Phiên bản đã phát hành là bất biến. Kiểm tra phía máy chủ luôn là nguồn xác thực cuối cùng.</p>
        </aside>
    </div>
</div>

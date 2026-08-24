<div class="space-y-5" aria-labelledby="request-designer-title">
    @if(session('request_success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[14rem_minmax(0,1fr)_18rem]">
        <nav class="rounded-xl border border-slate-200 bg-white p-3 xl:sticky xl:top-4 xl:self-start" aria-label="Designer sections">
            <a href="#request-designer-metadata" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Metadata</a>
            <a href="#request-designer-form" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Form</a>
            <a href="#request-designer-approval" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Approval</a>
            <a href="#request-designer-audience" class="block min-h-10 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Audience</a>
        </nav>

        <main class="min-w-0 space-y-5">
            <section id="request-designer-metadata" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-designer-title">
                <h2 id="request-designer-title" class="text-lg font-semibold text-slate-900">Request type designer</h2>
                <p class="mt-1 text-sm text-slate-600">Draft changes are server-authoritative. Preview and local persistence never publish a version.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">Title
                        <input wire:model="title" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Description
                        <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Requester guidance
                        <textarea wire:model="requesterGuidance" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </label>
                </div>
            </section>

            <section id="request-designer-form" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-form-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="request-form-title" class="text-lg font-semibold text-slate-900">Form builder</h2>
                        <p class="text-sm text-slate-600">Sections and fields preserve their stable keys. Use move controls for keyboard-accessible ordering.</p>
                    </div>
                    <button type="button" wire:click="addSection" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Add section</button>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse($sections as $sectionIndex => $section)
                        <article wire:key="section-{{ $sectionIndex }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2">
                                    <label class="text-sm font-medium text-slate-700">Section key
                                        <input wire:model="sections.{{ $sectionIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm">
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">Label
                                        <input wire:model="sections.{{ $sectionIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                    </label>
                                </div>
                                <div class="flex gap-1" aria-label="Section ordering">
                                    <button type="button" wire:click="moveSection({{ $sectionIndex }}, -1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white" aria-label="Move section up">↑</button>
                                    <button type="button" wire:click="moveSection({{ $sectionIndex }}, 1)" class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white" aria-label="Move section down">↓</button>
                                    <button type="button" wire:click="removeSection({{ $sectionIndex }})" wire:confirm="Remove this section and its fields?" class="min-h-11 rounded-lg border border-red-200 bg-white px-3 text-sm text-red-700">Remove</button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach((array)($section['fields'] ?? []) as $fieldIndex => $field)
                                    <div wire:key="field-{{ $sectionIndex }}-{{ $fieldIndex }}" class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                            <label class="text-sm font-medium text-slate-700">Field key
                                                <input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">
                                            </label>
                                            <label class="text-sm font-medium text-slate-700">Label
                                                <input wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.label" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2">
                                            </label>
                                            <label class="text-sm font-medium text-slate-700">Type
                                                <select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.type" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                                    @foreach(['text','textarea','integer','decimal','currency','date','datetime','boolean','select','multiselect','user','role','attachment','computed_display'] as $fieldType)
                                                        <option value="{{ $fieldType }}">{{ $fieldType }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="text-sm font-medium text-slate-700">Classification
                                                <select wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.classification" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                                    <option value="public_internal">public_internal</option>
                                                    <option value="internal">internal</option>
                                                    <option value="confidential">confidential</option>
                                                </select>
                                            </label>
                                            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.required" class="h-4 w-4 rounded border-slate-300"> Required</label>
                                            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="sections.{{ $sectionIndex }}.fields.{{ $fieldIndex }}.offline_draft" class="h-4 w-4 rounded border-slate-300"> Eligible for local draft policy</label>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, -1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Move up</button>
                                            <button type="button" wire:click="moveField({{ $sectionIndex }}, {{ $fieldIndex }}, 1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Move down</button>
                                            <button type="button" wire:click="removeField({{ $sectionIndex }}, {{ $fieldIndex }})" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Remove field</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" wire:click="addField({{ $sectionIndex }})" class="mt-3 min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700">Add field</button>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">No form sections yet.</div>
                    @endforelse
                </div>
            </section>

            <section id="request-designer-approval" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-approval-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 id="request-approval-title" class="text-lg font-semibold text-slate-900">Approval stages</h2><p class="text-sm text-slate-600">Ordered list only; no workflow graph is introduced.</p></div>
                    <button type="button" wire:click="addStage" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700">Add stage</button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($stages as $stageIndex => $stage)
                        <article wire:key="stage-{{ $stageIndex }}" class="rounded-xl border border-slate-200 p-4">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="text-sm font-medium text-slate-700">Stage key<input wire:model="stages.{{ $stageIndex }}.stage_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Name<input wire:model="stages.{{ $stageIndex }}.name" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="text-sm font-medium text-slate-700">Mode<select wire:model="stages.{{ $stageIndex }}.mode" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="sequential">sequential</option><option value="parallel_all">parallel_all</option><option value="parallel_any">parallel_any</option></select></label>
                                <label class="text-sm font-medium text-slate-700">Resolver<select wire:model="stages.{{ $stageIndex }}.resolver_key" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="fixed_user">fixed_user</option><option value="fixed_role">fixed_role</option></select></label>
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Resolver config JSON<textarea wire:model="stages.{{ $stageIndex }}.resolver_config_json" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"></textarea></label>
                                @error('stages.'.$stageIndex.'.resolver_config_json')<p class="text-sm text-red-600 md:col-span-2">{{ $message }}</p>@enderror
                                <label class="text-sm font-medium text-slate-700 md:col-span-2">Instructions<textarea wire:model="stages.{{ $stageIndex }}.instructions" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea></label>
                                <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="stages.{{ $stageIndex }}.allow_reassignment" class="h-4 w-4 rounded border-slate-300"> Allow reassignment</label>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" wire:click="moveStage({{ $stageIndex }}, -1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Move up</button>
                                <button type="button" wire:click="moveStage({{ $stageIndex }}, 1)" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm">Move down</button>
                                <button type="button" wire:click="removeStage({{ $stageIndex }})" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm text-red-700">Remove stage</button>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-600">No approval stages yet.</div>
                    @endforelse
                </div>
            </section>

            <section id="request-designer-audience" class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="request-audience-title">
                <h2 id="request-audience-title" class="text-lg font-semibold text-slate-900">Audience</h2>
                <p class="mt-1 text-sm text-slate-600">Audience remains an advanced stable-ID JSON editor in MR-08; Request never queries identity tables directly.</p>
                <textarea wire:model="audiencesJson" rows="8" class="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm" aria-describedby="request-audience-help"></textarea>
                <p id="request-audience-help" class="mt-2 text-xs text-slate-500">Entries use actor_type, actor_id and capability.</p>
                @error('audiencesJson')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </section>
        </main>

        <aside class="rounded-xl border border-slate-200 bg-white p-4 xl:sticky xl:top-4 xl:self-start" aria-label="Designer status and actions">
            <h2 class="font-semibold text-slate-900">Draft status</h2>
            <dl class="mt-3 space-y-2 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Lock version</dt><dd class="font-mono">{{ $lockVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Schema</dt><dd>v{{ $schemaVersion }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Sections</dt><dd>{{ count($sections) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Stages</dt><dd>{{ count($stages) }}</dd></div></dl>
            @error('lock_version')<div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" role="alert">This draft changed on the server. Reload and review before saving again.</div>@enderror
            <div class="mt-4 grid gap-2">
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="min-h-11 rounded-lg border border-indigo-300 px-4 py-2 font-medium text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Request::request.save') }}</button>
                <button type="button" wire:click="publish" wire:confirm="{{ __('Request::request.publish_confirm') }}" wire:loading.attr="disabled" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Request::request.publish') }}</button>
                <a href="{{ route('request.admin.types.versions', $type->public_id) }}" class="flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Review version history</a>
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Publishing is immutable. Server validation remains authoritative.</p>
        </aside>
    </div>
</div>

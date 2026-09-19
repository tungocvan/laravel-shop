@props(['template', 'dossier' => null, 'defaults' => []])

@php
    $persistedItems = $dossier?->items?->keyBy('code') ?? collect();
@endphp

<div class="space-y-5" x-data="{ customItems: [] }">
    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-950">{{ $template->name }}</h2>
                <p class="text-sm text-slate-600">Các trường có dấu * là bắt buộc. File đính kèm của từng mục là tùy chọn.</p>
            </div>
            <button type="button" @click="customItems.push({ title: '', effective_to: '' })" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">+ Thêm mục hồ sơ</button>
        </div>

        @foreach($template->items as $templateItem)
            @php
                $savedItem = $persistedItems->get($templateItem->code);
                $metadata = $savedItem?->metadata ?? [];
                $itemDefaults = $defaults[$templateItem->code] ?? [];
            @endphp
            <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm" open>
                <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ str_pad((string) $templateItem->sort_order, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-slate-950">{{ $templateItem->name }}</div>
                        <div class="mt-0.5 text-xs text-slate-500">{{ $templateItem->is_required ? 'Mục bắt buộc' : 'Mục tùy chọn' }} · Upload file tùy chọn</div>
                    </div>
                    <span class="text-slate-400 group-open:rotate-180">⌄</span>
                </summary>
                <div class="border-t border-slate-100 px-5 py-4">
                    @if(count($templateItem->metadata_schema ?? []))
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach($templateItem->metadata_schema as $field)
                                <div class="{{ ($field['type'] ?? '') === 'textarea' ? 'md:col-span-3' : '' }}">
                                    <label class="block text-sm font-medium text-slate-700">{{ $field['label'] }} @if($field['required'] ?? false)<span class="text-rose-600">*</span>@endif</label>
                                    @php($fieldValue = old('items.'.$templateItem->code.'.'.$field['key'], $metadata[$field['key']] ?? $itemDefaults[$field['key']] ?? ''))
                                    @if(($field['type'] ?? 'text') === 'textarea')
                                        <textarea name="items[{{ $templateItem->code }}][{{ $field['key'] }}]" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ $fieldValue }}</textarea>
                                    @else
                                        <input type="{{ ($field['type'] ?? 'text') === 'date' ? 'date' : 'text' }}" name="items[{{ $templateItem->code }}][{{ $field['key'] }}]" value="{{ $fieldValue }}" @required($field['required'] ?? false) class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="{{ count($templateItem->metadata_schema ?? []) ? 'mt-4' : '' }} rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                        <label class="block text-sm font-semibold text-slate-700">Tài liệu đính kèm</label>
                        <input type="file" name="item_files[{{ $templateItem->code }}][]" multiple class="mt-2 block w-full text-sm text-slate-600">
                        @foreach($savedItem?->attachments ?? [] as $attachment)
                            <div class="mt-2 text-xs text-slate-600">✓ {{ $attachment->original_name }} · {{ $attachment->sync_status }}</div>
                        @endforeach
                    </div>
                </div>
            </details>
        @endforeach

        @foreach(($dossier?->items ?? collect())->whereNull('template_item_id') as $customItem)
            <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tên mục hồ sơ bổ sung</label>
                        <input name="existing_custom_items[{{ $customItem->id }}][title]" value="{{ old('existing_custom_items.'.$customItem->id.'.title', $customItem->title) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Hiệu lực đến (nếu có)</label>
                        <input type="date" name="existing_custom_items[{{ $customItem->id }}][effective_to]" value="{{ old('existing_custom_items.'.$customItem->id.'.effective_to', $customItem->metadata['effective_to'] ?? '') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <input type="file" multiple name="existing_custom_files[{{ $customItem->id }}][]" class="block w-full text-sm text-slate-600">
                        @foreach($customItem->attachments as $attachment)
                            <div class="mt-2 text-xs text-slate-600">✓ {{ $attachment->original_name }} · {{ $attachment->sync_status }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <template x-for="(item, index) in customItems" :key="index">
            <div class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="grid flex-1 gap-4 md:grid-cols-2">
                        <div><label class="block text-sm font-medium text-slate-700">Tên mục hồ sơ</label><input :name="'custom_items['+index+'][title]'" x-model="item.title" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5"></div>
                        <div><label class="block text-sm font-medium text-slate-700">Hiệu lực đến (nếu có)</label><input type="date" :name="'custom_items['+index+'][effective_to]'" x-model="item.effective_to" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5"></div>
                        <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700">File đính kèm</label><input type="file" multiple :name="'custom_files['+index+'][]'" class="mt-2 block w-full text-sm text-slate-600"></div>
                    </div>
                    <button type="button" @click="customItems.splice(index, 1)" class="rounded-lg px-2 py-1 text-sm font-semibold text-rose-600 hover:bg-rose-50">Xóa</button>
                </div>
            </div>
        </template>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-slate-950">Hồ sơ tổng hợp</h2>
        <p class="mt-1 text-sm text-slate-600">Có thể upload file tổng chứa toàn bộ mục lục; không bắt buộc.</p>
        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
            <input type="file" name="master_files[]" multiple class="block w-full text-sm text-slate-600">
            @foreach($dossier?->attachments?->where('kind', 'master') ?? [] as $attachment)
                <div class="mt-2 text-xs text-slate-600">✓ {{ $attachment->original_name }} · {{ $attachment->sync_status }}</div>
            @endforeach
        </div>
    </section>
</div>

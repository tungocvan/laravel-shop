@extends('Admin::layouts.master')

@section('title', $profile->exists ? 'Cập nhật bộ HSSP' : 'Tạo bộ HSSP')

@section('content')
@php
    $persistedItems = $dossier?->items?->keyBy('code') ?? collect();
@endphp
<div class="container-fluid max-w-6xl space-y-5" x-data="{ customItems: [] }">
    <header>
        <a href="{{ route('admin.pharma.hssp.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Quay về HSSP</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">{{ $profile->exists ? 'Cập nhật bộ hồ sơ sản phẩm' : 'Tạo bộ hồ sơ sản phẩm' }}</h1>
                <p class="mt-1 text-sm text-slate-600">Quản lý mục lục, hiệu lực và tài liệu của HSSP. File đính kèm là tùy chọn.</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $dossierTemplate->name }}</span>
        </div>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="text-xs font-semibold uppercase text-slate-500">Thuốc</div><div class="mt-1 font-semibold text-slate-950">{{ $medicine->name }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">Medicine code</div><div class="mt-1 font-mono text-sm">{{ $medicine->medicine_code ?: 'Chưa có' }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">GPLH</div><div class="mt-1 font-mono text-sm">{{ $medicine->registration_number ?: '—' }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">Hàm lượng</div><div class="mt-1 text-sm">{{ $medicine->concentration ?: '—' }}</div></div>
        </div>
    </section>

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ $profile->exists ? route('admin.pharma.hssp.update', [$medicine->id, $profile->id]) : route('admin.pharma.hssp.store', $medicine->id) }}" class="space-y-5">
        @csrf
        @if($profile->exists) @method('PUT') @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-950">Thông tin bộ hồ sơ</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phiên bản hồ sơ</label>
                    <input name="profile_version" value="{{ old('profile_version', $profile->profile_version) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Trạng thái</label>
                    <select name="profile_status" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        @foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('profile_status', $profile->profile_status) === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nguồn hồ sơ</label>
                    <input name="source" value="{{ old('source', $profile->source) }}" placeholder="Manual, DAV..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700">Ghi chú</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('notes', $profile->notes) }}</textarea>
                </div>
                <label class="md:col-span-3 inline-flex items-center gap-3">
                    <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $profile->is_current ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-slate-700">Đặt làm HSSP hiện hành</span>
                </label>
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Mục lục bộ hồ sơ sản phẩm</h2>
                    <p class="text-sm text-slate-600">GMP và hiệu lực số đăng ký bắt buộc nhập; file của từng mục không bắt buộc.</p>
                </div>
                <button type="button" @click="customItems.push({ title: '', effective_to: '' })" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">+ Thêm mục hồ sơ</button>
            </div>

            @foreach($dossierTemplate->items as $templateItem)
                @php
                    $savedItem = $persistedItems->get($templateItem->code);
                    $metadata = $savedItem?->metadata ?? [];
                @endphp
                <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm" open>
                    <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ str_pad((string) $templateItem->sort_order, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-950">{{ $templateItem->name }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">{{ $templateItem->is_required ? 'Mục bắt buộc' : 'Mục tùy chọn' }} · File đính kèm tùy chọn</div>
                        </div>
                        <span class="text-slate-400 group-open:rotate-180">⌄</span>
                    </summary>
                    <div class="border-t border-slate-100 px-5 py-4">
                        @if(count($templateItem->metadata_schema ?? []))
                            <div class="grid gap-4 md:grid-cols-3">
                                @foreach($templateItem->metadata_schema as $field)
                                    <div class="{{ ($field['type'] ?? '') === 'textarea' ? 'md:col-span-3' : '' }}">
                                        <label class="block text-sm font-medium text-slate-700">{{ $field['label'] }} @if($field['required'] ?? false)<span class="text-rose-600">*</span>@endif</label>
                                        @if(($field['type'] ?? 'text') === 'textarea')
                                            <textarea name="items[{{ $templateItem->code }}][{{ $field['key'] }}]" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('items.'.$templateItem->code.'.'.$field['key'], $metadata[$field['key']] ?? '') }}</textarea>
                                        @else
                                            <input type="{{ ($field['type'] ?? 'text') === 'date' ? 'date' : 'text' }}" name="items[{{ $templateItem->code }}][{{ $field['key'] }}]" value="{{ old('items.'.$templateItem->code.'.'.$field['key'], $metadata[$field['key']] ?? ($templateItem->code === 'registration' && $field['key'] === 'document_number' ? $medicine->registration_number : '')) }}" @required($field['required'] ?? false) class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="{{ count($templateItem->metadata_schema ?? []) ? 'mt-4' : '' }} rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                            <label class="block text-sm font-semibold text-slate-700">Tài liệu đính kèm</label>
                            <input type="file" name="item_files[{{ $templateItem->code }}][]" multiple class="mt-2 block w-full text-sm text-slate-600">
                            @if($savedItem?->attachments?->isNotEmpty())
                                <div class="mt-3 space-y-1 text-xs text-slate-600">
                                    @foreach($savedItem->attachments as $attachment)
                                        <div>✓ {{ $attachment->original_name }} · {{ $attachment->sync_status }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </details>
            @endforeach

            <template x-for="(item, index) in customItems" :key="index">
                <div class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="grid flex-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Tên mục hồ sơ</label>
                                <input :name="'custom_items['+index+'][title]'" x-model="item.title" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5" placeholder="Ví dụ: Giấy tiếp nhận hồ sơ gia hạn">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Hiệu lực đến (nếu có)</label>
                                <input type="date" :name="'custom_items['+index+'][effective_to]'" x-model="item.effective_to" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">File đính kèm</label>
                                <input type="file" multiple :name="'custom_files['+index+'][]'" class="mt-2 block w-full text-sm text-slate-600">
                            </div>
                        </div>
                        <button type="button" @click="customItems.splice(index, 1)" class="rounded-lg px-2 py-1 text-sm font-semibold text-rose-600 hover:bg-rose-50">Xóa</button>
                    </div>
                </div>
            </template>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-950">Hồ sơ tổng hợp</h2>
            <p class="mt-1 text-sm text-slate-600">Có thể tải lên một hoặc nhiều file tổng chứa toàn bộ các mục hồ sơ.</p>
            <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <input type="file" name="master_files[]" multiple class="block w-full text-sm text-slate-600">
                @if($dossier?->attachments?->where('kind', 'master')->isNotEmpty())
                    <div class="mt-3 space-y-1 text-xs text-slate-600">
                        @foreach($dossier->attachments->where('kind', 'master') as $attachment)
                            <div>✓ {{ $attachment->original_name }} · {{ $attachment->sync_status }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="sticky bottom-4 flex justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <a href="{{ route('admin.pharma.hssp.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Hủy</a>
            <button class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $profile->exists ? 'Lưu bộ HSSP' : 'Tạo bộ HSSP' }}</button>
        </div>
    </form>
</div>
@endsection

<section class="{{ $fieldKey ? 'rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3' : 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm' }}" aria-labelledby="request-attachments-{{ $fieldKey ?? 'general' }}">
    <h2 id="request-attachments-{{ $fieldKey ?? 'general' }}" class="{{ $fieldKey ? 'text-sm font-semibold text-slate-800' : 'text-lg font-bold text-gray-900' }}">{{ $fieldKey ? __('Request::request.field_attachments') : __('Request::request.attachments') }}</h2>
    @if(session('request_attachment_success'))<p class="mt-3 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('request_attachment_success') }}</p>@endif
    @can('upload', [\Modules\Request\Models\RequestAttachment::class, $request])
        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1"><label for="request-upload-{{ $fieldKey ?? 'general' }}" class="block text-sm font-medium text-gray-700">{{ __('Request::request.choose_attachment') }}</label><input id="request-upload-{{ $fieldKey ?? 'general' }}" wire:model="uploads" type="file" multiple accept=".pdf,.png,.jpg,.jpeg,.docx,.xlsx" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm"><p class="mt-1 text-xs text-slate-500">{{ __('Request::request.multiple_attachment_help') }}</p></div>
            <button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="uploads,store" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60">{{ __('Request::request.upload') }}</button>
        </div>
        @error('uploads')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        @error('uploads.*')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
    @endcan
    <ul class="mt-4 space-y-2">
        @forelse($attachments as $attachment)
            <li class="flex flex-col gap-2 rounded-xl border border-gray-200 p-3 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="truncate text-sm font-medium text-gray-900">{{ $attachment->original_filename }}</p><p class="text-xs text-gray-500">{{ number_format($attachment->size_bytes / 1024, 1) }} KB · {{ __('Request::request.attachment_scan_statuses.'.$attachment->scan_status->value) }}</p></div>@can('download', $attachment)<a href="{{ route('request.attachments.download', [$request->public_id, $attachment->public_id]) }}" class="text-sm font-semibold text-indigo-700">{{ __('Request::request.download') }}</a>@endcan</li>
        @empty
            <li class="text-sm text-gray-500">{{ __('Request::request.no_attachments') }}</li>
        @endforelse
    </ul>
</section>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $post->title ?: 'Bài Facebook #' . $post->id }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $post->page?->page_name ?? '-' }} · {{ $post->status_label }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-base font-semibold text-gray-900">Nội dung</h2>
            <div class="mt-4 whitespace-pre-wrap text-sm leading-6 text-gray-700">{{ $post->message ?: '-' }}</div>
            @if ($post->link_url)
                <a href="{{ $post->link_url }}" target="_blank" class="mt-4 block text-sm font-semibold text-indigo-600">{{ $post->link_url }}</a>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Meta</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-gray-500">Facebook Post ID</dt><dd class="font-medium text-gray-900">{{ $post->facebook_post_id ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Published</dt><dd class="font-medium text-gray-900">{{ $post->published_at?->format('Y-m-d H:i:s') ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Attempts</dt><dd class="font-medium text-gray-900">{{ $post->attempts }}</dd></div>
                <div><dt class="text-gray-500">Error</dt><dd class="font-medium text-red-700">{{ $post->last_error_message ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Trace ID</dt><dd class="font-medium text-gray-900">{{ $post->last_error_trace_id ?? '-' }}</dd></div>
            </dl>
        </div>
    </div>
</div>

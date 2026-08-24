<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="request-comments-title">
    <h2 id="request-comments-title" class="text-lg font-bold text-gray-900">{{ __('Request::request.comments') }}</h2>
    @if(session('request_comment_success'))<p class="mt-3 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('request_comment_success') }}</p>@endif
    @can('create', [\Modules\Request\Models\RequestComment::class, $request])
        <form wire:submit="add" class="mt-4 space-y-3">
            <label for="request-comment-body" class="block text-sm font-medium text-gray-700">{{ __('Request::request.add_comment') }}</label>
            <textarea id="request-comment-body" wire:model="body" rows="3" maxlength="5000" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" aria-describedby="request-comment-error"></textarea>
            @error('body')<p id="request-comment-error" class="text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
            <button type="submit" wire:loading.attr="disabled" wire:target="add" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60">{{ __('Request::request.post_comment') }}</button>
        </form>
    @endcan
    <div class="mt-5 space-y-3">
        @forelse($comments as $comment)
            @can('view', $comment)<article class="rounded-xl border border-gray-200 bg-gray-50 p-4"><div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500"><span>{{ __('Request::request.user_reference', ['id' => $comment->author_id]) }}</span><time datetime="{{ $comment->created_at?->toIso8601String() }}">{{ $comment->created_at?->diffForHumans() }}</time></div><p class="mt-2 whitespace-pre-wrap break-words text-sm text-gray-800">{{ $comment->redacted_at ? __('Request::request.comment_redacted') : $comment->body }}</p></article>@endcan
        @empty
            <p class="text-sm text-gray-500">{{ __('Request::request.no_comments') }}</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $comments->links() }}</div>
</section>

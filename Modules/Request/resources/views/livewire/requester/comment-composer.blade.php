<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="request-comments-title">
    <div>
        <h2 id="request-comments-title" class="text-lg font-bold text-slate-900">{{ __('Request::request.comments') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Request::request.comments_help') }}</p>
    </div>

    @if(session('request_comment_success'))
        <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('request_comment_success') }}</p>
    @endif

    <div class="mt-4 space-y-3">
        @forelse($comments as $comment)
            @can('view', $comment)
                <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <span class="font-semibold text-slate-700">{{ __('Request::request.user_reference', ['id' => $comment->author_id]) }}</span>
                        <time datetime="{{ $comment->created_at?->toIso8601String() }}">{{ $comment->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-800">{{ $comment->redacted_at ? __('Request::request.comment_redacted') : $comment->body }}</p>
                </article>
            @endcan
        @empty
            <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">{{ __('Request::request.no_comments') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $comments->links() }}</div>

    @can('create', [\Modules\Request\Models\RequestComment::class, $request])
        <form wire:submit="add" class="mt-5 border-t border-slate-100 pt-4">
            <label for="request-comment-body" class="sr-only">{{ __('Request::request.add_comment') }}</label>
            <textarea id="request-comment-body" wire:model="body" rows="3" maxlength="5000" placeholder="{{ __('Request::request.comment_placeholder') }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" aria-describedby="request-comment-error"></textarea>
            @error('body')<p id="request-comment-error" class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
            <button type="submit" wire:loading.attr="disabled" wire:target="add" class="mt-3 min-h-11 w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60 sm:w-auto">{{ __('Request::request.post_comment') }}</button>
        </form>
    @endcan
</section>

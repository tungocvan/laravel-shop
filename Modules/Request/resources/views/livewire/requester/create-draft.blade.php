<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('request.catalog') }}" class="text-sm font-semibold text-indigo-700">← {{ __('Request::request.catalog.title') }}</a>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="text-sm font-semibold text-indigo-600">{{ $type->group->name }}</div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $type->currentPublishedVersion->title }}</h1>
        @if($type->currentPublishedVersion->description)<p class="mt-3 text-sm leading-6 text-gray-600">{{ $type->currentPublishedVersion->description }}</p>@endif
        @if($type->currentPublishedVersion->requester_guidance)<div class="mt-4 rounded-xl bg-indigo-50 p-4 text-sm leading-6 text-indigo-900">{{ $type->currentPublishedVersion->requester_guidance }}</div>@endif
        <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="mt-6 w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60 sm:w-auto"><span wire:loading.remove wire:target="create">{{ __('Request::request.create_draft') }}</span><span wire:loading wire:target="create">{{ __('Request::request.creating') }}</span></button>
    </section>
</div>

<div class="sticky bottom-3 rounded-2xl border border-indigo-200 bg-white/95 p-4 shadow-lg backdrop-blur">
    @if($suspended)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="status">
            <div class="font-semibold">Tác vụ đã tạm dừng do quá hạn SLA.</div>
            <p class="mt-1">Không thể phê duyệt, trả lại hoặc từ chối cho đến khi tác vụ được xử lý bởi cơ chế tiếp theo.</p>
        </div>
    @else
        <button
            type="button"
            wire:click="$set('confirming', true)"
            class="w-full rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white"
        >
            {{ __('Request::request.decide') }}
        </button>

        @if($confirming)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="decision-title">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                    <h2 id="decision-title" class="text-lg font-bold text-gray-900">
                        {{ __('Request::request.decision_confirm') }}
                    </h2>

                    <label for="request-decision" class="mt-4 block text-sm font-medium text-gray-700">
                        {{ __('Request::request.decision') }}
                    </label>
                    <select
                        id="request-decision"
                        wire:model.live="decision"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5"
                    >
                        <option value="approve">{{ __('Request::request.approve') }}</option>
                        <option value="return">{{ __('Request::request.return') }}</option>
                        <option value="reject">{{ __('Request::request.reject') }}</option>
                    </select>

                    <label for="request-decision-reason" class="mt-4 block text-sm font-medium text-gray-700">
                        {{ __('Request::request.reason') }}
                        @if($decision !== 'approve')
                            <span class="text-red-600" aria-hidden="true">*</span>
                        @endif
                    </label>
                    <textarea
                        id="request-decision-reason"
                        wire:model="reason"
                        rows="4"
                        class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm"
                    ></textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="$set('confirming', false)"
                            class="rounded-xl border border-gray-300 px-4 py-2.5 font-semibold text-gray-700"
                        >
                            {{ __('Request::request.back') }}
                        </button>
                        <button
                            type="button"
                            wire:click="decide"
                            wire:loading.attr="disabled"
                            wire:target="decide"
                            class="rounded-xl px-4 py-2.5 font-semibold text-white disabled:opacity-60 {{ $decision === 'reject' ? 'bg-red-700' : ($decision === 'return' ? 'bg-amber-600' : 'bg-indigo-600') }}"
                        >
                            <span wire:loading.remove wire:target="decide">
                                {{ $decision === 'reject' ? __('Request::request.reject') : ($decision === 'return' ? __('Request::request.return') : __('Request::request.approve')) }}
                            </span>
                            <span wire:loading wire:target="decide">{{ __('Request::request.loading') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

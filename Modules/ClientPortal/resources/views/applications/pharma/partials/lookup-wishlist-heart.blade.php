@if(($item['source_id'] ?? '') !== '')
    <form method="POST" action="{{ route('client.pharma.lookups.wishlist.toggle') }}" class="inline-flex">
        @csrf
        <input type="hidden" name="source_type" value="{{ $wishlistSource }}">
        <input type="hidden" name="source_id" value="{{ $item['source_id'] }}">
        <input type="hidden" name="q" value="{{ $keyword }}">
        <input type="hidden" name="page" value="{{ $page }}">
        @if(isset($field))<input type="hidden" name="field" value="{{ $field }}">@endif
        <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-xl border px-3 text-sm font-bold {{ in_array($item['source_id'], $savedIds ?? [], true) ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-300 bg-white text-slate-700' }}"
            aria-label="{{ in_array($item['source_id'], $savedIds ?? [], true) ? 'Bỏ quan tâm' : 'Thêm vào danh sách quan tâm' }} {{ $item['name'] ?? 'thuốc' }}"
            title="{{ in_array($item['source_id'], $savedIds ?? [], true) ? 'Bỏ quan tâm' : 'Lưu quan tâm' }}">
            <span aria-hidden="true">{{ in_array($item['source_id'], $savedIds ?? [], true) ? '♥' : '♡' }}</span>
            <span>{{ in_array($item['source_id'], $savedIds ?? [], true) ? 'Đã lưu' : 'Quan tâm' }}</span>
        </button>
    </form>
@endif

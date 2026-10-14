@extends('Admin::layouts.master')

@section('title', 'Thiết lập đặt hàng nhanh Pharma')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-semibold text-gray-900">Đặt hàng nhanh Pharma</h1><a href="{{ route('admin.website.pharma-quick-order.requests.index') }}" class="rounded-lg border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700">Xem yêu cầu đặt hàng</a></div>
    <p class="mt-2 text-sm text-gray-600">Chỉ bảng giá đang hoạt động và được chọn tại đây mới có thể trở thành nguồn đặt hàng. Quyền người dùng và phạm vi khách hàng vẫn phải được kiểm tra ở phía máy chủ.</p>

    @if(session('success'))
        <div role="status" class="mt-4 rounded-lg bg-green-50 p-3 text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.website.pharma-quick-order.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="enabled" value="0">
        <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4">
            <input type="checkbox" name="enabled" value="1" @checked((bool) old('enabled', $configuration['enabled'])) class="h-5 w-5 rounded border-gray-300">
            <span class="font-medium">Bật đặt hàng nhanh Pharma</span>
        </label>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="font-semibold">Nguồn bảng giá được phép sử dụng</h2>
            <p class="mt-1 text-sm text-gray-500">Bảng giá hết hiệu lực theo ngày sẽ tự bị loại tại thời điểm đặt hàng. Nếu chưa chọn bảng giá, hệ thống không trả về sản phẩm để đặt hàng.</p>
            <div class="mt-4 max-h-[460px] space-y-2 overflow-y-auto">
                @forelse($priceLists as $priceList)
                    @php($active = $priceList->status === \Modules\Pharma\Models\PriceList::STATUS_ACTIVE)
                    <label class="flex gap-3 rounded-lg border border-gray-100 p-3 {{ $active ? '' : 'opacity-50' }}">
                        <input type="checkbox" name="price_list_ids[]" value="{{ $priceList->id }}"
                            @checked(in_array($priceList->id, array_map('intval', old('price_list_ids', $configuration['price_list_ids'])), true))
                            @disabled(!$active) class="mt-1 h-4 w-4">
                        <span class="text-sm">
                            <strong>{{ $priceList->code }} — {{ $priceList->name }}</strong>
                            <span class="block text-gray-500">{{ $priceList->type }} · {{ $priceList->status }} · {{ $priceList->effective_from?->format('d/m/Y') ?? 'Không giới hạn' }} – {{ $priceList->effective_to?->format('d/m/Y') ?? 'Không giới hạn' }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Chưa có bảng giá Pharma.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="font-semibold text-gray-900">Danh sách email nhận yêu cầu đặt hàng mới</h2>
            <p class="mt-1 text-sm text-gray-500">Tối đa 10 người nhận. Mỗi email một dòng; thông báo được gửi qua queue. Để trống nếu không cần thông báo.</p>
            @php($emailRows = old('notification_emails', app(\Modules\Website\Services\PharmaQuickOrderSettingsService::class)->parseNotificationEmails($configuration['notification_email'] ?? '')))
            @php($emailRows = array_slice(is_array($emailRows) && count($emailRows) ? $emailRows : [''], 0, 10))
            <div id="quick-order-email-list" class="mt-3 space-y-2">
                @foreach($emailRows as $email)
                    <div data-email-row class="flex items-center gap-2">
                        <input type="email" name="notification_emails[]" value="{{ $email }}" maxlength="254" autocomplete="off"
                            aria-label="Email người nhận {{ $loop->iteration }}" placeholder="nguoinhan@example.com"
                            class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        <button type="button" data-remove-email aria-label="Xóa email" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50">Xóa</button>
                    </div>
                @endforeach
            </div>
            <button id="quick-order-add-email" type="button" class="mt-3 rounded-lg border border-blue-300 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">+ Thêm email</button>
            <p id="quick-order-email-count" class="mt-2 text-xs text-gray-500"></p>
            @error('notification_emails')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            @error('notification_emails.*')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-medium text-white hover:bg-blue-700 active:scale-[0.985]">Lưu thiết lập</button>
    </form>
</div>
@push('scripts')
<script>
(() => {
    const list = document.getElementById('quick-order-email-list');
    const add = document.getElementById('quick-order-add-email');
    const count = document.getElementById('quick-order-email-count');
    if (!list || !add) return;
    const update = () => {
        const total = list.querySelectorAll('[data-email-row]').length;
        add.disabled = total >= 10;
        add.classList.toggle('opacity-50', add.disabled);
        count.textContent = total + '/10 người nhận';
    };
    add.addEventListener('click', () => {
        if (list.querySelectorAll('[data-email-row]').length >= 10) return;
        const row = document.createElement('div');
        row.dataset.emailRow = '';
        row.className = 'flex items-center gap-2';
        const input = document.createElement('input');
        input.type = 'email';
        input.name = 'notification_emails[]';
        input.maxLength = 254;
        input.autocomplete = 'off';
        input.placeholder = 'nguoinhan@example.com';
        input.setAttribute('aria-label', 'Email người nhận mới');
        input.className = 'min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.removeEmail = '';
        remove.setAttribute('aria-label', 'Xóa email');
        remove.className = 'rounded-lg border border-gray-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50';
        remove.textContent = 'Xóa';
        row.append(input, remove);
        list.append(row);
        update();
        input.focus();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-email]');
        if (!button) return;
        const row = button.closest('[data-email-row]');
        if (row) row.remove();
        update();
    });
    update();
})();
</script>
@endpush
@endsection

<div class="space-y-5">
    @if ($message)<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">{{ $message }}</div>@endif
    @if ($error)<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">{{ $error }}</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">1. Kết nối GDT</h2>
            <p class="mt-1 text-sm text-gray-500">Thông tin đăng nhập chỉ dùng cho phiên test hiện tại; mật khẩu không được lưu vào cấu hình hệ thống.</p>
        </div>
        @if ($connectedTaxCode)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-sm text-gray-500">Đang kết nối MST</p><p class="font-semibold text-emerald-700">{{ $connectedTaxCode }}</p></div>
                <button type="button" wire:click="disconnect" class="h-10 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đổi công ty / đăng nhập lại</button>
            </div>
        @else
            <form wire:submit="connect" class="grid gap-4 p-5 md:grid-cols-2">
                <div><label class="text-sm font-medium text-gray-700">Mã số thuế</label><input type="text" wire:model="taxCode" autocomplete="username" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">@error('taxCode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-gray-700">Mật khẩu</label><input type="password" wire:model="password" autocomplete="current-password" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">@error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div>
                    <div class="flex items-center justify-between"><label class="text-sm font-medium text-gray-700">Captcha</label><button type="button" wire:click="refreshCaptcha" class="text-xs font-semibold text-indigo-600">Tải mã mới</button></div>
                    <div class="mt-1 flex min-h-14 items-center rounded-xl border border-gray-200 bg-gray-50 px-3">@if($captchaSvg){!! $captchaSvg !!}@else<span class="text-xs text-gray-400">Chưa tải được captcha</span>@endif</div>
                </div>
                <div><label class="text-sm font-medium text-gray-700">Mã xác nhận</label><input type="text" wire:model="captchaValue" autocomplete="off" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">@error('captchaValue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2"><button class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50" wire:loading.attr="disabled" wire:target="connect"><span wire:loading.remove wire:target="connect">Kết nối GDT</span><span wire:loading wire:target="connect">Đang kết nối…</span></button></div>
            </form>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">2. Đồng bộ thành Excel</h2></div>
        <form wire:submit="sync" class="grid gap-4 p-5 md:grid-cols-4">
            <div><label class="text-sm font-medium text-gray-700">Loại hóa đơn</label><select wire:model="invoiceType" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-3 text-sm"><option value="sold">Đầu ra / Bán ra</option><option value="purchase">Đầu vào / Mua vào</option></select></div>
            <div><label class="text-sm font-medium text-gray-700">Từ ngày</label><input type="date" wire:model="fromDate" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-3 text-sm">@error('fromDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-gray-700">Đến ngày</label><input type="date" wire:model="toDate" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-3 text-sm">@error('toDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div class="flex items-end"><button @disabled(!$connectedTaxCode) class="h-11 w-full rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40" wire:loading.attr="disabled" wire:target="sync"><span wire:loading.remove wire:target="sync">Đồng bộ Excel</span><span wire:loading wire:target="sync">Đang đồng bộ…</span></button></div>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">File test đã lưu trên server</h2><p class="mt-1 text-sm text-gray-500">Tách riêng theo user quản trị và MST công ty.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">MST</th><th class="px-5 py-3">Tên file</th><th class="px-5 py-3">Thời gian</th><th class="px-5 py-3 text-right">Tải</th></tr></thead>
            <tbody class="divide-y divide-gray-100">@forelse($files as $file)<tr><td class="px-5 py-3 font-medium">{{ $file['tax_code'] }}</td><td class="px-5 py-3">{{ $file['filename'] }}</td><td class="px-5 py-3 text-gray-500">{{ $file['modified_at'] }}</td><td class="px-5 py-3 text-right"><button type="button" wire:click="download(@js($file['tax_code']), @js($file['filename']))" class="font-semibold text-indigo-600 hover:text-indigo-800">Download</button></td></tr>@empty<tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">Chưa có file test.</td></tr>@endforelse</tbody></table>
        </div>
    </section>
</div>

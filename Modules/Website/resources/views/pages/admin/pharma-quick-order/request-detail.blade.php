@extends('Admin::layouts.master')
@section('title', 'Chi tiết yêu cầu đặt hàng Pharma')
@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.website.pharma-quick-order.requests.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Danh sách yêu cầu</a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pharma / Xét duyệt</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Yêu cầu #{{ str_pad($quickOrderRequest->id, 4, '0', STR_PAD_LEFT) }}</h1>
            <p class="mt-1 text-sm text-slate-500">Gửi lúc {{ $quickOrderRequest->submitted_at?->format('d/m/Y H:i') }}</p>
        </div>
        <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $quickOrderRequest->status === 'accepted' ? 'bg-emerald-50 text-emerald-700' : ($quickOrderRequest->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$quickOrderRequest->status] ?? $quickOrderRequest->status }}</span>
    </header>
    @if(session('success'))<div role="status" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,7fr)_minmax(300px,3fr)]">
        <div class="min-w-0 space-y-5">
            <section class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-4"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">◈</span><h2 class="text-base font-bold text-slate-900">Thông tin người mua và nhận hàng</h2></div>
                <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Loại người mua</dt><dd class="mt-1 font-medium text-slate-900">{{ $quickOrderRequest->buyer_type === 'company' ? 'Doanh nghiệp / cơ sở' : 'Cá nhân' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Doanh nghiệp / cơ sở</dt><dd class="mt-1 font-medium text-slate-900">{{ $quickOrderRequest->company_name ?: 'Chưa cung cấp' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Mã số thuế</dt><dd class="mt-1 font-medium text-slate-900">{{ $quickOrderRequest->tax_code ?: 'Chưa cung cấp' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Người nhận</dt><dd class="mt-1 font-medium text-slate-900">{{ $quickOrderRequest->recipient_name ?: 'Chưa cung cấp' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Số điện thoại</dt><dd class="mt-1 font-medium text-slate-900">{{ $quickOrderRequest->phone ?: 'Chưa cung cấp' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Địa chỉ nhận hàng</dt><dd class="mt-1 whitespace-pre-wrap font-medium text-slate-900">{{ $quickOrderRequest->shipping_address ?: 'Chưa cung cấp' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Ghi chú</dt><dd class="mt-1 whitespace-pre-wrap text-slate-700">{{ $quickOrderRequest->notes ?: 'Không có' }}</dd></div>
                </dl>
            </section>
            <section class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4"><h2 class="text-base font-bold text-slate-900">Danh sách thuốc yêu cầu</h2><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ count($lines) }} sản phẩm</span></div>
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">Đơn giá (Có VAT) và thành tiền cần được xác minh theo bảng giá hợp lệ trước khi lập phiếu xuất kho.</p>
                <div class="mt-2 divide-y divide-slate-100">
                    @foreach($lines as $line)
                        <div class="flex items-center gap-3 py-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">Thuốc</div>
                            <div class="min-w-0 flex-1"><p class="text-sm font-semibold text-slate-900">{{ $line['title'] }}</p><p class="mt-1 text-xs text-slate-500">Website #{{ $line['product_id'] }} · Đơn giá (Có VAT): Cần kiểm tra</p>
                                @if($line['missing'] || !$line['has_pharma_link'])<p class="mt-1 text-xs font-medium text-rose-700">Thiếu sản phẩm hoặc liên kết Pharma — cần kiểm tra.</p>@endif
                            </div>
                            <span class="shrink-0 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold tabular-nums text-emerald-800">× {{ $line['quantity'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">Lịch sử xét duyệt</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse($reviews as $review)
                        <div class="py-3 text-sm"><p class="font-medium text-slate-800">{{ \Illuminate\Support\Carbon::parse($review->created_at)->format('d/m/Y H:i') }} · Admin #{{ $review->admin_id }}</p><p class="mt-1 text-xs text-slate-500">{{ $review->from_status }} → {{ $review->to_status }}</p><p class="mt-2 whitespace-pre-wrap text-slate-700">{{ $review->note }}</p></div>
                    @empty
                        <p class="py-3 text-sm text-slate-500">Chưa có thao tác xét duyệt.</p>
                    @endforelse
                </div>
            </section>
        </div>
        <aside class="min-w-0 lg:sticky lg:top-5">
            <section class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">Xét duyệt yêu cầu</h2>
                <p class="mt-2 text-xs leading-5 text-slate-500">Kiểm tra tư cách người mua, điều kiện mua dược phẩm và thông tin sản phẩm. Chấp nhận yêu cầu chưa đồng nghĩa được xuất kho.</p>
                @if($quickOrderRequest->status === 'pending_review')
                    <form method="POST" action="{{ route('admin.website.pharma-quick-order.requests.accept-and-draft', $quickOrderRequest) }}" class="mt-4 space-y-4">
                        @csrf
                        <div><label for="quick-order-review-note" class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú xét duyệt <span class="text-rose-600">*</span></label><textarea id="quick-order-review-note" name="note" required maxlength="2000" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500">{{ old('note') }}</textarea></div>
                        <div class="space-y-3 rounded-lg bg-slate-50 p-3">
                            <p class="text-xs font-semibold text-slate-700">Thông tin lập phiếu xuất nháp</p>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Khách hàng Pharma</label>
                                <x-select-search id="quick-order-customer-select" name="recipient_partner_id" placeholder="Tìm khách hàng Pharma..." required>
                                    <option value="">Chọn khách hàng</option>
                                    @foreach($handoffCustomers as $customer)<option value="{{ $customer->id }}" @selected(old('recipient_partner_id') == $customer->id)>{{ $customer->name }} · {{ $customer->tax_code }}</option>@endforeach
                                </x-select-search>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Người phụ trách</label>
                                <x-select-search id="quick-order-manager-select" name="manager_user_id" placeholder="Tìm người phụ trách..." required>
                                    <option value="">Chọn người phụ trách</option>
                                    @foreach($handoffManagers as $manager)<option value="{{ $manager->id }}" @selected(old('manager_user_id') == $manager->id)>{{ $manager->name }}</option>@endforeach
                                </x-select-search>
                            </div>
                            <p class="text-xs text-slate-500">Bảng giá và đơn giá được đối chiếu tự động theo cấu hình đặt hàng nhanh. Nếu có nhiều bảng giá trùng khớp, hệ thống sẽ yêu cầu xử lý cấu hình.</p>
                        </div>
                        <label class="flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-700"><input type="checkbox" name="eligibility_confirmed" value="1" class="mt-1 rounded border-slate-300"><span>Tôi đã kiểm tra thông tin người mua và xác nhận đủ điều kiện tiếp nhận yêu cầu mua dược phẩm.</span></label>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <button type="submit" name="decision" value="accepted" onclick="return confirm('Xác nhận chấp nhận yêu cầu sau khi kiểm tra điều kiện mua thuốc?')" class="rounded-lg bg-emerald-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Chấp nhận & tạo phiếu xuất nháp</button>
                            <button type="submit" name="decision" value="rejected" formnovalidate formaction="{{ route('admin.website.pharma-quick-order.requests.review', $quickOrderRequest) }}" onclick="return confirm('Xác nhận từ chối yêu cầu này?')" class="rounded-lg border border-rose-200 bg-white px-3 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Từ chối</button>
                        </div>
                    </form>
                @else
                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Yêu cầu đã được xét duyệt. Không thể thực hiện lại thao tác.</div>
                @endif
            </section>

            @if($quickOrderRequest->inventory_issue_id)
                <div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">
                    <p class="font-semibold">Đã liên kết phiếu xuất #{{ $quickOrderRequest->inventory_issue_id }}</p>
                    <a href="{{ url('/admin/pharma/inventory/issues/'.$quickOrderRequest->inventory_issue_id) }}" class="mt-2 inline-block underline">Mở phiếu xuất</a>
                </div>
            @endif

            @if(!$quickOrderRequest->inventory_issue_id)
                <section class="mt-4 space-y-3 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Quản lý yêu cầu</h2>
                    <p class="text-xs text-slate-500">Thao tác có lưu lịch sử. Yêu cầu đã liên kết phiếu xuất phải được xử lý tại phân hệ Kho.</p>
                    @if(in_array($quickOrderRequest->status, ['accepted', 'rejected'], true))
                        <form method="POST" action="{{ route('admin.website.pharma-quick-order.requests.revert', $quickOrderRequest) }}" class="space-y-2">
                            @csrf
                            <label for="revert-reason" class="block text-xs font-semibold text-slate-700">Lý do hoàn tác</label>
                            <textarea id="revert-reason" name="reason" required minlength="3" maxlength="2000" rows="2" class="w-full rounded-lg border border-slate-200 p-2 text-sm"></textarea>
                            <button onclick="return confirm('Đưa yêu cầu về Chờ xét duyệt?')" class="rounded-lg border border-amber-200 px-3 py-2 text-sm font-semibold text-amber-800">Hoàn tác trạng thái</button>
                        </form>
                    @endif
                    @if(in_array($quickOrderRequest->status, ['pending_review', 'rejected'], true))
                        <form method="POST" action="{{ route('admin.website.pharma-quick-order.requests.destroy', $quickOrderRequest) }}" class="space-y-2">
                            @csrf
                            @method('DELETE')
                            <label for="delete-reason" class="block text-xs font-semibold text-slate-700">Lý do xóa mềm</label>
                            <textarea id="delete-reason" name="reason" required minlength="3" maxlength="2000" rows="2" class="w-full rounded-lg border border-slate-200 p-2 text-sm"></textarea>
                            <button onclick="return confirm('Xóa mềm yêu cầu này? Lịch sử vẫn được giữ lại.')" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Xóa yêu cầu</button>
                        </form>
                    @endif
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection

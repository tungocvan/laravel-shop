@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Thông tin sản phẩm')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950"><span aria-hidden="true">←</span> Danh mục thuốc</a>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Product Intelligence</p>
                <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $product->brandName }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-300">{{ $product->activeIngredients ?: 'Chưa có hoạt chất' }}@if($product->strength) · {{ $product->strength }}@endif</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if($awards->isNotEmpty())<span class="rounded-full bg-blue-500/20 px-3 py-1 text-xs font-bold text-blue-100 ring-1 ring-inset ring-blue-400/40">Trúng thầu</span>@endif
                    @if($profile)<span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-100 ring-1 ring-inset ring-emerald-400/40">HSSP</span>@endif
                    @if($supplierPricingVisible && $suppliers->contains(fn ($supplier) => array_key_exists('import_price', $supplier) && $supplier['import_price'] !== null))<span class="rounded-full bg-amber-400/20 px-3 py-1 text-xs font-bold text-amber-100 ring-1 ring-inset ring-amber-300/40">Giá NCC</span>@endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-white/10 px-3 py-1.5">{{ $medicine['medicine_code'] ?: 'Chưa có mã thuốc' }}</span>
                <span class="rounded-full bg-white/10 px-3 py-1.5">{{ $product->registrationNumber ?: 'Chưa có GPLH' }}</span>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">01 · Sản phẩm</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin cơ bản</h2></div>
        <dl class="grid gap-x-7 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Hoạt chất', $product->activeIngredients],
                ['Hàm lượng', $product->strength],
                ['Dạng bào chế', $product->dosageForm],
                ['Đường dùng', $product->route],
                ['Đơn vị tính', $product->unit],
                ['Quy cách', $product->packaging],
                ['Nhà sản xuất', $product->manufacturer],
                ['Nước sản xuất', $medicine['manufacturing_country']],
                ['Công ty đăng ký', $medicine['registered_company']],
                ['Hạn dùng', $medicine['shelf_life']],
                ['Nhóm thông tư', $medicine['circular_group']],
                ['Nhóm điều trị', $medicine['therapeutic_group']],
            ] as [$label, $value])
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 text-sm font-semibold leading-6 text-slate-800">{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">02 · Hồ sơ</p><h2 class="mt-1 text-lg font-black text-slate-950">Hồ sơ sản phẩm</h2></div>
            @if($profile)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $profile['status'] ?: 'Chưa xác định' }}</span>@endif
        </div>
        @if($profile)
            <dl class="grid gap-x-7 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Phiên bản', $profile['version']],
                    ['Nguồn', $profile['source']],
                    ['Hiệu lực từ', $profile['effective_from']?->format('d/m/Y')],
                    ['Hiệu lực đến', $profile['effective_to']?->format('d/m/Y')],
                    ['Xác minh lúc', $profile['verified_at']?->format('d/m/Y H:i')],
                    ['GPLH hiệu lực đến', $medicine['visa_validity_date']?->format('d/m/Y')],
                ] as [$label, $value])
                    <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
            @if($profile['link'])<a href="{{ $profile['link'] }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Mở hồ sơ ↗</a>@endif
            @if($profile['notes'])<p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $profile['notes'] }}</p>@endif
        @else
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Sản phẩm này chưa có hồ sơ hiện hành.</p>
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="Thông tin trúng thầu gần đây">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">03 · Thầu</p>
            <h2 class="mt-1 text-lg font-black text-slate-950">Thông tin trúng thầu gần đây</h2>
            <p class="mt-2 text-sm text-slate-500">Bao gồm dữ liệu trúng thầu chính thức và nguồn công khai đã đồng bộ. Nguồn công khai chỉ để tham khảo, không thay đổi giá bán, giá nhập hoặc tồn kho.</p>
        </div>
        @forelse($awards as $award)
            @php $isPublic = ($award['origin'] ?? 'canonical') === 'public_tender'; @endphp
            <article class="border-t border-slate-100 py-4 first:border-t-0 first:pt-0">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $isPublic ? 'bg-cyan-50 text-cyan-800' : 'bg-slate-100 text-slate-700' }}">{{ $isPublic ? 'Nguồn công khai · Chưa xác minh nghiệp vụ' : 'Trúng thầu chính thức' }}</span>
                    @if($award['is_default'])<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">★ Mặc định cho Excel</span>@endif
                    @if($isPublic && $award['synced_at'])
                        <span class="text-xs text-slate-500">Đồng bộ {{ $award['synced_at']->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['TBMT', $award['bidding_notice_code']],
                        ['Chủ đầu tư / bên mời thầu', $award['investor_name']],
                        ['Số quyết định', $award['decision_number']],
                        ['Ngày quyết định', $award['decision_date']],
                        ['Đơn giá trúng thầu', is_numeric($award['unit_price']) ? number_format((float) $award['unit_price'], 0, ',', '.') . ' đ' : null],
                        ['Đơn vị trúng thầu', $award['winning_company_name']],
                        ['Số lượng', is_numeric($award['quantity']) ? number_format((float) $award['quantity'], 0, ',', '.') : null],
                        ['Lô', $award['lot_no']],
                    ] as [$label, $value])
                        <div class="min-w-0">
                            <dt class="text-xs font-bold text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ is_array($value) ? (implode('; ', array_filter($value, 'is_scalar')) ?: '—') : (is_scalar($value) && trim((string) $value) !== '' ? $value : '—') }}</dd>
                        </div>
                    @endforeach
                    @if($isPublic)
                        @foreach([
                            ['Ngày đăng KQLCNT', $award['published_at']],
                            ['Mã nhà thầu trúng', $award['winning_code']],
                            ['Đơn vị tính', $award['unit']],
                            ['Quy cách đóng gói', $award['packaging']],
                            ['Cơ sở sản xuất', $award['manufacturer']],
                            ['Nước sản xuất', $award['manufacturing_country']],
                            ['GĐKLH / GPNK', $award['registration_number']],
                            ['Mã bản ghi nguồn', $award['source_record_key']],
                        ] as [$label, $value])
                            <div class="min-w-0">
                                <dt class="text-xs font-bold text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 break-words text-sm font-semibold text-slate-800">{{ is_array($value) ? (implode('; ', array_filter($value, 'is_scalar')) ?: '—') : (is_scalar($value) && trim((string) $value) !== '' ? $value : '—') }}</dd>
                            </div>
                        @endforeach
                        <div class="min-w-0">
                            <dt class="text-xs font-bold text-slate-500">Trạng thái số đăng ký</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $award['registration_verified'] ? 'Đã xác minh định dạng' : 'Cần kiểm tra' }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-slate-500">Hiệu lực thời gian thầu</p>
                            <p class="mt-1 break-words text-sm font-semibold text-slate-800">{{ $award['tender_validity_text'] ?: ($award['contract_period_text'] ?: 'Chưa có thông tin') }}</p>
                            @if($award['tender_validity_text'])
                                <p class="mt-1 text-xs text-amber-700">Nhập thủ công · Không phải dữ liệu xác nhận từ API</p>
                            @endif
                        </div>
                        @if($canManageAwards)
                            <button type="button" class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700" aria-label="Sửa hiệu lực thời gian thầu" aria-expanded="false"
                                onclick="const panel=this.closest('.rounded-xl').querySelector('[data-validity-editor]'); panel.hidden=!panel.hidden; this.setAttribute('aria-expanded', String(!panel.hidden));">
                                ✎ Sửa
                            </button>
                        @endif
                    </div>
                    @if($canManageAwards)
                        <form data-validity-editor hidden method="POST" action="{{ route('client.pharma.products.award-preferences.update', $medicineId) }}" class="mt-3 space-y-2 border-t border-slate-200 pt-3">
                            @csrf
                            <input type="hidden" name="origin" value="{{ $award['origin'] }}">
                            <input type="hidden" name="record_id" value="{{ $award['record_id'] }}">
                            <input type="hidden" name="action" value="validity">
                            <label class="block text-xs font-bold text-slate-600" for="award-validity-{{ $award['origin'] }}-{{ $award['record_id'] }}">Hiệu lực thời gian thầu (nhập thủ công)</label>
                            <input id="award-validity-{{ $award['origin'] }}-{{ $award['record_id'] }}" name="tender_validity_text" type="text" maxlength="255"
                                value="{{ $award['tender_validity_text'] ?? '' }}" placeholder="Ví dụ: 12 tháng hoặc 01/01/2026 - 31/12/2026"
                                class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <p class="text-xs text-slate-500">Để trống và lưu để xóa giá trị thủ công. Không thay đổi dữ liệu trúng thầu gốc.</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="min-h-11 rounded-lg bg-slate-900 px-4 text-sm font-bold text-white">Lưu</button>
                                <button type="button" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700"
                                    onclick="const panel=this.closest('[data-validity-editor]'); panel.hidden=true; const toggle=panel.parentElement.querySelector('[aria-expanded]'); if(toggle) toggle.setAttribute('aria-expanded','false');">Hủy</button>
                            </div>
                        </form>
                    @endif
                </div>
                @if($canManageAwards)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @unless($award['is_default'])
                            <form method="POST" action="{{ route('client.pharma.products.award-preferences.update', $medicineId) }}">
                                @csrf
                                <input type="hidden" name="origin" value="{{ $award['origin'] }}">
                                <input type="hidden" name="record_id" value="{{ $award['record_id'] }}">
                                <input type="hidden" name="action" value="default">
                                <button type="submit" class="min-h-11 rounded-xl border border-amber-300 bg-amber-50 px-4 text-sm font-bold text-amber-900">★ Đặt mặc định</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('client.pharma.products.award-preferences.update', $medicineId) }}" onsubmit="return confirm('Gỡ bản ghi khỏi danh mục thuốc? Dữ liệu gốc vẫn được giữ và có thể khôi phục.');">
                            @csrf
                            <input type="hidden" name="origin" value="{{ $award['origin'] }}">
                            <input type="hidden" name="record_id" value="{{ $award['record_id'] }}">
                            <input type="hidden" name="action" value="hide">
                            <button type="submit" class="min-h-11 rounded-xl border border-rose-200 px-4 text-sm font-bold text-rose-700">Gỡ bỏ</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có thông tin trúng thầu được liên kết với sản phẩm này.</p>
        @endforelse
        @if($canManageAwards && $hiddenAwards->isNotEmpty())
            <details class="mt-4 rounded-2xl border border-slate-200 p-4">
                <summary class="cursor-pointer text-sm font-bold text-slate-700">Bản ghi đã gỡ ({{ $hiddenAwards->count() }}) · Khôi phục</summary>
                <div class="mt-3 space-y-3">
                    @foreach($hiddenAwards as $award)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <span class="text-sm text-slate-700">{{ $award['bidding_notice_code'] ?: 'Không có TBMT' }} · {{ is_numeric($award['unit_price']) ? number_format((float) $award['unit_price'], 0, ',', '.') . ' đ' : '—' }}</span>
                            <form method="POST" action="{{ route('client.pharma.products.award-preferences.update', $medicineId) }}">
                                @csrf
                                <input type="hidden" name="origin" value="{{ $award['origin'] }}">
                                <input type="hidden" name="record_id" value="{{ $award['record_id'] }}">
                                <input type="hidden" name="action" value="restore">
                                <button type="submit" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold">Khôi phục</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </section>

    @if($supplierPricingVisible)
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">04 · Nhà cung cấp</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin nhà cung cấp</h2></div>
        </div>
        @forelse($suppliers as $supplier)
            <article class="grid gap-4 border-t border-slate-100 py-4 first:border-t-0 first:pt-0 sm:grid-cols-2 {{ $supplierPricingVisible ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }}">
                <div><p class="text-xs font-bold text-slate-400">Nhà cung cấp</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $supplier['supplier_name'] ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Ngày làm việc</p><p class="mt-1 text-sm text-slate-700">{{ $supplier['working_date']?->format('d/m/Y') ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Hiệu lực</p><p class="mt-1 text-sm text-slate-700">{{ $supplier['start_date']?->format('d/m/Y') ?: '—' }} → {{ $supplier['end_date']?->format('d/m/Y') ?: '—' }}</p></div>
                @if($supplierPricingVisible)
                    <div><p class="text-xs font-bold text-slate-400">Giá thu NCC</p><p class="mt-1 text-sm font-black tabular-nums text-slate-950">{{ isset($supplier['import_price']) ? number_format((float) $supplier['import_price'], 0, ',', '.') : '—' }}</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá xuất HĐ NCC</p><p class="mt-1 text-sm font-black tabular-nums text-slate-950">{{ isset($supplier['invoice_price']) ? number_format((float) $supplier['invoice_price'], 0, ',', '.') : '—' }}</p></div>
                @endif
            </article>
        @empty
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có nhà cung cấp được liên kết với sản phẩm này.</p>
        @endforelse
    </section>
    @endif
</div>
@endsection

<div class="space-y-5" @if ($syncId && in_array($syncState, ['queued', 'processing'], true)) wire:poll.2s="pollStatus" @endif>

    <div wire:loading.flex wire:target="backupFilesToEmail" class="fixed inset-0 z-[120] items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Đang tạo và gửi backup qua Email</h3>
            <p class="mt-2 text-sm text-slate-500">Hệ thống đang nén kho file và có thể chia thành nhiều email nếu dung lượng lớn. Vui lòng không refresh trang.</p>
        </div>
    </div>

    @if($posCaptchaModalOpen && $invoiceSource === 'pos')
        <div class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/60 px-4 py-6" role="presentation">
            <div role="dialog" aria-modal="true" aria-labelledby="pos-captcha-title" class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                <h3 id="pos-captcha-title" class="text-lg font-semibold text-slate-900">Xác minh CAPTCHA GDT</h3>
                <p class="mt-2 text-sm text-slate-600">{{ in_array($posCaptchaPurpose, ['sync', 'sync_page2'], true) ? ($posCaptchaPurpose === 'sync_page2' ? 'CAPTCHA mới cho trang 2 đồng bộ POS; chỉ ghi canonical khi đủ hóa đơn.' : 'Xác nhận CAPTCHA mới để đồng bộ canonical POS.') : ($posCaptchaPurpose === 'page2' ? 'Nhập CAPTCHA mới để tiếp tục trang 2 từ checkpoint đã xác minh.' : 'Nhập CAPTCHA để hoàn tất tìm kiếm POS.') }}</p>
                <div class="mt-4 space-y-3">
                    @if($posCaptchaImage)
                        <img src="{{ $posCaptchaImage }}" alt="CAPTCHA GDT" width="200" height="50" class="max-w-full rounded-lg border border-slate-200 bg-white p-1">
                    @else
                        <p class="text-sm text-amber-800">Chưa tải được CAPTCHA. Vui lòng tải lại.</p>
                    @endif
                    <button type="button" wire:click="refreshPosCaptcha" wire:loading.attr="disabled" wire:target="refreshPosCaptcha" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-800">Tải lại CAPTCHA</button>
                    <label class="block text-sm font-medium text-slate-700">Mã xác minh
                        <input type="text" wire:model="posCaptchaAnswer" autocomplete="off" maxlength="20" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-3" placeholder="Nhập ký tự trong ảnh">
                    </label>
                    @if($posCaptchaError)<p role="alert" class="text-sm text-red-700">{{ $posCaptchaError }}</p>@endif
                    @error('posCaptchaAnswer')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closePosCaptcha" class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-medium">Hủy</button>
                    <button type="button" wire:click="submitPosCaptcha" wire:loading.attr="disabled" wire:target="submitPosCaptcha" class="min-h-11 rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white disabled:opacity-50">Xác nhận {{ $posCaptchaPurpose === 'sync' ? 'và đồng bộ' : 'và tra cứu' }}</button>
                </div>
            </div>
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Bước 1</p>
            <h2 class="mt-1 text-lg font-semibold text-gray-900">Dashboard đồng bộ hóa đơn GDT</h2>
            <p class="mt-1 text-sm text-gray-500">Chọn nguồn, xác minh nếu cần và theo dõi kết quả riêng của Regular / POS. Số lượng chỉ xuất hiện khi có kết quả truy vấn thực tế.</p>
        </div>
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Nguồn hóa đơn GDT">
                <button type="button" role="tab" aria-selected="{{ $invoiceSource === 'regular' ? 'true' : 'false' }}" wire:click="$set('invoiceSource', 'regular')" class="min-h-14 rounded-lg px-3 py-3 text-sm font-semibold transition {{ $invoiceSource === 'regular' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/60' }}">Hóa đơn thông thường <span class="hidden text-xs font-normal sm:inline">· Regular</span></button>
                <button type="button" role="tab" aria-selected="{{ $invoiceSource === 'pos' ? 'true' : 'false' }}" wire:click="$set('invoiceSource', 'pos')" class="min-h-14 rounded-lg px-3 py-3 text-sm font-semibold transition {{ $invoiceSource === 'pos' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/60' }}">Hóa đơn máy tính tiền <span class="hidden text-xs font-normal sm:inline">· POS</span></button>
            </div>
            <div class="mt-5 flex flex-wrap items-start justify-between gap-2">
                <div><h3 class="text-base font-semibold text-slate-900">{{ $invoiceSource === 'pos' ? 'Tra cứu hóa đơn khởi tạo từ máy tính tiền' : 'Tra cứu hóa đơn thông thường' }}</h3><p class="mt-1 text-sm text-slate-500">Tìm kiếm chỉ đọc dữ liệu GDT; đồng bộ canonical là bước ghi dữ liệu riêng.</p></div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $invoiceSource === 'pos' ? 'CAPTCHA bắt buộc' : 'Hỗ trợ queue' }}</span>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div><label class="text-sm font-medium text-slate-700">Loại hóa đơn</label><select wire:model.live="vatIn" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="1">Mua vào</option><option value="0">Bán ra</option></select></div>
                <div><label class="text-sm font-medium text-slate-700">Từ ngày</label><input type="date" wire:model.live="start_date" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">@error('start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Đến ngày</label><input type="date" wire:model.live="end_date" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">@error('end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            @if($invoiceSource === 'pos')
                <p class="mt-4 text-sm text-amber-800">Tra cứu POS chỉ hỗ trợ một tháng dương lịch mỗi lần. Nhấn Tìm kiếm để mở CAPTCHA, nhập mã và xác nhận. Đồng bộ canonical cần CAPTCHA mới; không đưa mã vào queue.</p>
            @endif
            @error('invoiceSource')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">Bước 1: Tìm kiếm trên GDT · Bước 2: Đồng bộ canonical nếu kết quả đúng.</p>
                <button type="button" wire:click="{{ $invoiceSource === 'pos' ? 'openPosCaptcha' : 'searchInvoices' }}" wire:loading.attr="disabled" wire:target="openPosCaptcha,searchInvoices" class="min-h-11 rounded-xl bg-slate-900 px-7 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="openPosCaptcha,searchInvoices">Tìm kiếm hóa đơn</span><span wire:loading wire:target="openPosCaptcha,searchInvoices">Đang tìm kiếm…</span></button>
            </div>
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4" aria-live="polite">
                <div class="flex flex-wrap items-center justify-between gap-2"><h4 class="text-sm font-semibold text-slate-900">Kết quả tra cứu {{ $invoiceSource === 'pos' ? 'POS' : 'Regular' }}</h4><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $searchState === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($searchState === 'failed' ? 'bg-red-100 text-red-800' : 'bg-white text-slate-600') }}">{{ match($searchState) {'searching'=>'Đang tra cứu','completed'=>((int) ($searchPreview['count'] ?? 0) === 0 ? 'Không tìm thấy hóa đơn' : 'GDT đã phản hồi'),'failed'=>'Tra cứu thất bại',default=>'Chưa tìm kiếm'} }}</span></div>
                @if($invoiceSource === 'pos' && $searchState === 'awaiting_captcha')
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3" role="status">
                        <p class="text-sm font-semibold text-amber-900">Đã nhận {{ (int) ($searchPreview['count'] ?? 0) }}/{{ (int) ($searchPreview['total'] ?? 0) }} hóa đơn</p>
                        <p class="mt-1 text-xs text-amber-900">Cần CAPTCHA mới cho trang tiếp theo. Chưa ghi canonical. Checkpoint hết hạn sau 5 phút.</p>
                        <button type="button" wire:click="continuePosSearch" wire:loading.attr="disabled" wire:target="continuePosSearch" class="mt-3 min-h-11 rounded-lg bg-amber-800 px-4 py-2 text-sm font-semibold text-white">Tiếp tục trang 2</button>
                    </div>
                @endif
                @if($searchState === 'completed' && $invoiceSource === 'pos' && $posMultiPagePreview)
                    <p class="mt-2 text-sm text-amber-800" role="status">Đã xác minh đủ các trang để xem trước. Đồng bộ canonical sẽ yêu cầu CAPTCHA mới cho từng trang.</p>
                @endif
                @if($searchState === 'completed')
                    <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900">{{ number_format((int) ($searchPreview['count'] ?? 0)) }} <span class="text-sm font-normal text-slate-500">hóa đơn</span></p>
                    <p class="mt-1 text-sm text-slate-600">{{ $searchMessage }}</p>
                    @if(!empty($searchPreview['sample']))
                        <div class="mt-3 overflow-x-auto"><table class="min-w-full text-left text-xs"><thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 pr-3">Ký hiệu</th><th class="py-2 pr-3">Số hóa đơn</th><th class="py-2">Ngày lập</th></tr></thead><tbody>@foreach($searchPreview['sample'] as $invoice)<tr class="border-b border-slate-100"><td class="py-2 pr-3">{{ $invoice['symbol'] }}</td><td class="py-2 pr-3">{{ $invoice['number'] }}</td><td class="py-2">{{ $invoice['date'] }}</td></tr>@endforeach</tbody></table></div>
                        <p class="mt-2 text-xs text-slate-500">Xem trước tối đa 20 hóa đơn. Chưa ghi vào database.</p>
                    @endif
                @else
                    <p class="mt-2 text-sm {{ $searchState === 'failed' ? 'text-red-700' : 'text-slate-500' }}">{{ $searchMessage ?: 'Chọn bộ lọc và nhấn Tìm kiếm để xem kết quả thực tế.' }}</p>
                    @if($invoiceSource === 'pos' && $searchState === 'failed')
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3" role="status">
                            <p class="text-sm font-semibold text-amber-900">Tra cứu POS chưa hoàn tất</p>
                            <p class="mt-1 text-xs leading-5 text-amber-900">Không thể tiếp tục từ trang lỗi trong phiên này. Dữ liệu một phần chưa được xác nhận và không được đồng bộ canonical. Bạn có thể tra cứu lại bằng CAPTCHA mới hoặc chia khoảng ngày nhỏ hơn trong cùng tháng.</p>
                            <button type="button" wire:click="openPosCaptcha('search')" wire:loading.attr="disabled" wire:target="openPosCaptcha,refreshPosCaptcha,searchInvoices" class="mt-3 min-h-11 rounded-lg border border-amber-400 bg-white px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100 disabled:opacity-50">Nhập CAPTCHA mới và tra cứu lại</button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-3 border-t border-slate-100 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div><label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model.live="useQueue" @disabled($invoiceSource !== 'regular') class="rounded border-slate-300"> Xử lý qua queue</label><p class="mt-1 text-xs text-slate-500">{{ $invoiceSource === 'regular' ? 'Regular có thể xử lý qua queue.' : 'POS chỉ xử lý trực tiếp sau CAPTCHA.' }}</p></div>
            @if($completedCanonical = $this->completedCanonicalExport())
                <div class="w-full rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900" role="status">
                    <p class="font-semibold">Đã đồng bộ canonical hoàn tất — {{ basename($completedCanonical) }}</p>
                    <p class="mt-1">Hệ thống sẽ bỏ qua lần đồng bộ trùng, không yêu cầu CAPTCHA.</p>
                    @if(!$forceResync)
                        <button type="button" wire:click="confirmCanonicalResync" wire:confirm="Đồng bộ lại sẽ truy vấn GDT và cập nhật canonical/Excel đã hoàn tất. Bạn chắc chắn muốn tiếp tục?" class="mt-2 min-h-10 rounded-lg border border-emerald-700 px-3 py-2 font-semibold">Đồng bộ lại</button>
                    @else
                        <p class="mt-2 font-semibold text-amber-800">Đã xác nhận đồng bộ lại. Nhấn Đồng bộ canonical để thực hiện.</p>
                    @endif
                </div>
            @endif
            <button type="button" wire:click="{{ $invoiceSource === 'pos' ? 'openPosSyncCaptcha' : 'run' }}" wire:loading.attr="disabled" wire:target="openPosSyncCaptcha,run" @disabled($searchState !== 'completed' || (int) ($searchPreview['count'] ?? 0) <= 0 || $searchedFingerprint !== hash('sha256', implode('|', [$invoiceSource, $start_date, $end_date, (int) $vatIn]))) class="min-h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40"><span wire:loading.remove wire:target="run">Đồng bộ canonical</span><span wire:loading wire:target="run">Đang đồng bộ…</span></button>
        </div>
    </section>

    <section x-on:pos-sync-awaiting.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Bước 2 · Sức khỏe dữ liệu</p><h2 class="mt-1 text-base font-semibold text-gray-900">Trạng thái đồng bộ</h2><p class="mt-1 text-sm text-gray-500">Theo dõi đến khi hoàn tất, hoàn tất một phần hoặc thất bại.</p></div>
            @php($stateLabel=match($syncState){'queued'=>'Đang chờ queue','processing'=>'Đang xử lý','completed'=>'Hoàn tất','partial'=>'Hoàn tất một phần','failed'=>'Thất bại','awaiting_captcha'=>'Chờ CAPTCHA trang 2',default=>'Chưa chạy'})
            @php($stateClass=match($syncState){'completed'=>'bg-emerald-50 text-emerald-700','partial'=>'bg-amber-50 text-amber-800','failed'=>'bg-red-50 text-red-700','queued','processing','awaiting_captcha'=>'bg-amber-50 text-amber-700',default=>'bg-gray-100 text-gray-600'})
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $stateClass }}">{{ $stateLabel }}</span>
        </div>
    @if($invoiceSource === 'pos' && $posSyncPhase === 'awaiting_captcha')
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4" role="status">
            <p class="text-sm font-semibold text-amber-900">{{ $syncMessage }}</p>
            <button type="button" wire:click="continuePosSync" wire:loading.attr="disabled" wire:target="continuePosSync" class="mt-3 min-h-11 rounded-lg bg-amber-800 px-4 py-2 text-sm font-semibold text-white">Tiếp tục đồng bộ trang 2 · CAPTCHA mới</button>
        </div>
    @endif
        @if(in_array($syncState,['queued','processing'],true))<div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full w-2/3 animate-pulse rounded-full bg-indigo-500"></div></div>@endif
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                <p class="text-sm font-semibold text-slate-900">Kết quả xác minh theo nguồn</p>
                <p class="mt-1 text-xs text-slate-600">“Hoàn tất” chỉ mô tả tác vụ; kiểm tra số lượng và trạng thái từng nguồn để biết POS đã thực sự được tra cứu hay chưa.</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach(['regular'=>'Regular', 'pos'=>'POS'] as $key=>$label)
                        @php($metric=$sourceResults[$key] ?? null)
                        @php($state=$metric['state'] ?? 'pending')
                        <div class="rounded-lg border border-slate-200 bg-white p-3" wire:key="gdt-result-{{ $key }}">
                            <p class="text-xs font-semibold uppercase text-slate-600">{{ $label }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ match($state) {'completed'=>'Đã xử lý canonical','empty'=>'Tra cứu thành công · không có hóa đơn','partial'=>'Còn thiếu RAW detail','fetched'=>'Đã truy vấn GDT','failed'=>'Thất bại','skipped'=>'Không được chọn',default=>'Chưa xác minh'} }}</p>
                            <p class="mt-1 text-xs text-slate-500">GDT trả về: {{ isset($metric['count']) ? number_format($metric['count']).' hóa đơn' : 'chưa có số liệu' }}</p>
                        </div>
                    @endforeach
                </div>
                @if($detailResults !== null)
                    <p class="mt-3 text-xs text-slate-700">RAW detail toàn phiên: đã có {{ (int) ($detailResults['reused'] ?? 0) }} · tải mới {{ (int) ($detailResults['fetched'] ?? 0) }} · lỗi {{ (int) ($detailResults['failed'] ?? 0) }}. Các số này không tách riêng theo nguồn.</p>
                @endif
            </div>
            <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-gray-500">Kết quả</p><p class="mt-1 text-sm font-semibold {{ $syncState==='partial'?'text-amber-800':'text-gray-800' }}">{{ $syncMessage ?: 'Chưa có phiên đồng bộ mới.' }}</p></div>
            <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-gray-500">File export</p><p class="mt-1 truncate text-sm font-semibold {{ $syncFile?'text-emerald-700':'text-gray-500' }}">{{ $syncFile ?: 'Chưa tạo file' }}</p></div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Bước 3</p><h2 class="mt-1 text-base font-semibold text-gray-900">Kho file hóa đơn</h2><p class="mt-1 text-sm text-gray-500">Quản lý export/backup. Xóa file không xóa dữ liệu hóa đơn, RAW canonical, Inventory hoặc Partner.</p></div>
            <button wire:click="refreshAvailableFiles" class="h-10 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700">Làm mới</button>
        </div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ count($availableFiles) }} file</span><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">Đã chọn {{ count($selectedFiles) }}</span></div>
                <div class="flex flex-wrap gap-2"><button wire:click="selectAllAvailableFiles" @disabled(count($availableFiles)===0) class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 disabled:opacity-40">Chọn tất cả</button><button wire:click="clearFileSelection" @disabled(count($selectedFiles)===0) class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-600 disabled:opacity-40">Bỏ chọn</button></div>
            </div>

            <div class="mt-4 max-h-[28rem] space-y-2 overflow-y-auto pr-1">
                @forelse($availableFiles as $file)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                        <input type="checkbox" wire:model.live="selectedFiles" value="{{ $file['token'] }}" class="mt-1 rounded border-gray-300 text-indigo-600">
                        <span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-2"><span class="block truncate text-sm font-medium text-gray-800">{{ $file['name'] }}</span><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $file['direction']==='vat_in'?'bg-blue-50 text-blue-700':'bg-emerald-50 text-emerald-700' }}">{{ $file['type_label'] }}</span></span><span class="mt-1 block text-xs text-gray-500">{{ number_format($file['size']/1024,1) }} KB · {{ $file['modified_at'] }}</span></span>
                    </label>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Chưa có file XLSX/CSV đúng quy ước trong kho.</div>
                @endforelse
            </div>

            <div class="sticky bottom-3 z-10 mt-4 overflow-hidden rounded-2xl border border-indigo-100 bg-white/95 shadow-lg backdrop-blur sm:static sm:shadow-none">
                <div class="border-b border-gray-100 px-4 py-3 sm:px-5">
                    <p class="text-sm font-semibold text-gray-900">Thao tác với file đã chọn</p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">File tạo từ đồng bộ GDT canonical (bao gồm hóa đơn máy tính tiền) đã ghi database và KHÔNG cần Import lại. Chỉ dùng Import cho file bên ngoài hoặc dữ liệu legacy.</p>
                </div>
                <div class="grid gap-2 bg-gray-50/70 p-3 sm:grid-cols-3 sm:p-4">
                    <button wire:click="importSelectedFile" wire:confirm="Chỉ tiếp tục nếu đây là file upload thủ công, Google Drive hoặc dữ liệu legacy. File tạo trực tiếp từ GDT đã được ghi vào danh sách hóa đơn và không cần import lần nữa. Bạn có muốn tiếp tục?" wire:loading.attr="disabled" @disabled(count($selectedFiles)!==1) class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-center text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="importSelectedFile">Import vào danh sách hóa đơn</span><span wire:loading wire:target="importSelectedFile">Đang import…</span></button>
                    <button wire:click="downloadSelectedFile" wire:loading.attr="disabled" @disabled(count($selectedFiles)!==1) class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Download file</button>
                    <button wire:click="deleteSelectedFiles" wire:confirm="Xác nhận xóa {{ count($selectedFiles) }} file đã chọn khỏi kho file? Dữ liệu hóa đơn, RAW canonical, Inventory và Partner không bị xóa." wire:loading.attr="disabled" @disabled(count($selectedFiles)===0) class="inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-40">Xóa file đã chọn ({{ count($selectedFiles) }})</button>
                </div>
            </div>
            @error('selectedFiles')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Công cụ phụ trợ</p><h2 class="mt-1 text-base font-semibold text-gray-900">Nhập file ngoài & backup nhanh</h2><p class="mt-1 text-sm text-gray-500">Các tác vụ này không thuộc luồng GDT chính. Dùng khi nhận file thủ công, file chia sẻ hoặc cần sao lưu ngay.</p></div>
        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">Upload từ máy tính</p><p class="mt-1 text-xs leading-5 text-gray-500">XLSX/CSV tối đa 20 MB. Tên phải bắt đầu bằng <code>vat_in_</code> hoặc <code>vat_out_</code>.</p>
                <input type="file" wire:model="uploadFile" accept=".xlsx,.csv" class="mt-4 block w-full rounded-xl border border-gray-300 bg-white p-3 text-sm text-gray-700">@error('uploadFile')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                @if($uploadFile)<p class="mt-2 truncate text-xs text-gray-600">{{ $uploadFile->getClientOriginalName() }}</p>@endif
                <button wire:click="stageUploadedFile" wire:loading.attr="disabled" @disabled(!$uploadFile) class="mt-4 h-11 w-full rounded-xl bg-slate-800 px-4 text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="stageUploadedFile">Đưa vào kho file</span><span wire:loading wire:target="stageUploadedFile">Đang upload…</span></button>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4">
                <p class="text-sm font-semibold text-gray-900">Lấy file từ Google Drive</p><p class="mt-1 text-xs leading-5 text-gray-500">Dán link chia sẻ công khai. Hệ thống lấy tên file gốc và đưa file hợp lệ vào kho.</p>
                <input type="url" wire:model="googleDriveUrl" placeholder="https://drive.google.com/file/d/.../view" class="mt-4 h-11 w-full rounded-xl border border-blue-200 bg-white px-4 text-sm">@error('googleDriveUrl')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                <button wire:click="stageGoogleDriveFile" wire:loading.attr="disabled" @disabled(trim($googleDriveUrl)==='') class="mt-4 h-11 w-full rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="stageGoogleDriveFile">Tải vào kho file</span><span wire:loading wire:target="stageGoogleDriveFile">Đang tải…</span></button>
            </div>

            <div class="rounded-2xl border border-violet-100 bg-violet-50/40 p-4">
                <p class="text-sm font-semibold text-gray-900">Backup nhanh qua Email</p><p class="mt-1 text-xs leading-5 text-gray-500">Gửi bản sao toàn bộ file vat_in_* / vat_out_* dạng ZIP. File trên server không bị xóa.</p>
                <input type="email" wire:model="backupEmail" placeholder="email-nhan-backup@example.com" class="mt-4 h-11 w-full rounded-xl border border-violet-200 bg-white px-4 text-sm">@error('backupEmail')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                <button wire:click="backupFilesToEmail" wire:loading.attr="disabled" @disabled(trim($backupEmail)==='' || count($availableFiles)===0) class="mt-4 h-11 w-full rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="backupFilesToEmail">Gửi backup</span><span wire:loading wire:target="backupFilesToEmail">Đang gửi…</span></button>
            </div>
        </div>
    </section>

    <details class="group rounded-2xl border border-gray-200 bg-gray-950 shadow-sm" @if(in_array($syncState,['processing','partial','failed'],true)) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-white"><div><p class="text-sm font-semibold">Nhật ký xử lý</p><p class="mt-1 text-xs text-gray-400">Mở để xem từng lần gọi GDT, số hóa đơn nhận và lỗi. Không hiển thị mã CAPTCHA.</p></div><span class="text-xs font-semibold text-gray-300 group-open:hidden">Mở nhật ký</span><span class="hidden text-xs font-semibold text-gray-300 group-open:inline">Thu gọn</span></summary>
        <div class="border-t border-gray-800 px-5 pb-5 pt-4 font-mono text-xs text-gray-200"><div class="max-h-80 space-y-1 overflow-y-auto">@forelse($logs as $line)<div>{{ $line }}</div>@empty<div class="text-gray-400">Chưa có tác vụ.</div>@endforelse</div></div>
    </details>
</div>
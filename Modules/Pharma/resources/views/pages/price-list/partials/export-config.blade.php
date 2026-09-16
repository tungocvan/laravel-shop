<div id="price-list-export-config" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="price-list-export-title">
    <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
            <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600">Excel Export</p><h3 id="price-list-export-title" class="mt-1 text-lg font-bold text-slate-900">Cấu hình cột xuất Excel</h3><p class="mt-1 text-sm text-slate-500">Chọn đúng các trường cần đưa vào file. Cấu hình được ghi nhớ trên trình duyệt này.</p></div>
            <button type="button" onclick="closePriceListExportConfig()" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600">Đóng</button>
        </div>
        <div class="max-h-[65vh] overflow-y-auto p-5 sm:p-6">
            <div class="mb-4 flex flex-wrap gap-2"><button type="button" onclick="setAllPriceListExportColumns(true)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">Chọn tất cả</button><button type="button" onclick="setAllPriceListExportColumns(false)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">Bỏ chọn tất cả</button><button type="button" onclick="resetPriceListExportColumns()" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">Mặc định</button></div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($exportColumns as $key => $label)
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 hover:bg-slate-50"><input type="checkbox" value="{{ $key }}" data-export-column class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span class="text-sm font-medium text-slate-700">{{ $label }}</span></label>
                @endforeach
            </div>
        </div>
        <div class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"><p id="price-list-export-count" class="text-xs font-semibold text-slate-500"></p><div class="flex gap-2"><button type="button" onclick="closePriceListExportConfig()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button type="button" onclick="savePriceListExportConfig()" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Lưu cấu hình</button></div></div>
    </div>
</div>
<script>
const priceListExportStorageKey = 'pharma.price-list.export-columns.v1';
const priceListDefaultExportColumns = @json($defaultExportColumns);
function exportColumnBoxes(){return Array.from(document.querySelectorAll('[data-export-column]'))}
function storedPriceListExportColumns(){try{const value=JSON.parse(localStorage.getItem(priceListExportStorageKey));return Array.isArray(value)&&value.length?value:priceListDefaultExportColumns}catch(e){return priceListDefaultExportColumns}}
function applyPriceListExportColumns(columns){const selected=new Set(columns);exportColumnBoxes().forEach(box=>box.checked=selected.has(box.value));updatePriceListExportCount()}
function updatePriceListExportCount(){const count=exportColumnBoxes().filter(box=>box.checked).length;const el=document.getElementById('price-list-export-count');if(el)el.textContent=count+' cột được chọn'}
function openPriceListExportConfig(){applyPriceListExportColumns(storedPriceListExportColumns());const modal=document.getElementById('price-list-export-config');modal.classList.remove('hidden');modal.classList.add('flex')}
function closePriceListExportConfig(){const modal=document.getElementById('price-list-export-config');modal.classList.add('hidden');modal.classList.remove('flex')}
function setAllPriceListExportColumns(enabled){exportColumnBoxes().forEach(box=>box.checked=enabled);updatePriceListExportCount()}
function resetPriceListExportColumns(){applyPriceListExportColumns(priceListDefaultExportColumns)}
function savePriceListExportConfig(){const columns=exportColumnBoxes().filter(box=>box.checked).map(box=>box.value);if(!columns.length){alert('Vui lòng chọn ít nhất một cột để xuất Excel.');return}localStorage.setItem(priceListExportStorageKey,JSON.stringify(columns));closePriceListExportConfig();alert('Đã lưu cấu hình cột xuất Excel.')}
function submitPriceListExport(){const form=document.getElementById('price-list-export-form');const columns=storedPriceListExportColumns();if(!columns.length){alert('Vui lòng cấu hình ít nhất một cột xuất Excel.');return}form.querySelectorAll('input[data-export-hidden]').forEach(el=>el.remove());columns.forEach(column=>{const input=document.createElement('input');input.type='hidden';input.name='columns[]';input.value=column;input.dataset.exportHidden='1';form.appendChild(input)});form.submit()}
document.addEventListener('change',event=>{if(event.target.matches('[data-export-column]'))updatePriceListExportCount()});
</script>

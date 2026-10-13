@php($pharmaSections = [
    'hero' => ['Hero Pharma', 'Nội dung giới thiệu đầu trang và ảnh Hero.'],
    'categories' => ['Danh mục dược phẩm', 'Nhóm danh mục thuốc được công bố.'],
    'new_arrivals' => ['Sản phẩm mới cập nhật', 'Danh sách sản phẩm Pharma mới nhất.'],
])
<section class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
    <div class="mb-5">
        <h2 class="text-lg font-bold text-gray-900">Bố cục Homepage — Pharma</h2>
        <p class="mt-1 text-sm text-gray-500">Workspace độc lập. Không hiển thị Flash Sale, Top bán chạy hoặc các section bán lẻ của Default.</p>
    </div>
    <div class="space-y-3">
        @foreach($pharmaSectionOrder as $sectionKey)
            @if(isset($pharmaSections[$sectionKey]))
                <article wire:key="pharma-section-{{ $sectionKey }}" class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $pharmaSections[$sectionKey][0] }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $pharmaSections[$sectionKey][1] }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="movePharmaSection('{{ $sectionKey }}', 'up')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" aria-label="Đưa lên">↑</button>
                        <button type="button" wire:click="movePharmaSection('{{ $sectionKey }}', 'down')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" aria-label="Đưa xuống">↓</button>
                        <label class="sr-only" for="pharma-visibility-{{ $sectionKey }}">Hiển thị {{ $pharmaSections[$sectionKey][0] }}</label>
                        <select id="pharma-visibility-{{ $sectionKey }}" wire:model="pharmaLayout.{{ $sectionKey }}" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                            <option value="all">Desktop + Mobile</option>
                            <option value="desktop">Chỉ Desktop</option>
                            <option value="mobile">Chỉ Mobile</option>
                            <option value="none">Ẩn hoàn toàn</option>
                        </select>
                    </div>
                </article>
            @endif
        @endforeach
    </div>
    <p class="mt-4 text-xs text-amber-700">Checkpoint 1: lưu cấu hình độc lập. Chưa nối cấu hình này với renderer Homepage công khai.</p>
</section>

<div class="space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <img src="{{ $product->image_url }}" class="w-16 h-16 rounded-xl object-cover">
            <div>
                <h2 class="text-xl font-black text-gray-900">{{ $product->title }}</h2>
                <p class="text-sm text-gray-500">Giá hiện tại: <span class="font-bold text-blue-600">{{ number_format($product->regular_price) }}đ</span></p>
            </div>
        </div>
        <a href="{{ route('admin.products.index') }}" class="px-4 py-2 bg-gray-100 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-200 transition">Quay lại</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-widest">
                        <tr>
                            <th class="px-6 py-4">Đối tượng</th>
                            <th class="px-6 py-4 text-center">Kiểu</th>
                            <th class="px-6 py-4">Tỷ lệ (%)</th>
                            <th class="px-6 py-4">Tiền mặt (đ)</th>
                            <th class="px-6 py-4 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($schemes as $scheme)
                            <tr wire:key="scheme-{{ $scheme->id }}" class="hover:bg-gray-50/50">
                                <td class="px-6 py-4">
                                    @if($scheme->level_id)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-blue-50 text-blue-700 font-bold text-xs border border-blue-100">
                                            LEVEL: {{ $scheme->level->name }}
                                        </span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                            <span class="font-bold text-gray-900">{{ $scheme->user->name }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <select wire:change="updateScheme({{ $scheme->id }}, 'commission_type', $event.target.value)" class="text-[11px] font-bold border-gray-200 rounded-lg p-1">
                                        <option value="percentage" @selected($scheme->commission_type == 'percentage')>%</option>
                                        <option value="fixed" @selected($scheme->commission_type == 'fixed')>VNĐ</option>
                                        <option value="hybrid" @selected($scheme->commission_type == 'hybrid')>Hybrid</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="number" step="0.1" 
                                           wire:blur="updateScheme({{ $scheme->id }}, 'percent_value', $event.target.value)"
                                           value="{{ $scheme->percent_value }}"
                                           class="w-20 border-gray-100 rounded-lg text-sm font-bold focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4">
                                    <input type="number" 
                                           wire:blur="updateScheme({{ $scheme->id }}, 'fixed_value', $event.target.value)"
                                           value="{{ (int)$scheme->fixed_value }}"
                                           class="w-32 border-gray-100 rounded-lg text-sm font-bold focus:ring-blue-500 text-right">
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="deleteScheme({{ $scheme->id }})" class="p-2 text-red-300 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-widest">Thêm cấp bậc</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($levels as $lv)
                        <button wire:click="addLevelScheme({{ $lv->id }})" class="px-3 py-2 bg-blue-50 text-blue-600 rounded-xl text-xs font-bold hover:bg-blue-100 transition border border-blue-100">+ {{ $lv->name }}</button>
                    @endforeach
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-widest">Thêm đối tác riêng</h3>
                <div class="relative">
                    <input type="text" wire:model.live="searchUser" placeholder="Tìm tên hoặc email..." class="w-full border-gray-200 rounded-xl text-sm focus:ring-blue-500">
                    @if(count($userResults) > 0)
                        <div class="absolute z-10 w-full bg-white mt-1 border border-gray-100 rounded-xl shadow-xl overflow-hidden">
                            @foreach($userResults as $u)
                                <button wire:click="addUserScheme({{ $u->id }})" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 font-medium transition">{{ $u->name }} ({{ $u->email }})</button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
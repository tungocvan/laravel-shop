<div x-data="{
    mobileFilterOpen: false,
    viewMode: 'grid',
    showToast: false
}"
x-on:cart-updated.window="showToast = true; setTimeout(() => showToast = false, 3000)"
class="min-h-screen bg-gray-50 font-sans">

    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <nav class="flex text-sm text-gray-500 mb-2">
                <a href="{{ route('home') }}" class="hover:text-blue-600 transition">Trang chủ</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900 font-medium">Sản phẩm</span>
            </nav>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Tất cả sản phẩm</h1>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">

            <div x-show="mobileFilterOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
                <div x-show="mobileFilterOpen"
                     x-transition:enter="transition-opacity ease-linear duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black bg-opacity-25" @click="mobileFilterOpen = false"></div>

                <div x-show="mobileFilterOpen"
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="fixed inset-y-0 right-0 max-w-xs w-full bg-white shadow-xl py-4 pb-6 flex flex-col overflow-y-auto">

                    <div class="px-4 flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Bộ lọc</h2>
                        <button @click="mobileFilterOpen = false" class="-mr-2 w-10 h-10 bg-white p-2 rounded-md flex items-center justify-center text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-4">
                        @include('Website::livewire.products.partials.filters')
                    </div>
                </div>
            </div>

            <aside class="hidden lg:block lg:w-1/4">
                <div class="sticky top-6 space-y-8">
                    @include('Website::livewire.products.partials.filters')
                </div>
            </aside>

            <main class="w-full lg:w-3/4">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 pb-6 border-b border-gray-200 gap-4">
                    <button @click="mobileFilterOpen = true" class="lg:hidden flex items-center gap-2 text-gray-700 font-medium hover:text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Lọc sản phẩm
                    </button>

                    <span class="text-sm text-gray-500">Hiển thị <span class="font-bold text-gray-900">{{ $products->count() }}</span> kết quả</span>

                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <select wire:model.live="sort" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md cursor-pointer hover:bg-gray-50 transition">
                                <option value="latest">Mới nhất</option>
                                <option value="price_asc">Giá: Thấp đến Cao</option>
                                <option value="price_desc">Giá: Cao đến Thấp</option>
                                <option value="name_asc">Tên: A-Z</option>
                            </select>
                        </div>

                        <div class="hidden sm:flex bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
                            <button @click="viewMode = 'grid'" :class="{'bg-gray-100 text-blue-600': viewMode === 'grid', 'text-gray-400 hover:text-gray-600': viewMode !== 'grid'}" class="p-1.5 rounded transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            </button>
                            <button @click="viewMode = 'list'" :class="{'bg-gray-100 text-blue-600': viewMode === 'list', 'text-gray-400 hover:text-gray-600': viewMode !== 'list'}" class="p-1.5 rounded transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative min-h-[400px]">

                    <div wire:loading class="absolute inset-0 z-10 w-full h-full bg-white bg-opacity-70 backdrop-blur-[2px]">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            @for($i=0; $i<6; $i++)
                            <div class="animate-pulse">
                                <div class="bg-gray-200 rounded-xl h-64 w-full mb-4"></div>
                                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                            </div>
                            @endfor
                        </div>
                    </div>

                    @if($products->isEmpty())
                        <div class="text-center py-16 bg-white rounded-xl border border-dashed border-gray-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Không tìm thấy sản phẩm</h3>
                            <p class="mt-1 text-sm text-gray-500">Hãy thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
                            <button wire:click="$set('selected_categories', [])" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                Xóa bộ lọc
                            </button>
                        </div>
                    @else
                        <div :class="viewMode === 'grid' ? 'grid grid-cols-2 md:grid-cols-3 gap-6' : 'space-y-4'">
                            @foreach($products as $product)
                                <div wire:key="product-{{ $product->id }}"
                                     class="group relative bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 ease-out"
                                     :class="viewMode === 'list' ? 'flex flex-row items-center border p-4 hover:translate-y-0' : ''">

                                    <div class="relative overflow-hidden bg-gray-100"
                                         :class="viewMode === 'grid' ? 'aspect-[4/5]' : 'w-1/3 aspect-square rounded-lg'">
                                        <a href="{{ route('product.detail', $product->slug) }}" class="block w-full h-full">
                                        <img src="{{ $product->image_url ?? asset('images/placeholder.jpg') }}"
                                             alt="{{ $product->title }}"
                                             class="w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-110">
                                        </a>
                                        <div class="absolute top-3 left-3 flex flex-col gap-1">
                                            @if($product->sale_price)
                                                <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm tracking-wide">-SALE</span>
                                            @endif
                                            @if($product->created_at->diffInDays(now()) < 7)
                                                <span class="bg-blue-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm tracking-wide">NEW</span>
                                            @endif
                                        </div>

                                        <div x-show="viewMode === 'grid'" class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 z-20">
                                            <button wire:click="addToCart({{ $product->id }})" class="w-full bg-gray-900 text-white font-medium py-3 rounded-lg shadow-lg hover:bg-black flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                                Thêm vào giỏ
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-4" :class="viewMode === 'list' ? 'w-2/3 pl-6' : ''">
                                        <p class="text-xs text-gray-500 mb-1 uppercase tracking-wider">
                                            {{ $product->categories->first()->name ?? 'Chưa phân loại' }}
                                        </p>

                                        <h3 class="text-sm font-bold text-gray-900 leading-snug mb-2 group-hover:text-blue-600 transition-colors line-clamp-2">
                                            <a href="#">{{ $product->title }}</a>
                                        </h3>

                                        <div class="flex items-center mb-3">
                                            <div class="flex text-yellow-400 text-xs">
                                                @for($i=1; $i<=5; $i++)
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-gray-400 ml-1">(12 đánh giá)</span>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            @if($product->sale_price > 0)
                                                <span class="text-lg font-extrabold text-blue-600">{{ number_format($product->sale_price) }}đ</span>
                                                <span class="text-sm text-gray-400 line-through">{{ number_format($product->regular_price) }}đ</span>
                                            @else
                                                <span class="text-lg font-extrabold text-gray-900">{{ number_format($product->regular_price) }}đ</span>
                                            @endif
                                        </div>

                                        <div x-show="viewMode === 'list'" class="mt-4">
                                            <button wire:click="addToCart({{ $product->id }})" class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-black transition">
                                                Thêm vào giỏ hàng
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-10 border-t border-gray-200 pt-6">
                    {{ $products->links() }}
                </div>
            </main>
        </div>
    </div>

    <div x-show="showToast"
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-50 bg-gray-900 text-white px-6 py-4 rounded-lg shadow-2xl flex items-center gap-3"
         style="display: none;">
        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <div>
            <h4 class="font-bold">Thành công!</h4>
            <p class="text-sm text-gray-300">Sản phẩm đã được thêm vào giỏ.</p>
        </div>
    </div>
</div>

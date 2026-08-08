@props(['routes', 'modules' => [], 'total' => 0, 'editingRouteKey' => null])

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">GET Routes của Modules</h3>
                <p class="mt-1 text-sm text-gray-600">Title được tự động gợi ý và có thể chỉnh sửa trước khi thêm route vào Sidebar Menu.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:w-1/2">
                <label>
                    <span class="mb-1 block text-xs font-medium text-gray-600">Tìm route</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="routeSearch"
                        placeholder="URI, route name, title..."
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </label>
                <label>
                    <span class="mb-1 block text-xs font-medium text-gray-600">Lọc Module</span>
                    <select wire:model.live="routeModuleFilter" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tất cả Module</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}">{{ $module }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Route Name</th>
                    <th class="px-4 py-3">URI</th>
                    <th class="px-4 py-3">Title Module</th>
                    <th class="px-4 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($routes as $route)
                    <tr wire:key="module-route-{{ $route['key'] }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $route['module'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">{{ $route['name'] ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-indigo-700">{{ $route['url'] }}</td>
                        <td class="px-4 py-3">
                            @if ($editingRouteKey === $route['key'])
                                <input type="text" wire:model="routeTitle" wire:keydown.enter="saveRouteTitle" class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @else
                                <span class="font-medium text-gray-800">{{ $route['title'] }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($editingRouteKey === $route['key'])
                                <button type="button" wire:click="saveRouteTitle" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">Lưu</button>
                                <button type="button" wire:click="$set('editingRouteKey', null)" class="ml-1 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">Hủy</button>
                            @else
                                <button type="button" wire:click="editRouteTitle('{{ $route['key'] }}')" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">Edit</button>
                                <button
                                    type="button"
                                    wire:click="addRouteToMenu('{{ $route['key'] }}')"
                                    {{ ($route['in_menu'] || $route['is_dynamic']) ? 'disabled' : '' }}
                                    class="ml-1 rounded-md px-3 py-1.5 text-xs font-semibold {{ $route['in_menu'] ? 'cursor-not-allowed bg-emerald-50 text-emerald-600' : ($route['is_dynamic'] ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-indigo-600 text-white hover:bg-indigo-500') }}"
                                >{{ $route['in_menu'] ? 'Đã có Menu' : ($route['is_dynamic'] ? 'Route động' : 'Add Menu') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Không tìm thấy GET route thuộc module đang bật.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
        Đang hiển thị <span class="font-semibold">{{ count($routes) }}</span> / {{ $total }} routes
    </div>
</div>

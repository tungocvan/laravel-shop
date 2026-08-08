<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 border-b border-red-200 bg-red-50">
        <h2 class="text-base font-semibold text-red-700">Database administration is disabled</h2>
        <p class="mt-1 text-sm text-red-600">
            Backup, restore, truncate, drop, and download actions are unavailable until P0 security controls,
            named permissions, server-owned identifiers, audit logs, and tests are implemented.
        </p>
    </div>

    <div class="p-4 border-b border-gray-200 flex flex-wrap justify-between items-center gap-4 bg-gray-50">
        <div class="flex items-center gap-2">
            <input type="checkbox" disabled
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 opacity-50">
            <span class="text-sm font-medium text-gray-500">Select all disabled</span>
        </div>

        <div class="relative w-64">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text"
                class="block w-full pl-10 sm:text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Search disabled database view">
        </div>
    </div>

    <div class="px-6 py-10 text-center text-gray-500">
        Database tables are hidden while this Admin surface is contained.
    </div>
</div>

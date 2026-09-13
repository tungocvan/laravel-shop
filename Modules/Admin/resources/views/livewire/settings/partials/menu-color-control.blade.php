@php
    $dataPath = str_starts_with($model, 'config.') ? substr($model, 7) : $model;
    $current = (string) data_get($config, $dataPath, $fallback ?? 'slate-500');
    $resolved = preg_match('/^#[0-9a-fA-F]{6}$/', $current) === 1
        ? strtolower($current)
        : ($colorOptions[$current] ?? ($fallbackHex ?? '#64748b'));
@endphp

<label class="block min-w-0">
    <span class="text-xs font-semibold text-slate-700">{{ $label }}</span>
    <div class="mt-1.5 flex items-center gap-2">
        <input
            type="color"
            value="{{ $resolved }}"
            x-on:input="$wire.set(@js($model), $event.target.value)"
            class="h-10 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
            aria-label="{{ $label }} custom color"
        >
        <input
            type="text"
            list="admin-menu-color-presets"
            wire:model.live="{{ $model }}"
            placeholder="preset hoặc #RRGGBB"
            class="h-10 min-w-0 flex-1 rounded-lg border-slate-300 bg-white px-3 font-mono text-xs text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
    </div>
    <span class="mt-1 block text-[10px] text-slate-400">Preset hoặc HEX tùy chỉnh.</span>
</label>

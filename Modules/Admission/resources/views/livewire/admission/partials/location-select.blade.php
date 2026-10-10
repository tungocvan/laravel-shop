{{-- Admission-only searchable combobox; Livewire owns the selected value. --}}
@php
    $items = collect($items ?? []);
    $value = (string) ($form[$field] ?? '');
    $names = $items->pluck($labelKey)->map(fn ($name) => (string) $name)->all();
    $choices = $items->pluck($labelKey)->map(fn ($name) => (string) $name)->values()->all();
    if ($value !== '' && !in_array($value, $names, true)) {
        $choices[] = $value; // Keep legacy value (chưa có trong danh mục) selectable.
    }
@endphp
<div
    x-data="{
        open: false,
        search: '',
        selected: @js($value),
        choices: @js($choices),
        model: @js('form.'.$field),
        get filtered() {
            const q = this.search.trim().toLocaleLowerCase('vi');
            return this.choices.filter(value => !q || value.toLocaleLowerCase('vi').includes(q));
        },
        choose(value) {
            this.selected = value;
            this.search = '';
            this.open = false;
            this.$wire.set(this.model, value);
        }
    }"
    x-effect="
        selected = $wire.form[@js($field)] ?? '';
        choices = @js($choices);
    "
    x-on:click.outside="open = false"
    x-on:keydown.escape.prevent="open = false"
    class="relative min-w-0"
>
    <div class="relative">
        <input
            type="search"
            x-model="search"
            x-on:focus="open = true"
            x-on:click="open = true"
            x-on:keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.firstChoice?.focus())"
            x-on:keydown.enter.prevent="if (open && filtered.length) choose(filtered[0]); else open = true"
            :placeholder="selected || @js($placeholder)"
            aria-label="Tìm {{ $placeholder }}"
            aria-autocomplete="list"
            aria-haspopup="listbox"
            :aria-expanded="open.toString()"
            class="w-full min-h-11 rounded-xl border border-gray-300 bg-white px-3 py-2.5 pr-10 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
        >
        <button type="button" x-on:click="open = !open" aria-label="Mở danh sách {{ $placeholder }}" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-500">⌄</button>
    </div>
    <div
        x-show="open"
        x-cloak
        role="listbox"
        class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1 shadow-lg"
    >
        <button type="button" role="option" x-on:click="choose('')" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">-- Chọn --</button>
        <template x-for="(choice, index) in filtered" :key="choice">
            <button
                type="button"
                role="option"
                :x-ref="index === 0 ? 'firstChoice' : null"
                :aria-selected="(choice === selected).toString()"
                x-text="choice"
                x-on:click="choose(choice)"
                x-on:keydown.enter.prevent="choose(choice)"
                class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"
                :class="choice === selected ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-gray-800'"
            ></button>
        </template>
        <p x-show="filtered.length === 0" class="px-3 py-3 text-sm text-gray-500">Không tìm thấy kết quả</p>
    </div>
    <span class="sr-only" x-text="selected"></span>
</div>

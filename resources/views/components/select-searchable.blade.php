@props([
    'items' => collect(),
    'modelName' => 'filter',
    'modelValue' => null,
    'displayProperty' => 'name',
    'valueProperty' => 'id',
    'placeholder' => 'Pilih Item',
    'searchPlaceholder' => 'Cari...',
])

<div class="relative" x-data="{ open: false, search: '' }" x-on:click.outside="open = false">
    <button type="button"
        x-on:click="open = !open; if (open) { $nextTick(() => $refs.search{{ $modelName }}.focus()) }"
        class="appearance-none bg-white border border-gray-300 rounded-lg px-4 size-10 pr-10 text-sm text-gray-700 focus:ring-2 focus:ring-gray-400 focus:border-gray-400 focus:outline-none transition-all duration-300 hover:border-gray-400 cursor-pointer w-full text-left"
        {{ $attributes }}>
        <span>{{ $items?->find($modelValue)?->{$displayProperty} ?? $placeholder }}</span>
    </button>

    <div x-show="open" x-transition
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-hidden">
        <!-- Search Input -->
        <div class="p-2 border-b border-gray-200">
            <input type="text" x-model="search" x-ref="search{{ $modelName }}" placeholder="{{ $searchPlaceholder }}"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
        </div>

        <div class="overflow-y-auto max-h-48">
            <div class="py-1">
                <button type="button" wire:click="$set('{{ $modelName }}', '')" x-on:click="open = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    {{ $placeholder }}
                </button>
                @foreach ($items as $item)
                    <button type="button" wire:click="$set('{{ $modelName }}', {{ $item->{$valueProperty} }})"
                        x-on:click="open = false"
                        x-show="String({{ json_encode(strtolower($item->{$displayProperty})) }}).includes(search.toLowerCase())"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200 {{ $modelValue == $item->{$valueProperty} ? 'bg-gray-400 text-white' : '' }}">
                        {{ $item->{$displayProperty} }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <flux:icon name="chevrons-up-down" class="w-4 h-4 text-gray-400 transition-transform duration-200"
            x-bind:class="{ 'rotate-180': open }" />
    </div>
</div>

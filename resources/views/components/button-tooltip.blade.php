@props([
    'tooltip' => null,
    'icon' => null,
    'variant' => 'ghost',
    'size' => 'xs',
    'iconClass' => 'w-4 h-4 inline-block',
])

<div class="relative inline-block group">
    @if($attributes->has('href'))
        <a
            {{ $attributes->merge(['class' => 'relative']) }}
        >
            @if($icon)
                <flux:icon :name="$icon" :class="$iconClass" />
            @endif
            {{ $slot }}
        </a>
    @elseif($attributes->has('wire:click'))
        <button
            {{ $attributes->merge(['class' => 'relative']) }}
            type="button"
        >
            @if($icon)
                <flux:icon :name="$icon" :class="$iconClass" />
            @endif
            {{ $slot }}
        </button>
    @else
        <flux:button
            {{ $attributes->class(['relative']) }}
            :variant="$variant"
            :size="$size"
        >
            @if($icon)
                <flux:icon :name="$icon" :class="$iconClass" />
            @endif
            {{ $slot }}
        </flux:button>
    @endif

    @if($tooltip)
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-900 rounded whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none z-50">
            {{ $tooltip }}
            <span class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></span>
        </span>
    @endif
</div>

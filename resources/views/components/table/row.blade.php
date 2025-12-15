@props([
    'hover' => true,
    'clickable' => false,
    'striped' => false,
])

<tr
    x-data="{ isHovered: false }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
    {{ $attributes->class([
        'bg-white border-b table-row-transition',
        'hover:bg-gray-50' => $hover && !$clickable,
        'hover:bg-blue-50 cursor-pointer' => $clickable,
        'active:scale-[0.99]' => $clickable,
    ]) }}
    :class="{
        'ring-2 ring-blue-200': isHovered && {{ $clickable ? 'true' : 'false' }}
    }"
>
    {{ $slot }}
</tr>

@props([
    'header' => false,
    'stickyLeft' => false,
    'stickyBg' => 'bg-white',
])

@if ($header)
    <th {{ $attributes->class([
        'px-6 py-3',
        'sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] border-r border-gray-300' => $stickyLeft,
        $stickyBg => $stickyLeft,
    ]) }}>
        {{ $slot }}
    </th>
@else
    <td {{ $attributes->class([
        'px-6 py-4',
        'sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] border-r border-gray-300' => $stickyLeft,
        $stickyBg => $stickyLeft,
    ]) }}>
        {{ $slot }}
    </td>
@endif

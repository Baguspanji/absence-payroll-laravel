{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
    if ($variant === 'solid') {
        throw new \Exception('The "solid" variant is not supported in Lucide.');
    }

    $classes = Flux::classes('shrink-0')->add(
        match ($variant) {
            'outline' => '[:where(&)]:size-6',
            'solid' => '[:where(&)]:size-6',
            'mini' => '[:where(&)]:size-5',
            'micro' => '[:where(&)]:size-4',
        },
    );

    $strokeWidth = match ($variant) {
        'outline' => 2,
        'mini' => 2.25,
        'micro' => 2.5,
    };
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 200 200"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-slot="icon"
>
  <!-- Background circle -->
  <circle cx="100" cy="100" r="95" fill="#EFF6FF" stroke="#1E40AF" stroke-width="2"/>

  <!-- Main building structure -->
  <g>
    <!-- Left building -->
    <rect x="30" y="60" width="50" height="80" rx="4" fill="#1E40AF" stroke="#1E40AF" stroke-width="2"/>

    <!-- Right building -->
    <rect x="120" y="50" width="50" height="90" rx="4" fill="#2563EB" stroke="#2563EB" stroke-width="2"/>

    <!-- Center bridge/connection -->
    <rect x="80" y="75" width="40" height="65" rx="3" fill="#3B82F6" stroke="#3B82F6" stroke-width="2"/>

    <!-- Windows - Left building -->
    <g fill="white" opacity="0.9">
      <rect x="38" y="68" width="10" height="10" rx="2"/>
      <rect x="52" y="68" width="10" height="10" rx="2"/>
      <rect x="38" y="85" width="10" height="10" rx="2"/>
      <rect x="52" y="85" width="10" height="10" rx="2"/>
      <rect x="38" y="102" width="10" height="10" rx="2"/>
      <rect x="52" y="102" width="10" height="10" rx="2"/>
      <rect x="38" y="119" width="10" height="10" rx="2"/>
      <rect x="52" y="119" width="10" height="10" rx="2"/>
    </g>

    <!-- Windows - Right building -->
    <g fill="white" opacity="0.85">
      <rect x="128" y="58" width="10" height="10" rx="2"/>
      <rect x="142" y="58" width="10" height="10" rx="2"/>
      <rect x="128" y="75" width="10" height="10" rx="2"/>
      <rect x="142" y="75" width="10" height="10" rx="2"/>
      <rect x="128" y="92" width="10" height="10" rx="2"/>
      <rect x="142" y="92" width="10" height="10" rx="2"/>
      <rect x="128" y="109" width="10" height="10" rx="2"/>
      <rect x="142" y="109" width="10" height="10" rx="2"/>
      <rect x="128" y="126" width="10" height="10" rx="2"/>
      <rect x="142" y="126" width="10" height="10" rx="2"/>
    </g>

    <!-- Windows - Center bridge -->
    <g fill="white" opacity="0.8">
      <rect x="88" y="83" width="8" height="8" rx="1.5"/>
      <rect x="104" y="83" width="8" height="8" rx="1.5"/>
      <rect x="88" y="100" width="8" height="8" rx="1.5"/>
      <rect x="104" y="100" width="8" height="8" rx="1.5"/>
    </g>

    <!-- Roof elements - Left -->
    <polygon points="30,60 55,40 80,60" fill="#1E3A8A" stroke="#1E40AF" stroke-width="1.5"/>

    <!-- Roof elements - Right -->
    <polygon points="120,50 145,30 170,50" fill="#1E3A8A" stroke="#2563EB" stroke-width="1.5"/>

    <!-- Flag pole with flagpole accent -->
    <line x1="145" y1="30" x2="145" y2="20" stroke="#1E40AF" stroke-width="3" stroke-linecap="round"/>
    <circle cx="145" cy="18" r="3" fill="#EF4444"/>
  </g>

  <!-- Bottom accent line -->
  <line x1="50" y1="145" x2="150" y2="145" stroke="#2563EB" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
</svg>

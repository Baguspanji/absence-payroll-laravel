@props([
    'headers' => [],
    'rows' => [],
    'emptyMessage' => 'Tidak ada data.',
    'fixedHeader' => false,
    'maxHeight' => '500px',
    'stickyFirstColumn' => false,
    'hoverable' => true,
    'striped' => false,
])

@push('style')
    <style>
        /* Custom scrollbar styling for Excel-like appearance */
        .excel-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .excel-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .excel-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }

        .excel-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Smooth transitions for row interactions */
        .table-row-transition {
            transition: background-color 150ms ease-in-out, transform 50ms ease-in-out;
        }
    </style>
@endpush

<div x-data="{
    scrollTop: 0,
    scrollLeft: 0,
    showScrollIndicator: false,
    init() {
        this.$nextTick(() => {
            const container = this.$refs.tableContainer;
            if (container) {
                this.checkScroll(container);
            }
        });
    },
    handleScroll(e) {
        this.scrollTop = e.target.scrollTop;
        this.scrollLeft = e.target.scrollLeft;
        this.checkScroll(e.target);
    },
    checkScroll(container) {
        const hasVerticalScroll = container.scrollHeight > container.clientHeight;
        const hasHorizontalScroll = container.scrollWidth > container.clientWidth;
        this.showScrollIndicator = hasVerticalScroll || hasHorizontalScroll;
    }
}">
    <!-- Scroll Indicator -->
    {{-- <div x-show="showScrollIndicator && {{ $fixedHeader ? 'true' : 'false' }}" x-transition
        class="absolute top-2 right-2 z-30 bg-blue-500 text-white text-xs px-2 py-1 rounded shadow-lg" x-cloak>
        <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
            </svg>
            Scroll
        </span>
    </div> --}}

    <div x-ref="tableContainer" @scroll="handleScroll" @class([
        'w-full border border-gray-300 shadow-md bg-white rounded-lg relative excel-scrollbar',
        'overflow-auto' => $fixedHeader,
        'overflow-x-auto' => !$fixedHeader,
    ]) @style([
        'max-height: ' . $maxHeight => $fixedHeader,
    ])>
        <table class="w-full text-sm text-left text-gray-500" @class([
            'border-collapse whitespace-nowrap' => $fixedHeader,
        ])>
            @if (count($headers) > 0)
                <thead @class([
                    'text-xs text-gray-700 uppercase bg-gray-50',
                    'sticky top-0 z-10' => $fixedHeader,
                ])>
                    <tr>
                        @foreach ($headers as $index => $header)
                            <th scope="col" @class([
                                'px-6 py-3',
                                'border-r border-gray-200' => $fixedHeader,
                                'sticky left-0 z-20 bg-gray-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]' =>
                                    $fixedHeader && $stickyFirstColumn && $index === 0,
                                'bg-gray-50' => $fixedHeader && (!$stickyFirstColumn || $index !== 0),
                            ])>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody @class([
                'divide-y divide-gray-200' => $fixedHeader,
            ])>
                @if (count($rows) > 0)
                    {{ $slot }}
                @else
                    <tr class="bg-white border-b">
                        <td colspan="{{ count($headers) }}" class="px-6 py-4 text-center text-gray-500">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

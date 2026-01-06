<x-layouts.app :title="__('Dashboard')">
    <div class="px-6 py-4">
    <!-- Stats Cards - Fixed Position on Scroll -->
    <livewire:dashboard.stat-chart />

    <!-- Attendance Chart -->
    <div class="grid grid-cols-1 gap-6">
        <livewire:dashboard.attendance />
        <livewire:dashboard.employee />
    </div>
</div>
</x-layouts.app>

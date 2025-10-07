<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>

                <flux:navlist.item icon="document-check" :href="route('admin.leaves.approval')"
                    :current="request()->routeIs('admin.leaves.approval')" wire:navigate>{{ __('Persetujuan Cuti') }}
                </flux:navlist.item>
                <flux:navlist.item icon="document-plus" :href="route('leaves.create')"
                    :current="request()->routeIs('leaves.create')" wire:navigate>{{ __('Pengajuan Cuti') }}
                </flux:navlist.item>

                <flux:navlist.item icon="clock" :href="route('admin.overtime.approval')"
                    :current="request()->routeIs('admin.overtime.approval')" wire:navigate>
                    {{ __('Persetujuan Lembur') }}
                </flux:navlist.item>
                <flux:navlist.item icon="clock-plus" :href="route('overtime.create')"
                    :current="request()->routeIs('overtime.create')" wire:navigate>{{ __('Pengajuan Lembur') }}
                </flux:navlist.item>

                <flux:navlist.item icon="circle-dollar-sign" :href="route('admin.payroll.generator')"
                    :current="request()->routeIs('admin.payroll.generator')" wire:navigate>
                    {{ __('Penggajian') }}
                </flux:navlist.item>

                <flux:navlist.item icon="file-text" :href="route('admin.payroll.report')"
                    :current="request()->routeIs('admin.payroll.report')" wire:navigate>
                    {{ __('Laporan Rekap Gaji') }}
                </flux:navlist.item>

            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />
    </flux:sidebar>

    <!-- Include the navbar component -->
    <x-layouts.app.navbar />

    {{ $slot }}

    @fluxScripts
</body>

</html>

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
            </flux:navlist.group>

            @can('admin')
                <flux:navlist.group :heading="__('Master Data')" class="grid">
                    <flux:navlist.item icon="map-pin" :href="route('admin.branch.index')"
                        :current="request()->routeIs('admin.branch.index')" wire:navigate>
                        {{ __('Daftar Cabang') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="file-clock" :href="route('admin.shift.index')"
                        :current="request()->routeIs('admin.shift.index')" wire:navigate>
                        {{ __('Daftar Aturan Shift') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="credit-card" :href="route('admin.payroll.component')"
                        :current="request()->routeIs('admin.payroll.component')" wire:navigate>
                        {{ __('Daftar Master Gaji') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="user" :href="route('admin.user.index')"
                        :current="request()->routeIs('admin.user.index')" wire:navigate>
                        {{ __('Daftar Pengguna') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="fingerprint" :href="route('admin.device.index')"
                        :current="request()->routeIs('admin.device.index')" wire:navigate>
                        {{ __('Daftar Device') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endcan

            <flux:navlist.group :heading="__('Absensi')" class="grid">
                @canany(['admin', 'leader'])
                    <flux:navlist.item icon="notebook-text" :href="route('admin.attedance.history')"
                        :current="request()->routeIs('admin.attedance.history')" wire:navigate>{{ __('Riwayat Absensi') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="document-check" :href="route('admin.leaves.approval')"
                        :current="request()->routeIs('admin.leaves.approval')" wire:navigate>
                        {{ __('Persetujuan Cuti') }}
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
                @endcanany

                @can('employee')
                    <flux:navlist.item icon="document-plus" :href="route('leaves.create')"
                        :current="request()->routeIs('leaves.create')" wire:navigate>{{ __('Pengajuan Cuti') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="clock-plus" :href="route('overtime.create')"
                        :current="request()->routeIs('overtime.create')" wire:navigate>{{ __('Pengajuan Lembur') }}
                    </flux:navlist.item>
                @endcan
            </flux:navlist.group>

            @can('admin')
                <flux:navlist.group :heading="__('Laporan')" class="grid">
                    <flux:navlist.item icon="circle-dollar-sign" :href="route('admin.payroll.generator')"
                        :current="request()->routeIs('admin.payroll.generator')" wire:navigate>
                        {{ __('Penggajian') }}
                    </flux:navlist.item>

                    <flux:navlist.item icon="file-text" :href="route('admin.payroll.report')"
                        :current="request()->routeIs('admin.payroll.report')" wire:navigate>
                        {{ __('Laporan Rekap Gaji') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endcan
        </flux:navlist>

        <flux:spacer />
    </flux:sidebar>

    <!-- Include the navbar component -->
    <x-layouts.app.navbar />

    {{ $slot }}

    @fluxScripts
</body>

</html>

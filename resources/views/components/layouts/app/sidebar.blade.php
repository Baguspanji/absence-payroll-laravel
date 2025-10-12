<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-start space-x-2 rtl:space-x-reverse" wire:navigate>
            <svg class="h-9 w-9 text-black mt-0.5" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="36" height="36" rx="4" stroke="currentColor" stroke-width="3" fill="none"/>
                <path d="M20 10C22.2 10 24 11.8 24 14C24 16.2 22.2 18 20 18C17.8 18 16 16.2 16 14C16 11.8 17.8 10 20 10Z" fill="currentColor"/>
                <path d="M20 20C16 20 12 22 12 26V30H28V26C28 22 24 20 20 20Z" fill="currentColor"/>
                <path d="M20 6V8M20 32V34M28 20H30M10 20H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <h2 class="text-xl font-semibold leading-[1]">
                Absensi &<br> Penggajian
            </h2>
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

                    <flux:navlist.item icon="clipboard-check" :href="route('admin.attedance.summary')"
                        :current="request()->routeIs('admin.attedance.summary')" wire:navigate>{{ __('Rekap Absensi') }}
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

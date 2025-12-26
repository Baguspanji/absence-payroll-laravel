<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-md flex-col gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-medium mx-auto" wire:navigate>
                <flux:icon name="office-building" class="w-12 h-12" />
                <div class="flex flex-col pt-1">
                    <h2 class="text-xl font-semibold leading-[1] text-zinc-900 dark:text-white">
                        Absensi <span class="text-md font-medium">&</span>
                    </h2>
                    <span class="text-md font-medium text-zinc-600 dark:text-zinc-400">Penggajian</span>
                </div>
            </a>

            <div class="flex flex-col gap-6">
                <div
                    class="rounded-xl border bg-white dark:bg-stone-950 dark:border-stone-800 text-stone-800 shadow-xs">
                    <div class="px-10 py-8">{{ $slot }}</div>
                </div>
            </div>
        </div>
    </div>
    @fluxScripts
</body>

</html>

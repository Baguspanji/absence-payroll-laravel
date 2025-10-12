<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-md flex-col gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-medium mx-auto" wire:navigate>
                <svg class="h-9 w-9 text-black mt-0.5" viewBox="0 0 40 40" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="36" height="36" rx="4" stroke="currentColor"
                        stroke-width="3" fill="none" />
                    <path
                        d="M20 10C22.2 10 24 11.8 24 14C24 16.2 22.2 18 20 18C17.8 18 16 16.2 16 14C16 11.8 17.8 10 20 10Z"
                        fill="currentColor" />
                    <path d="M20 20C16 20 12 22 12 26V30H28V26C28 22 24 20 20 20Z" fill="currentColor" />
                    <path d="M20 6V8M20 32V34M28 20H30M10 20H12" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" />
                </svg>
                <h2 class="text-xl font-semibold leading-[1]">
                    Absensi &<br> Penggajian
                </h2>
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

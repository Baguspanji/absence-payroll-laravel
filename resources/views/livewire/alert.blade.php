<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $show = false;
    public string $message = '';
    public string $type = 'success'; // 'success', 'info', 'warning', 'error'

    /**
     * Dijalankan saat komponen pertama kali dirender.
     * Berguna untuk menangkap session flash dari redirect.
     */
    public function mount(): void
    {
        if (session()->has('alert-message')) {
            $alert = session('alert-message');
            $this->showAlert($alert['message'], $alert['type'] ?? 'success');
        }
    }

    /**
     * Listener untuk event 'alert-shown'.
     * Ini adalah cara utama untuk memanggil alert dari komponen lain.
     */
    #[On('alert-shown')]
    public function showAlert(string $message, string $type = 'success'): void
    {
        $this->show = true;
        $this->message = $message;
        $this->type = $type;
    }
}; ?>

<div x-data="{
    show: @entangle('show'),
    timer: null
}" x-init="$watch('show', value => {
    if (value) {
        clearTimeout(timer);
        timer = setTimeout(() => { show = false }, 5000); // Alert hilang setelah 5 detik
    }
})" x-show="show" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-2" class="fixed top-5 right-5 z-50" style="display: none;">

    @php
        $colors = match ($type) {
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            default => 'bg-gray-100 border-gray-400 text-gray-700',
        };
    @endphp

    <div class="flex items-center border rounded-lg px-4 py-2 shadow-lg {{ $colors }}" role="alert">
        <span class="flex-grow">{{ $message }}</span>
        <button @click="show = false" class="text-2xl ml-4 cursor-pointer">&times;</button>
    </div>
</div>

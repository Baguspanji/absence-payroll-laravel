<?php

use App\Http\Controllers\FingerprintController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::middleware(['is_admin'])->prefix('admin')->group(function () {
        Volt::route('persetujuan-cuti', 'leaves.approval')->name('admin.leaves.approval');
    });

    Volt::route('ajukan-cuti', 'leaves.request-form')->name('leaves.create');
});

// Group all iclock routes under the log.iclock middleware
Route::middleware(['log.iclock'])->group(function () {
    /// Route untuk mesin "bertanya" / check-in
    Route::get('iclock/getrequest', [FingerprintController::class, 'getRequest']);

    // Route untuk mesin "mengirim data" absensi
    Route::post('iclock/cdata', [FingerprintController::class, 'cData']);
});

require __DIR__ . '/auth.php';

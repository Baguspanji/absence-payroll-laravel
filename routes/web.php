<?php

use App\Http\Controllers\FingerprintController;
use App\Http\Controllers\PayrollController;
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

        Volt::route('persetujuan-lembur', 'overtime.approval')->name('admin.overtime.approval');

        Volt::route('payroll/component', 'payroll.component')->name('admin.payroll.component');

        Volt::route('payroll/generate', 'payroll.generator')->name('admin.payroll.generator');

        Volt::route('payroll/report', 'payroll.report')->name('admin.payroll.report');

        Volt::route('pengguna', 'user.list')->name('admin.user.index');

        Volt::route('cabang', 'branch.list')->name('admin.branch.index');

        Volt::route('shift', 'shift.list')->name('admin.shift.index');

        Volt::route('device', 'device.list')->name('admin.device.index');
    });

    Route::get('/payroll/{payroll}/slip', [PayrollController::class, 'showSlip'])->name('payroll.slip');

    Volt::route('ajukan-cuti', 'leaves.request-form')->name('leaves.create');

    Volt::route('ajukan-lembur', 'overtime.request-form')->name('overtime.create');
});

// Group all iclock routes under the log.iclock middleware
Route::middleware(['log.iclock'])->group(function () {
    /// Route untuk mesin "bertanya" / check-in
    Route::get('iclock/getrequest', [FingerprintController::class, 'getRequest']);

    // Route untuk mesin "mengirim data" absensi
    Route::post('iclock/cdata', [FingerprintController::class, 'cData']);
});

require __DIR__ . '/auth.php';

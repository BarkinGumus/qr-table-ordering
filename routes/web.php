<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Guest\TableScanController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PrintQrCodesController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/locale/{locale}', LocaleController::class)->name('locale');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    // The controller limits attempts per email + IP. This second limit caps
    // one IP trying many different emails.
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Staff screens. The kitchen, bar and waiter boards are added in later steps.
Route::middleware(['auth', 'role:owner,manager,waiter,kitchen,bar'])->group(function () {
    Route::view('/staff', 'staff.home')->name('staff.home');
});

// Printing QR codes is for the people who manage tables.
Route::middleware(['auth', 'role:owner,manager'])->group(function () {
    Route::get('/print/qr-codes', PrintQrCodesController::class)->name('print.qr-codes');
});

// Where a table's QR code points. Limited per IP so tokens cannot be probed
// in bulk (they are 128 random bits, so guessing is hopeless anyway).
Route::get('/t/{token}', TableScanController::class)
    ->middleware('throttle:30,1')
    ->name('table.scan');

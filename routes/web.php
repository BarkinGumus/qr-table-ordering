<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/locale/{locale}', LocaleController::class)->name('locale');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Staff screens. The kitchen, bar and waiter boards are added in later steps.
Route::middleware(['auth', 'role:owner,manager,waiter,kitchen,bar'])->group(function () {
    Route::view('/staff', 'staff.home')->name('staff.home');
});

<?php

use App\Http\Controllers\Auth\SamlController;
use App\Http\Controllers\DebuggerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.production')->group(function () {
    Route::get('/', [DebuggerController::class, 'index'])->name('debugger');
    Route::post('/debugger/simulate', [DebuggerController::class, 'simulate'])->name('debugger.simulate');
});

Route::prefix('saml')->name('saml.')->group(function () {
    Route::get('/metadata', [SamlController::class, 'metadata'])->name('metadata');
    Route::get('/login', [SamlController::class, 'login'])->name('login');
    Route::post('/acs', [SamlController::class, 'acs'])->name('acs');
    Route::match(['get', 'post'], '/sls', [SamlController::class, 'sls'])->name('sls');
});

<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OptionsController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/upload', [InvoiceController::class, 'create'])->name('invoices.upload');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/analyse', [InvoiceController::class, 'analyse'])->name('invoices.analyse');

    Route::get('options', [OptionsController::class, 'index'])->name('options.index');
    Route::get('options/{option}/edit', [OptionsController::class, 'edit'])->name('options.edit');
    Route::patch('options/{option}', [OptionsController::class, 'update'])->name('options.update');
});

require __DIR__.'/settings.php';

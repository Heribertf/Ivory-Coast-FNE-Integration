<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FneInvoiceController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::prefix('fne')->name('fne.')->group(function () {
    Route::get('/invoices', [FneInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [FneInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/certify', [FneInvoiceController::class, 'certify'])->name('invoices.certify');
    Route::post('/invoices/bulk-certify', [FneInvoiceController::class, 'bulkCertify'])->name('invoices.bulk-certify');
    Route::post('/invoices/{invoice}/retry', [FneInvoiceController::class, 'retry'])->name('invoices.retry');
    Route::post('/invoices/sync', [FneInvoiceController::class, 'sync'])->name('invoices.sync');
    Route::post('/invoices/{invoice}/refund', [FneInvoiceController::class, 'refund'])->name('invoices.refund');
    Route::get('/invoices/export', [FneInvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/invoices/{invoice}/pdf', [FneInvoiceController::class, 'pdf'])->name('invoices.pdf');
});

require __DIR__ . '/auth.php';

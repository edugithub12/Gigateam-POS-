<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PdfController;
use App\Livewire\Pos\PosCart;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

// ── POS Route ────────────────────────────────────────────────────────────────
Route::middleware(['auth'])->get('/pos', PosCart::class)
    ->name('pos')
    ->can('access pos');

// ── PDF Download Routes ───────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/{invoice}/pdf',           [PdfController::class, 'invoice'])      ->name('invoices.pdf');
    Route::get('/quotations/{quotation}/pdf',        [PdfController::class, 'quotation'])    ->name('quotations.pdf');
    Route::get('/job-cards/{jobCard}/pdf',           [PdfController::class, 'jobCard'])      ->name('job-cards.pdf');
    Route::get('/delivery-notes/{deliveryNote}/pdf', [PdfController::class, 'deliveryNote']) ->name('delivery-notes.pdf');
});

// ── Report PDF Export Routes ──────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/sales/pdf',     [App\Http\Controllers\ReportPdfController::class, 'sales'])     ->name('sales.pdf');
    Route::get('/vat/pdf',       [App\Http\Controllers\ReportPdfController::class, 'vat'])       ->name('vat.pdf');
    Route::get('/statement/pdf', [App\Http\Controllers\ReportPdfController::class, 'statement']) ->name('statement.pdf');
});

// ── M-Pesa Routes ─────────────────────────────────────────────────────────────
Route::post('/mpesa/callback', [App\Http\Controllers\MpesaController::class, 'callback'])
    ->name('mpesa.callback')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware(['auth'])->group(function () {
    Route::post('/mpesa/stk-push',                    [App\Http\Controllers\MpesaController::class, 'initiate'])->name('mpesa.initiate');
    Route::get('/mpesa/status/{checkoutRequestId}',   [App\Http\Controllers\MpesaController::class, 'status'])->name('mpesa.status');
});

require __DIR__.'/auth.php';
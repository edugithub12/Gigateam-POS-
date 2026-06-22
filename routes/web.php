<?php

use App\Http\Controllers\PdfController;
use App\Http\Controllers\ShopSwitchController;
use App\Livewire\Pos\PosCart;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

// ── Shop switcher (called via fetch from the top-bar dropdown) ────────────────
Route::middleware(['auth'])->post('/admin/switch-shop/{shopId}', ShopSwitchController::class)
    ->name('shop.switch');

// ── POS Route ─────────────────────────────────────────────────────────────────
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

// ── Offline POS API ───────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/pos-offline/products',  [App\Http\Controllers\PosOfflineController::class, 'products'])  ->name('pos.offline.products');
    Route::get('/pos-offline/customers', [App\Http\Controllers\PosOfflineController::class, 'customers']) ->name('pos.offline.customers');
    Route::post('/pos-offline/sync',     [App\Http\Controllers\PosOfflineController::class, 'sync'])      ->name('pos.offline.sync');
    Route::get('/pos-offline/user',      fn () => response()->json(['name' => auth()->user()->name]))     ->name('pos.offline.user');
    Route::get('/pos-offline/csrf',      fn () => response()->json(['token' => csrf_token()]))            ->name('pos.offline.csrf');
    Route::get('/pos-offline.html',      fn () => response()->file(public_path('pos-offline.html')))      ->name('pos.offline.page');
});

// ── M-Pesa Routes ─────────────────────────────────────────────────────────────
Route::post('/mpesa/callback', [App\Http\Controllers\MpesaController::class, 'callback'])
    ->name('mpesa.callback')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware(['auth'])->group(function () {
    Route::post('/mpesa/stk-push',                  [App\Http\Controllers\MpesaController::class, 'initiate']) ->name('mpesa.initiate');
    Route::get('/mpesa/status/{checkoutRequestId}', [App\Http\Controllers\MpesaController::class, 'status'])   ->name('mpesa.status');
});

// ── Debug route (remove in production) ───────────────────────────────────────
Route::get('/whoami', function () {
    return [
        'authenticated'     => auth()->check(),
        'user'              => auth()->user()?->only(['id', 'email']),
        'roles'             => auth()->user()?->getRoleNames(),
        'active_shop'       => activeShop()?->name ?? 'All Shops',
        'active_shop_id'    => activeShopId(),
    ];
})->middleware('auth');

require __DIR__.'/auth.php';

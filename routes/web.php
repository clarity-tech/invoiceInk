<?php

use App\Http\Controllers\PublicViewController;
use App\Livewire\CompanyManager;
use App\Livewire\CustomerManager;
use App\Livewire\InvoiceWizard;
use App\Livewire\TeamSettings;
use Illuminate\Support\Facades\Route;

// Public view routes for invoices and estimates (no authentication required)
Route::get('/invoices/{ulid}', [PublicViewController::class, 'showInvoice'])->name('invoices.public');
Route::get('/estimates/{ulid}', [PublicViewController::class, 'showEstimate'])->name('estimates.public');

// PDF download routes (no authentication required)
Route::get('/invoices/{ulid}/pdf', [PublicViewController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
Route::get('/estimates/{ulid}/pdf', [PublicViewController::class, 'downloadEstimatePdf'])->name('estimates.pdf');

// Protected application routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Redirect root to dashboard
    Route::get('/', function () {
        return redirect('/dashboard');
    });

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Main application routes (protected)
    Route::get('/companies', CompanyManager::class)->name('companies.index');
    Route::get('/customers', CustomerManager::class)->name('customers.index');
    Route::get('/invoices', InvoiceWizard::class)->name('invoices.index');
    Route::get('/team/settings', TeamSettings::class)->name('team.settings');
});

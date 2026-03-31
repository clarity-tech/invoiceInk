<?php

use App\Http\Controllers\PublicViewController;
use App\Livewire\CompanyManager;
use App\Livewire\CustomerManager;
use App\Livewire\InvoiceWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Application routes
Route::get('/companies', CompanyManager::class)->name('companies.index');
Route::get('/customers', CustomerManager::class)->name('customers.index');
Route::get('/invoices', InvoiceWizard::class)->name('invoices.index');

// Public view routes for invoices and estimates
Route::get('/invoices/{ulid}', [PublicViewController::class, 'showInvoice'])->name('invoices.public');
Route::get('/estimates/{ulid}', [PublicViewController::class, 'showEstimate'])->name('estimates.public');

// PDF download routes
Route::get('/invoices/{ulid}/pdf', [PublicViewController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
Route::get('/estimates/{ulid}/pdf', [PublicViewController::class, 'downloadEstimatePdf'])->name('estimates.pdf');

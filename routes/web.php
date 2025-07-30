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
Route::get('/invoices/{uuid}', [PublicViewController::class, 'showInvoice'])->name('invoices.public');
Route::get('/estimates/{uuid}', [PublicViewController::class, 'showEstimate'])->name('estimates.public');

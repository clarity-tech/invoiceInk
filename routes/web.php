<?php

use App\Http\Controllers\CurrentTeamController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\PublicViewController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\UserProfileController;
use App\Livewire\CustomerManager;
use App\Livewire\InvoiceForm;
use App\Livewire\InvoiceList;
use App\Livewire\NumberingSeriesManager;
use App\Livewire\OrganizationManager;
use App\Livewire\OrganizationSetup;
use Illuminate\Support\Facades\Route;

// Homepage (public landing for guests, redirect to dashboard for authenticated users)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return view('welcome');
});

// Terms and Privacy Policy
Route::get('/terms-of-service', [TermsOfServiceController::class, 'show'])->name('terms.show');
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('policy.show');

// Public view routes for invoices and estimates (no authentication required, rate limited)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/invoices/view/{ulid}', [PublicViewController::class, 'showInvoice'])->name('invoices.public');
    Route::get('/estimates/view/{ulid}', [PublicViewController::class, 'showEstimate'])->name('estimates.public');
});

// PDF download routes (no authentication required, stricter rate limit due to resource cost)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/invoices/{ulid}/pdf', [PublicViewController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
    Route::get('/estimates/{ulid}/pdf', [PublicViewController::class, 'downloadEstimatePdf'])->name('estimates.pdf');
});

// Protected application routes
Route::middleware([
    'auth:sanctum',
    \App\Http\Middleware\AuthenticateSession::class,
    'verified',
])->group(function () {
    // Profile management
    Route::get('/user/profile', [UserProfileController::class, 'show'])->name('profile.show');

    // Team management
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('/current-team', [CurrentTeamController::class, 'update'])->name('current-team.update');

    // Team invitations (signed URL)
    Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
        ->middleware(['signed'])
        ->name('team-invitations.accept');

    // Organization setup wizard (should be accessible before main app features)
    Route::get('/organization/setup', OrganizationSetup::class)->name('organization.setup');

    // Routes that require organization setup completion
    Route::middleware(['organization.setup'])->group(function () {
        // Dashboard
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        // Main application routes (protected)
        Route::get('/organizations', OrganizationManager::class)->name('organizations.index');
        Route::get('/organization/edit', OrganizationManager::class)->name('organization.edit');
        Route::get('/customers', CustomerManager::class)->name('customers.index');

        // Invoice management routes
        Route::get('/invoices', InvoiceList::class)->name('invoices.index');
        Route::get('/invoices/create', InvoiceForm::class)->name('invoices.create');

        Route::get('/invoices/{invoice}/edit', InvoiceForm::class)->name('invoices.edit');
        Route::get('/estimates/create', InvoiceForm::class)->name('estimates.create');

        Route::get('/numbering-series', NumberingSeriesManager::class)->name('numbering-series.index');
    });
});

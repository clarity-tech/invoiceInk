<?php

use App\Http\Controllers\CurrentTeamController;
use App\Http\Controllers\CurrentUserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NumberingSeriesController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSetupController;
use App\Http\Controllers\OtherBrowserSessionsController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\PublicViewController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\UserProfileController;
use App\Http\Middleware\AuthenticateSession;
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
})->where('ulid', '[0-9A-HJ-KM-NP-TV-Za-hj-km-np-tv-z]{26}');

// PDF download routes (no authentication required, stricter rate limit due to resource cost)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/invoices/{ulid}/pdf', [PublicViewController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
    Route::get('/estimates/{ulid}/pdf', [PublicViewController::class, 'downloadEstimatePdf'])->name('estimates.pdf');
})->where('ulid', '[0-9A-HJ-KM-NP-TV-Za-hj-km-np-tv-z]{26}');

// Protected application routes
Route::middleware([
    'auth:sanctum',
    AuthenticateSession::class,
    'verified',
])->group(function () {
    // Profile management
    Route::get('/user/profile', [UserProfileController::class, 'show'])->name('profile.show');
    Route::delete('/user/other-browser-sessions', [OtherBrowserSessionsController::class, 'destroy'])->name('other-browser-sessions.destroy');
    Route::delete('/user', [CurrentUserController::class, 'destroy'])->name('current-user.destroy');

    // Team management
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.store');
    Route::put('/teams/{team}/members/{user}', [TeamController::class, 'updateMemberRole'])->name('teams.members.update');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');
    Route::delete('/teams/{team}/invitations/{invitation}', [TeamController::class, 'cancelInvitation'])->name('teams.invitations.destroy');
    Route::put('/current-team', [CurrentTeamController::class, 'update'])->name('current-team.update');

    // Team invitations (signed URL)
    Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
        ->middleware(['signed'])
        ->name('team-invitations.accept');

    // Organization setup wizard (should be accessible before main app features)
    Route::get('/organization/setup', [OrganizationSetupController::class, 'show'])->name('organization.setup');
    Route::post('/organization/setup/{organization}/step', [OrganizationSetupController::class, 'saveStep'])->name('organization.setup.save-step');

    // Routes that require organization setup completion
    Route::middleware(['organization.setup'])->group(function () {
        // Dashboard
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Organization management
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('/organization/edit', [OrganizationController::class, 'index'])->name('organization.edit');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
        Route::put('/organizations/{organization}/location', [OrganizationController::class, 'updateLocation'])->name('organizations.update-location');
        Route::put('/organizations/{organization}/bank-details', [OrganizationController::class, 'updateBankDetails'])->name('organizations.update-bank-details');
        Route::post('/organizations/{organization}/logo', [OrganizationController::class, 'uploadLogo'])->name('organizations.upload-logo');
        Route::delete('/organizations/{organization}/logo', [OrganizationController::class, 'removeLogo'])->name('organizations.remove-logo');
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

        // Customer management
        Route::resource('customers', CustomerController::class)->except(['show', 'create', 'edit']);
        Route::post('customers/{customer}/locations', [CustomerController::class, 'storeLocation'])->name('customers.locations.store');
        Route::put('customers/{customer}/locations/{location}', [CustomerController::class, 'updateLocation'])->name('customers.locations.update');
        Route::delete('customers/{customer}/locations/{location}', [CustomerController::class, 'destroyLocation'])->name('customers.locations.destroy');
        Route::post('customers/{customer}/primary-location/{location}', [CustomerController::class, 'setPrimaryLocation'])->name('customers.primary-location');

        // Invoice management routes
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::post('/invoices/{invoice}/convert', [InvoiceController::class, 'convertEstimate'])->name('invoices.convert');
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::get('/estimates/create', [InvoiceController::class, 'create'])->name('estimates.create');
        Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send-email');

        // Numbering series management
        Route::get('/numbering-series', [NumberingSeriesController::class, 'index'])->name('numbering-series.index');
        Route::post('/numbering-series', [NumberingSeriesController::class, 'store'])->name('numbering-series.store');
        Route::post('/numbering-series/preview', [NumberingSeriesController::class, 'preview'])->name('numbering-series.preview');
        Route::put('/numbering-series/{series}', [NumberingSeriesController::class, 'update'])->name('numbering-series.update');
        Route::delete('/numbering-series/{series}', [NumberingSeriesController::class, 'destroy'])->name('numbering-series.destroy');
        Route::post('/numbering-series/{series}/toggle-active', [NumberingSeriesController::class, 'toggleActive'])->name('numbering-series.toggle-active');
        Route::post('/numbering-series/{series}/set-default', [NumberingSeriesController::class, 'setDefault'])->name('numbering-series.set-default');

        // Email Templates
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/{type}', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{type}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::delete('/email-templates/{type}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');
        Route::get('/api/email-templates/resolve', [EmailTemplateController::class, 'resolve'])->name('email-templates.resolve');
        Route::post('/api/email-templates/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    });
});

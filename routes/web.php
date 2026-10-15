<?php

use App\Http\Controllers\AiConnectionController;
use App\Http\Controllers\CurrentTeamController;
use App\Http\Controllers\CurrentUserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerReminderController;
use App\Http\Controllers\CustomerReminderDraftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceDraftController;
use App\Http\Controllers\InvoiceEmailDraftController;
use App\Http\Controllers\LineItemSuggestionController;
use App\Http\Controllers\NumberingSeriesController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSetupController;
use App\Http\Controllers\OtherBrowserSessionsController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\PublicViewController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\UserProfileController;
use App\Http\Middleware\AuthenticateSession;
use App\Http\Middleware\ValidateShareLink;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect('/dashboard') : view('welcome'));

// Terms and Privacy Policy
Route::get('/terms-of-service', [TermsOfServiceController::class, 'show'])->name('terms.show');
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('policy.show');

// Team invitation acceptance (signed URL). Reachable while unauthenticated so a
// brand-new invitee can register through the accept flow; the controller branches
// on whether a user already exists for the invited email.
Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed'])
    ->name('team-invitations.accept');

// Public view routes for invoices and estimates (no authentication required, rate limited).
// Reached only by a signed, expiring link -- see Invoice::shareUrl().
//
// The ULID pattern is set before group() so it reaches the routes; chained after
// it, as it used to be, it constrained nothing.
Route::middleware(['throttle:60,1', ValidateShareLink::class])->where(['ulid' => '[0-9A-HJ-KM-NP-TV-Za-hj-km-np-tv-z]{26}'])->group(function () {
    Route::get('/invoices/view/{ulid}', [PublicViewController::class, 'showInvoice'])->name('invoices.public');
    Route::get('/estimates/view/{ulid}', [PublicViewController::class, 'showEstimate'])->name('estimates.public');
});

// PDF download routes (no authentication required, stricter rate limit due to resource cost).
// Signed like the pages above; signed-in users download through invoices.download.
Route::middleware(['throttle:10,1', ValidateShareLink::class])->where(['ulid' => '[0-9A-HJ-KM-NP-TV-Za-hj-km-np-tv-z]{26}'])->group(function () {
    Route::get('/invoices/{ulid}/pdf', [PublicViewController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
    Route::get('/estimates/{ulid}/pdf', [PublicViewController::class, 'downloadEstimatePdf'])->name('estimates.pdf');
});

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

    // Organization setup wizard (should be accessible before main app features)
    Route::get('/organization/setup', [OrganizationSetupController::class, 'show'])->name('organization.setup');
    Route::post('/organization/setup/{organization}/step', [OrganizationSetupController::class, 'saveStep'])->name('organization.setup.save-step');

    // Routes that require organization setup completion
    Route::middleware(['organization.setup'])->group(function () {
        // Dashboard
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Organization management
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
        Route::get('/organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
        Route::put('/organizations/{organization}/location', [OrganizationController::class, 'updateLocation'])->name('organizations.update-location');
        Route::put('/organizations/{organization}/bank-details', [OrganizationController::class, 'updateBankDetails'])->name('organizations.update-bank-details');
        Route::post('/organizations/{organization}/logo', [OrganizationController::class, 'uploadLogo'])->name('organizations.upload-logo');
        Route::delete('/organizations/{organization}/logo', [OrganizationController::class, 'removeLogo'])->name('organizations.remove-logo');
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

        // Customer management
        Route::get('customers/trash', [CustomerController::class, 'trash'])->name('customers.trash');
        Route::post('customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore')->withTrashed();
        Route::delete('customers/{customer}/force', [CustomerController::class, 'forceDestroy'])->name('customers.force-destroy')->withTrashed();
        // `customers/trash` is registered above so it still wins over the
        // `customers/{customer}` pattern that `show` introduces.
        Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
        Route::post('customers/{customer}/locations', [CustomerController::class, 'storeLocation'])->name('customers.locations.store');
        Route::put('customers/{customer}/locations/{location}', [CustomerController::class, 'updateLocation'])->name('customers.locations.update');
        Route::delete('customers/{customer}/locations/{location}', [CustomerController::class, 'destroyLocation'])->name('customers.locations.destroy');
        Route::post('customers/{customer}/locations/{location}/restore', [CustomerController::class, 'restoreLocation'])->name('customers.locations.restore')->withTrashed();
        Route::delete('customers/{customer}/locations/{location}/force', [CustomerController::class, 'forceDestroyLocation'])->name('customers.locations.force-destroy')->withTrashed();
        Route::post('customers/{customer}/primary-location/{location}', [CustomerController::class, 'setPrimaryLocation'])->name('customers.primary-location');
        Route::post('customers/{customer}/send-reminder', [CustomerReminderController::class, 'store'])->name('customers.send-reminder');
        Route::post('customers/{customer}/reminder-draft', [CustomerReminderDraftController::class, 'store'])->middleware('throttle:email-drafts')->name('customers.reminder-draft');

        // Invoice management routes
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/trash', [InvoiceController::class, 'trash'])->name('invoices.trash');
        Route::post('/invoices/{invoice}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore')->withTrashed();
        Route::delete('/invoices/{invoice}/force', [InvoiceController::class, 'forceDestroy'])->name('invoices.force-destroy')->withTrashed();
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        // A GET: duplicating only opens a pre-filled create form, it saves nothing.
        Route::get('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::post('/invoices/{invoice}/convert', [InvoiceController::class, 'convertEstimate'])->name('invoices.convert');
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::get('/invoices/{invoice}/attachments/{media}', [InvoiceController::class, 'showAttachment'])->name('invoices.attachments.show');
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices/draft', [InvoiceDraftController::class, 'store'])->middleware('throttle:invoice-drafts')->name('invoices.draft');
        Route::post('/invoices/item-suggestion', [LineItemSuggestionController::class, 'store'])->middleware('throttle:item-suggestions')->name('invoices.item-suggestion');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        // Deliberately registered below /invoices/create and /invoices/trash, and
        // constrained to a numeric id: a bare {invoice} placeholder matches those
        // literal paths too, which would turn both into 404s.
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->whereNumber('invoice');
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::get('/estimates/create', [InvoiceController::class, 'create'])->name('estimates.create');
        Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send-email');
        Route::post('/invoices/{invoice}/email-draft', [InvoiceEmailDraftController::class, 'store'])->middleware('throttle:email-drafts')->name('invoices.email-draft');
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::delete('/invoices/{invoice}/payments/{payment}', [InvoiceController::class, 'deletePayment'])->name('invoices.payments.destroy');

        // Numbering series management (managed via Organization edit; CRUD routes only)
        Route::post('/numbering-series', [NumberingSeriesController::class, 'store'])->name('numbering-series.store');
        Route::post('/numbering-series/preview', [NumberingSeriesController::class, 'preview'])->name('numbering-series.preview');
        Route::put('/numbering-series/{series}', [NumberingSeriesController::class, 'update'])->name('numbering-series.update');
        Route::delete('/numbering-series/{series}', [NumberingSeriesController::class, 'destroy'])->name('numbering-series.destroy');
        Route::post('/numbering-series/{series}/toggle-active', [NumberingSeriesController::class, 'toggleActive'])->name('numbering-series.toggle-active');
        Route::post('/numbering-series/{series}/set-default', [NumberingSeriesController::class, 'setDefault'])->name('numbering-series.set-default');

        // Settings Hub
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

        // Connect AI tools — the discovery surface for the MCP server: how to
        // connect, the apps signed in over OAuth, and scoped API tokens.
        Route::get('/settings/ai-connection', [AiConnectionController::class, 'index'])->name('ai-connection.index');
        Route::post('/settings/ai-connection', [AiConnectionController::class, 'store'])->name('ai-connection.store');
        Route::delete('/settings/ai-connection/apps/{client}', [AiConnectionController::class, 'disconnect'])->name('ai-connection.disconnect');
        Route::delete('/settings/ai-connection/{token}', [AiConnectionController::class, 'destroy'])->name('ai-connection.destroy');

        // Email Templates
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/{type}', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{type}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::delete('/email-templates/{type}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');
        Route::get('/api/email-templates/resolve', [EmailTemplateController::class, 'resolve'])->name('email-templates.resolve');
        Route::post('/api/email-templates/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    });
});

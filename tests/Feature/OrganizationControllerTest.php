<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    $this->user = createUserWithTeam();
    $this->actingAs($this->user);
});

test('organizations index always shows the list, even for single org user', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->get('/organizations');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Index')
        ->has('organizations.data', 1)
        ->where('organizations.data.0.id', $organization->id)
    );
});

test('can render organization show page', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->get("/organizations/{$organization->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Show')
        ->has('organization')
        ->where('taxLabels.IN', 'GSTIN')
        ->where('taxLabels.US', 'EIN')
    );
});

test('can render organization edit page', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->get("/organizations/{$organization->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Edit')
        ->has('organization')
        ->has('countries')
        ->has('currencies')
        ->where('tab', 'basics')
    );
});

test('edit page respects tab query param', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->get("/organizations/{$organization->id}/edit?tab=bank");

    $response->assertInertia(fn ($page) => $page->where('tab', 'bank'));
});

test('can update organization basics', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Updated Org Name',
        'phone' => '+1-555-9999',
        'emails' => ['updated@test.com'],
        'currency' => 'USD',
        'country_code' => 'US',
        'financial_year_type' => 'january_december',
        'financial_year_start_month' => 1,
        'financial_year_start_day' => 1,
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('Updated Org Name');
    expect($organization->phone)->toBe('+1-555-9999');
    expect($organization->emails->getFirstEmail())->toBe('updated@test.com');
    expect($organization->currency->value)->toBe('USD');
});

test('accepts a website without a scheme and stores it as a full url', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $this->put("/organizations/{$organization->id}", [
        'name' => 'Updated Org Name',
        'emails' => ['updated@test.com'],
        'currency' => 'INR',
        'country_code' => 'IN',
        'website' => 'example.com',
    ])->assertSessionHasNoErrors();

    expect($organization->refresh()->website)->toBe('https://example.com');
});

test('can update the company name independently of the organization name', function () {
    $organization = createOrganizationWithLocation(['name' => 'Acme Workspace', 'company_name' => 'Old Co'], [], $this->user);

    $this->put("/organizations/{$organization->id}", [
        'name' => 'Acme Workspace',
        'company_name' => 'Acme Manufacturing Pvt Ltd',
        'emails' => ['ops@acme.test'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ])->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('Acme Workspace');
    expect($organization->company_name)->toBe('Acme Manufacturing Pvt Ltd');
});

test('omitting company_name leaves it unchanged', function () {
    $organization = createOrganizationWithLocation(['company_name' => 'Keep Me Ltd'], [], $this->user);

    $this->put("/organizations/{$organization->id}", [
        'name' => 'Renamed Workspace',
        'emails' => ['ops@acme.test'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ])->assertRedirect();

    expect($organization->refresh()->company_name)->toBe('Keep Me Ltd');
});

test('validates required fields when updating organization', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => '',
        'emails' => [],
        'currency' => '',
        'country_code' => '',
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('validates email format when updating', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Test Org',
        'emails' => ['invalid-email'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertSessionHasErrors(['emails.0']);
});

test('requires at least one non-empty email', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Test Org',
        'emails' => [''],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertSessionHasErrors(['emails.0']);
});

test('can update organization location', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/location", [
        'location_name' => 'New Office',
        'tax_id' => 'NEWGSTIN123',
        'address_line_1' => '456 New St',
        'address_line_2' => 'Suite 200',
        'city' => 'New City',
        'state' => 'New State',
        'country' => 'IN',
        'postal_code' => '99999',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    $location = $organization->primaryLocation;
    expect($location->name)->toBe('New Office');
    expect($location->tax_id)->toBe('NEWGSTIN123');
    expect($location->address_line_1)->toBe('456 New St');
    expect($location->city)->toBe('New City');
});

test('validates location required fields', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/location", [
        'address_line_1' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'postal_code' => '',
    ]);

    $response->assertSessionHasErrors(['address_line_1', 'city', 'state', 'country', 'postal_code']);
});

test('can update bank details', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/bank-details", [
        'bank_account_name' => 'Test Account',
        'bank_account_number' => '1234567890',
        'bank_name' => 'Test Bank',
        'bank_ifsc' => 'TEST0001234',
        'bank_branch' => 'Main Branch',
        'bank_swift' => 'TESTSWFT',
        'bank_pan' => 'ABCDE1234F',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->bank_details->accountName)->toBe('Test Account');
    expect($organization->bank_details->bankName)->toBe('Test Bank');
    expect($organization->bank_details->ifsc)->toBe('TEST0001234');
});

test('can delete organization when user has more than one', function () {
    // First org from createUserWithTeam in beforeEach is the current team.
    // Create a second owned org so the deletion is not "the last one".
    $secondOrg = Organization::factory()->completedSetup()->create([
        'user_id' => $this->user->id,
    ]);
    $secondOrgId = $secondOrg->id;

    $response = $this->delete("/organizations/{$secondOrgId}");

    $response->assertRedirect('/organizations');
    // Soft delete: hidden from normal queries but recoverable.
    expect(Organization::find($secondOrgId))->toBeNull();
    $this->assertSoftDeleted('teams', ['id' => $secondOrgId]);
    expect(Organization::withTrashed()->find($secondOrgId))->not->toBeNull();
});

test('cannot delete the only owned organization', function () {
    $onlyOrg = $this->user->currentTeam;
    $onlyOrg->markSetupComplete();

    $response = $this->delete("/organizations/{$onlyOrg->id}");

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(Organization::find($onlyOrg->id))->not->toBeNull();
});

test('deleting current org switches to remaining owned org', function () {
    $current = $this->user->currentTeam;
    $current->markSetupComplete();

    $remaining = Organization::factory()->completedSetup()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->delete("/organizations/{$current->id}");

    $response->assertRedirect('/organizations');
    expect(Organization::find($current->id))->toBeNull();
    $this->assertSoftDeleted('teams', ['id' => $current->id]);
    expect($this->user->fresh()->current_team_id)->toBe($remaining->id);
});

test('deleting an organization soft-deletes it and preserves its invoices and customers', function () {
    // Second org made current so helpers scope to it; give it a location so invoices are valid.
    $secondOrg = Organization::factory()->completedSetup()->create([
        'user_id' => $this->user->id,
    ]);
    $this->user->switchTeam($secondOrg);
    $this->actingAs($this->user->fresh());

    $secondOrg = createOrganizationWithLocation([], [], $this->user);
    $customer = createCustomerWithLocation([], [], $secondOrg);
    $invoice = createInvoiceWithItems([], null, $secondOrg, $customer);

    $response = $this->delete("/organizations/{$secondOrg->id}");

    $response->assertRedirect('/organizations');

    // Org is soft-deleted (recoverable), not cascade-wiped...
    $this->assertSoftDeleted('teams', ['id' => $secondOrg->id]);

    // ...and its child data still physically exists in the database. Drop ALL global
    // scopes (OrganizationScope is now pointed at the remaining org, plus SoftDeletes)
    // to assert the raw rows were not cascade-deleted.
    expect(Customer::withoutGlobalScopes()->whereKey($customer->id)->exists())->toBeTrue();
    expect(Invoice::withoutGlobalScopes()->whereKey($invoice->id)->exists())->toBeTrue();

    // It no longer appears among the user's owned organizations / switcher list.
    expect($this->user->fresh()->ownedTeams()->pluck('id'))->not->toContain($secondOrg->id);
});

test('cannot update organization user does not own', function () {
    $otherUser = createUserWithTeam();
    $otherOrg = createOrganizationWithLocation([], [], $otherUser);

    $response = $this->put("/organizations/{$otherOrg->id}", [
        'name' => 'Hacked Name',
        'emails' => ['hack@test.com'],
        'currency' => 'USD',
        'country_code' => 'US',
    ]);

    $response->assertStatus(403);
});

test('location country defaults from organization when empty', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/location", [
        'location_name' => '',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '12345',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->primaryLocation->name)->toBe($organization->name);
});

test('filters empty emails when updating', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Filter Test Org',
        'emails' => ['valid@test.com', '', 'another@test.com', '   '],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->emails->count())->toBe(2);
    expect($organization->emails->getEmails())->toContain('valid@test.com');
    expect($organization->emails->getEmails())->toContain('another@test.com');
});

test('can upload organization logo', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->post("/organizations/{$organization->id}/logo", [
        'logo' => $file,
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->getFirstMedia('logo'))->not->toBeNull();
    expect($organization->logo_url)->not->toBeNull();
});

test('can upload SVG organization logo', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $svg = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#000"/></svg>';
    $file = UploadedFile::fake()->createWithContent('logo.svg', $svg);

    $response = $this->post("/organizations/{$organization->id}/logo", [
        'logo' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $organization->refresh();
    expect($organization->getFirstMedia('logo'))->not->toBeNull();
});

test('sanitizes malicious SVG logo before storing', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $malicious = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)" width="100" height="100"><script>alert(document.cookie)</script><rect width="100" height="100"/></svg>';
    $file = UploadedFile::fake()->createWithContent('logo.svg', $malicious);

    $response = $this->post("/organizations/{$organization->id}/logo", [
        'logo' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $organization->refresh();
    $media = $organization->getFirstMedia('logo');
    $stored = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());

    expect($stored)
        ->not->toContain('<script')
        ->not->toContain('onload')
        ->not->toContain('alert(')
        ->toContain('<rect');
});

test('validates logo file type', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->post("/organizations/{$organization->id}/logo", [
        'logo' => $file,
    ]);

    $response->assertSessionHasErrors(['logo']);
});

test('validates logo file size', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $file = UploadedFile::fake()->image('large.png')->size(3000);

    $response = $this->post("/organizations/{$organization->id}/logo", [
        'logo' => $file,
    ]);

    $response->assertSessionHasErrors(['logo']);
});

test('can remove organization logo', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    // First upload a logo
    $file = UploadedFile::fake()->image('logo.png', 100, 100);
    $this->post("/organizations/{$organization->id}/logo", ['logo' => $file]);

    $organization->refresh();
    expect($organization->getFirstMedia('logo'))->not->toBeNull();

    // Then remove it
    $response = $this->delete("/organizations/{$organization->id}/logo");

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->getFirstMedia('logo'))->toBeNull();
});

test('cannot upload logo for organization user does not own', function () {
    $otherUser = createUserWithTeam();
    $otherOrg = createOrganizationWithLocation([], [], $otherUser);

    $file = UploadedFile::fake()->image('logo.png', 100, 100);

    $response = $this->post("/organizations/{$otherOrg->id}/logo", [
        'logo' => $file,
    ]);

    $response->assertStatus(403);
});

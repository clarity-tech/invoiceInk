<?php

use App\Models\InvoiceNumberingSeries;
use App\Models\User;

test('numbering series index redirects to email templates', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $response = $this->get('/numbering-series');

    $response->assertRedirect('/email-templates');
});

test('can create numbering series', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $organization = $user->currentTeam;

    $this->post('/numbering-series', [
        'organization_id' => $organization->id,
        'name' => 'Test Series',
        'prefix' => 'TST',
        'format_pattern' => '{PREFIX}{YEAR}{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => 'yearly',
        'is_active' => true,
        'is_default' => false,
    ])->assertRedirect();

    $this->assertDatabaseHas('invoice_numbering_series', [
        'organization_id' => $organization->id,
        'name' => 'Test Series',
        'prefix' => 'TST',
    ]);
});

test('can update numbering series', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $user->currentTeam->id,
        'name' => 'Original',
        'prefix' => 'ORG',
    ]);

    $this->put("/numbering-series/{$series->id}", [
        'organization_id' => $user->currentTeam->id,
        'name' => 'Updated',
        'prefix' => 'UPD',
        'format_pattern' => '{PREFIX}{YEAR}{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => 'yearly',
        'is_active' => true,
        'is_default' => false,
    ])->assertRedirect();

    $this->assertDatabaseHas('invoice_numbering_series', [
        'id' => $series->id,
        'name' => 'Updated',
        'prefix' => 'UPD',
    ]);
});

test('can delete numbering series without invoices', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $user->currentTeam->id,
    ]);

    $this->delete("/numbering-series/{$series->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('invoice_numbering_series', ['id' => $series->id]);
});

test('cannot delete numbering series with invoices', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $organization = createOrganizationWithLocation(user: $user);
    $customer = createCustomerWithLocation([], [], $organization);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $organization->id,
    ]);

    createInvoiceWithItems([
        'invoice_numbering_series_id' => $series->id,
    ], null, $organization, $customer);

    $this->delete("/numbering-series/{$series->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('invoice_numbering_series', ['id' => $series->id]);
});

test('can toggle active status', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $user->currentTeam->id,
        'is_active' => true,
    ]);

    $this->post("/numbering-series/{$series->id}/toggle-active")
        ->assertRedirect();

    $this->assertDatabaseHas('invoice_numbering_series', [
        'id' => $series->id,
        'is_active' => false,
    ]);
});

test('can set series as default', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $series1 = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $user->currentTeam->id,
        'is_default' => true,
    ]);

    $series2 = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $user->currentTeam->id,
        'is_default' => false,
    ]);

    $this->post("/numbering-series/{$series2->id}/set-default")
        ->assertRedirect();

    $this->assertDatabaseHas('invoice_numbering_series', [
        'id' => $series1->id,
        'is_default' => false,
    ]);

    $this->assertDatabaseHas('invoice_numbering_series', [
        'id' => $series2->id,
        'is_default' => true,
    ]);
});

test('can preview next number', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $response = $this->postJson('/numbering-series/preview', [
        'organization_id' => $user->currentTeam->id,
        'prefix' => 'TST',
        'format_pattern' => '{PREFIX}{YEAR}{SEQUENCE:4}',
        'current_number' => 5,
        'reset_frequency' => 'yearly',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['preview']);

    $preview = $response->json('preview');
    expect($preview)->toContain('TST');
    expect($preview)->toContain('0006');
});

test('validation works for create', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($user);

    $this->post('/numbering-series', [
        'name' => '',
        'prefix' => '',
        'format_pattern' => '',
    ])->assertSessionHasErrors(['organization_id', 'name', 'prefix', 'format_pattern']);
});

test('unauthorized user cannot update series from other organization', function () {
    $user1 = User::factory()->withPersonalTeam()->create();
    $user2 = User::factory()->withPersonalTeam()->create();

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $user1->currentTeam->id,
    ]);

    $this->actingAs($user2);

    $this->put("/numbering-series/{$series->id}", [
        'organization_id' => $user1->currentTeam->id,
        'name' => 'Hacked',
        'prefix' => 'HCK',
        'format_pattern' => '{PREFIX}{SEQUENCE}',
        'current_number' => 0,
        'reset_frequency' => 'yearly',
    ])->assertNotFound();
});

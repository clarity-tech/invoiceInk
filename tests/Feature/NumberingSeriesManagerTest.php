<?php

namespace Tests\Feature;

use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NumberingSeriesManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($this->user);
    }

    public function test_can_render_numbering_series_manager(): void
    {
        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->assertOk();
        $component->assertSee('Numbering Series');
    }

    public function test_can_create_numbering_series(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('create')
            ->set('organization_id', $organization->id)
            ->set('name', 'Test Series')
            ->set('prefix', 'TST')
            ->set('format_pattern', '{PREFIX}{YEAR}{SEQUENCE:4}')
            ->set('current_number', 0)
            ->set('reset_frequency', ResetFrequency::YEARLY)
            ->set('is_active', true)
            ->set('is_default', false)
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertSet('showCreateForm', false);

        $this->assertDatabaseHas('invoice_numbering_series', [
            'organization_id' => $organization->id,
            'name' => 'Test Series',
            'prefix' => 'TST',
            'format_pattern' => '{PREFIX}{YEAR}{SEQUENCE:4}',
            'current_number' => 0,
            'reset_frequency' => ResetFrequency::YEARLY->value,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    public function test_can_edit_numbering_series(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;
        $series = InvoiceNumberingSeries::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Original Series',
            'prefix' => 'ORG',
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('edit', $series)
            ->assertSet('editingId', $series->id)
            ->assertSet('name', 'Original Series')
            ->assertSet('prefix', 'ORG')
            ->set('name', 'Updated Series')
            ->set('prefix', 'UPD')
            ->call('save');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('invoice_numbering_series', [
            'id' => $series->id,
            'name' => 'Updated Series',
            'prefix' => 'UPD',
        ]);
    }

    public function test_can_delete_numbering_series(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;
        $series = InvoiceNumberingSeries::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('delete', $series);

        $this->assertDatabaseMissing('invoice_numbering_series', [
            'id' => $series->id,
        ]);
    }

    public function test_can_toggle_active_status(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;
        $series = InvoiceNumberingSeries::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('toggleActive', $series);

        $this->assertDatabaseHas('invoice_numbering_series', [
            'id' => $series->id,
            'is_active' => false,
        ]);
    }

    public function test_can_set_as_default(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;
        $series1 = InvoiceNumberingSeries::factory()->create([
            'organization_id' => $organization->id,
            'is_default' => true,
        ]);
        $series2 = InvoiceNumberingSeries::factory()->create([
            'organization_id' => $organization->id,
            'is_default' => false,
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('setAsDefault', $series2);

        $this->assertDatabaseHas('invoice_numbering_series', [
            'id' => $series1->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('invoice_numbering_series', [
            'id' => $series2->id,
            'is_default' => true,
        ]);
    }

    public function test_validation_works_correctly(): void
    {
        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('create')
            ->set('prefix', '') // Clear the default value
            ->set('format_pattern', '') // Clear the default value
            ->call('save')
            ->assertHasErrors(['organization_id', 'name', 'prefix', 'format_pattern']);
    }

    public function test_next_number_preview_works(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('create')
            ->set('organization_id', $organization->id)
            ->set('prefix', 'TST')
            ->set('format_pattern', '{PREFIX}{YEAR}{SEQUENCE:4}')
            ->set('current_number', 5);

        $preview = $component->get('nextNumberPreview');

        $this->assertStringContainsString('TST', $preview);
        $this->assertStringContainsString('0006', $preview);
    }

    public function test_next_number_preview_with_financial_year(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;

        // Set up financial year for the organization
        $organization->update([
            'financial_year_type' => \App\Enums\FinancialYearType::APRIL_MARCH,
            'country_code' => 'IN',
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $component->call('create')
            ->set('organization_id', $organization->id)
            ->set('prefix', 'INV')
            ->set('format_pattern', '{PREFIX}{FY}{SEQUENCE:4}')
            ->set('current_number', 5);

        $preview = $component->get('nextNumberPreview');

        // Preview should show the actual financial year start year only, not {FY}
        $this->assertStringContainsString('INV', $preview);
        $this->assertStringNotContainsString('{FY}', $preview);
        $this->assertStringContainsString('0006', $preview);

        // Verify it contains a valid format with just the start year (e.g., "INV20250006")
        $this->assertMatchesRegularExpression('/INV\d{4}0006/', $preview);
    }

    public function test_financial_year_token_formats(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;

        // Set up financial year for the organization
        $organization->update([
            'financial_year_type' => \App\Enums\FinancialYearType::APRIL_MARCH,
            'country_code' => 'IN',
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        // Test {FY} - should show only start year
        $component->call('create')
            ->set('organization_id', $organization->id)
            ->set('prefix', 'INV')
            ->set('format_pattern', '{PREFIX}{FY}{SEQUENCE:4}')
            ->set('current_number', 0);
        $preview1 = $component->get('nextNumberPreview');
        $this->assertMatchesRegularExpression('/INV\d{4}0001/', $preview1);

        // Test {FY_FULL} - should show short format (2024-25) - includes dash in token value
        $component->set('format_pattern', '{PREFIX}{FY_FULL}{SEQUENCE:4}');
        $preview2 = $component->get('nextNumberPreview');
        $this->assertMatchesRegularExpression('/INV\d{4}-\d{2}0001/', $preview2);

        // Test {FY_RANGE} - should show full format (2024-2025) - includes dash in token value
        $component->set('format_pattern', '{PREFIX}{FY_RANGE}{SEQUENCE:4}');
        $preview3 = $component->get('nextNumberPreview');
        $this->assertMatchesRegularExpression('/INV\d{4}-\d{4}0001/', $preview3);
    }

    public function test_computed_properties_work(): void
    {
        // Use the authenticated user's current team as organization
        $organization = $this->user->currentTeam;
        InvoiceNumberingSeries::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $component = Livewire::test(\App\Livewire\NumberingSeriesManager::class);

        $organizations = $component->get('organizations');
        $series = $component->get('series');
        $resetFrequencyOptions = $component->get('resetFrequencyOptions');

        $this->assertNotEmpty($organizations);
        $this->assertNotEmpty($series);
        $this->assertNotEmpty($resetFrequencyOptions);
    }
}

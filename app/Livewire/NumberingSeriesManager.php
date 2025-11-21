<?php

namespace App\Livewire;

use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Services\InvoiceNumberingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class NumberingSeriesManager extends Component
{
    use WithPagination;

    public bool $showCreateForm = false;

    public ?int $editingId = null;

    // Form fields
    #[Rule('required|exists:teams,id')]
    public ?int $organization_id = null;

    #[Rule('nullable|exists:locations,id')]
    public ?int $location_id = null;

    #[Rule('required|string|max:100')]
    public string $name = '';

    #[Rule('required|string|max:20')]
    public string $prefix = '';

    #[Rule('required|string|max:100')]
    public string $format_pattern = '';

    #[Rule('required|integer|min:0')]
    public int $current_number = 0;

    #[Rule('required')]
    public ResetFrequency $reset_frequency = ResetFrequency::YEARLY;

    #[Rule('boolean')]
    public bool $is_active = true;

    #[Rule('boolean')]
    public bool $is_default = false;

    private InvoiceNumberingService $numberingService;

    public function boot(InvoiceNumberingService $numberingService): void
    {
        $this->numberingService = $numberingService;
    }

    public function mount(): void
    {
        $this->reset_frequency = ResetFrequency::YEARLY;
        $this->format_pattern = '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}';
        $this->prefix = 'INV';
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function edit(InvoiceNumberingSeries $series): void
    {
        // Security check: Ensure user has access to this series' organization
        if (! auth()->user()->allTeams()->contains('id', $series->organization_id)) {
            abort(403, 'Unauthorized access to numbering series.');
        }

        $this->editingId = $series->id;
        $this->organization_id = $series->organization_id;
        $this->location_id = $series->location_id;
        $this->name = $series->name;
        $this->prefix = $series->prefix;
        $this->format_pattern = $series->format_pattern;
        $this->current_number = $series->current_number;
        $this->reset_frequency = $series->reset_frequency;
        $this->is_active = $series->is_active;
        $this->is_default = $series->is_default;
        $this->showCreateForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'organization_id' => $this->organization_id,
            'location_id' => $this->location_id,
            'name' => $this->name,
            'prefix' => $this->prefix,
            'format_pattern' => $this->format_pattern,
            'current_number' => $this->current_number,
            'reset_frequency' => $this->reset_frequency,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
        ];

        if ($this->editingId) {
            $series = InvoiceNumberingSeries::findOrFail($this->editingId);
            $series->update($data);
            $message = 'Numbering series updated successfully!';
        } else {
            InvoiceNumberingSeries::create($data);
            $message = 'Numbering series created successfully!';
        }

        $this->resetForm();
        $this->showCreateForm = false;
        $this->resetPage();
        session()->flash('message', $message);
    }

    public function delete(InvoiceNumberingSeries $series): void
    {
        // Security check: Ensure user has access to this series' organization
        if (! auth()->user()->allTeams()->contains('id', $series->organization_id)) {
            abort(403, 'Unauthorized access to numbering series.');
        }

        // Prevent deletion if series has invoices
        if ($series->invoices()->exists()) {
            session()->flash('error', 'Cannot delete numbering series that has associated invoices.');

            return;
        }

        $series->delete();
        $this->resetPage();
        session()->flash('message', 'Numbering series deleted successfully!');
    }

    public function toggleActive(InvoiceNumberingSeries $series): void
    {
        // Security check: Ensure user has access to this series' organization
        if (! auth()->user()->allTeams()->contains('id', $series->organization_id)) {
            abort(403, 'Unauthorized access to numbering series.');
        }

        $series->update(['is_active' => ! $series->is_active]);
        $status = $series->is_active ? 'activated' : 'deactivated';
        session()->flash('message', "Numbering series {$status} successfully!");
    }

    public function setAsDefault(InvoiceNumberingSeries $series): void
    {
        // Security check: Ensure user has access to this series' organization
        if (! auth()->user()->allTeams()->contains('id', $series->organization_id)) {
            abort(403, 'Unauthorized access to numbering series.');
        }

        // Remove default status from other series in the same organization
        InvoiceNumberingSeries::where('organization_id', $series->organization_id)
            ->update(['is_default' => false]);

        // Set this series as default
        $series->update(['is_default' => true]);

        session()->flash('message', 'Default numbering series updated successfully!');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showCreateForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->organization_id = null;
        $this->location_id = null;
        $this->name = '';
        $this->prefix = 'INV';
        $this->format_pattern = '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}';
        $this->current_number = 0;
        $this->reset_frequency = ResetFrequency::YEARLY;
        $this->is_active = true;
        $this->is_default = false;
        $this->resetValidation();
    }

    #[Computed]
    public function organizations()
    {
        // Only show organizations that the user has access to (their teams)
        if (! auth()->check()) {
            return collect();
        }

        // Get all teams the user is a member of or owns
        $userTeamIds = auth()->user()->allTeams()->pluck('id');

        return Organization::with('primaryLocation')
            ->whereIn('id', $userTeamIds)
            ->get();
    }

    #[Computed]
    public function organizationLocations()
    {
        if (! $this->organization_id) {
            return collect();
        }

        return Location::where('locatable_type', Organization::class)
            ->where('locatable_id', $this->organization_id)
            ->get();
    }

    #[Computed]
    public function automaticSeriesPreview()
    {
        if (! auth()->check() || ! auth()->user()->currentTeam) {
            return null;
        }

        $organization = auth()->user()->currentTeam;
        $numberingService = new InvoiceNumberingService;

        // Create a temporary series object to show what would be auto-created
        $tempSeries = new InvoiceNumberingSeries([
            'organization_id' => $organization->id,
            'location_id' => null,
            'name' => 'Default Invoice Series',
            'prefix' => 'INV',
            'current_number' => 0,
            'reset_frequency' => ResetFrequency::YEARLY,
            'is_active' => true,
            'is_default' => true,
        ]);

        // Set format pattern based on organization's financial year setup
        if ($organization->financial_year_type && $organization->country_code) {
            $tempSeries->format_pattern = '{PREFIX}-{FY}-{SEQUENCE:4}';
            $tempSeries->reset_frequency = ResetFrequency::FINANCIAL_YEAR;
        } else {
            $tempSeries->format_pattern = '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}';
            $tempSeries->reset_frequency = ResetFrequency::YEARLY;
        }

        // Set organization relationship for preview
        $tempSeries->setRelation('organization', $organization);

        return [
            'series' => $tempSeries,
            'preview_number' => $numberingService->previewNextNumber($tempSeries),
        ];
    }

    #[Computed]
    public function hasAnySeriesForCurrentOrg()
    {
        if (! auth()->check() || ! auth()->user()->currentTeam) {
            return false;
        }

        return InvoiceNumberingSeries::where('organization_id', auth()->user()->currentTeam->id)
            ->exists();
    }

    #[Computed]
    public function series()
    {
        // Only show numbering series for organizations the user has access to
        if (! auth()->check()) {
            return collect();
        }

        // Get all teams the user is a member of or owns
        $userTeamIds = auth()->user()->allTeams()->pluck('id');

        return InvoiceNumberingSeries::with(['organization', 'location'])
            ->whereIn('organization_id', $userTeamIds)
            ->orderBy('is_default', 'desc')
            ->orderBy('organization_id')
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function resetFrequencyOptions()
    {
        return ResetFrequency::getOptions();
    }

    #[Computed]
    public function nextNumberPreview(): string
    {
        if (! $this->organization_id || ! $this->prefix || ! $this->format_pattern) {
            return '';
        }

        try {
            // Create a temporary series to preview the next number
            $tempSeries = new InvoiceNumberingSeries([
                'prefix' => $this->prefix,
                'format_pattern' => $this->format_pattern,
                'current_number' => $this->current_number,
                'reset_frequency' => $this->reset_frequency,
                'last_reset_at' => now(),
            ]);

            return $this->numberingService->previewNextNumber($tempSeries);
        } catch (\Exception $e) {
            return 'Invalid format pattern';
        }
    }

    public function render()
    {
        return view('livewire.numbering-series-manager')
            ->layout('layouts.app', ['title' => 'Numbering Series Management']);
    }
}

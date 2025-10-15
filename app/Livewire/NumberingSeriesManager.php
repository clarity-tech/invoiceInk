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
        $series->update(['is_active' => !$series->is_active]);
        $status = $series->is_active ? 'activated' : 'deactivated';
        session()->flash('message', "Numbering series {$status} successfully!");
    }

    public function setAsDefault(InvoiceNumberingSeries $series): void
    {
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
        return Organization::with('primaryLocation')->get();
    }

    #[Computed]
    public function organizationLocations()
    {
        if (!$this->organization_id) {
            return collect();
        }

        return Location::where('locatable_type', Organization::class)
            ->where('locatable_id', $this->organization_id)
            ->get();
    }

    #[Computed]
    public function series()
    {
        return InvoiceNumberingSeries::with(['organization', 'location'])
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
        if (!$this->organization_id || !$this->prefix || !$this->format_pattern) {
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
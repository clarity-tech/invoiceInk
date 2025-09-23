<?php

namespace App\Livewire;

use App\Currency;
use App\Models\Location;
use App\Models\Organization;
use App\Rules\CurrencyCode;
use App\ValueObjects\EmailCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationManager extends Component
{
    use WithPagination;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    public array $emails = [''];

    #[Rule('required|string|max:255')]
    public string $location_name = '';

    #[Rule('nullable|string|max:50')]
    public string $gstin = '';

    #[Rule('required|string|max:500')]
    public string $address_line_1 = '';

    #[Rule('nullable|string|max:500')]
    public string $address_line_2 = '';

    #[Rule('required|string|max:100')]
    public string $city = '';

    #[Rule('required|string|max:100')]
    public string $state = '';

    #[Rule('required|string|max:100')]
    public string $country = '';

    #[Rule('required|string|max:20')]
    public string $postal_code = '';

    public string $currency = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public function addEmailField(): void
    {
        $this->emails[] = '';
    }

    public function removeEmailField(int $index): void
    {
        if (count($this->emails) > 1) {
            unset($this->emails[$index]);
            $this->emails = array_values($this->emails);
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Organization $organization): void
    {
        $organization->load('primaryLocation');

        $this->editingId = $organization->id;
        $this->name = $organization->name;
        $this->phone = $organization->phone ?? '';
        $this->emails = $organization->emails->toArray() ?: [''];
        $this->currency = $organization->currency?->value ?? Currency::default()->value;

        if ($organization->primaryLocation) {
            $this->location_name = $organization->primaryLocation->name;
            $this->gstin = $organization->primaryLocation->gstin ?? '';
            $this->address_line_1 = $organization->primaryLocation->address_line_1;
            $this->address_line_2 = $organization->primaryLocation->address_line_2 ?? '';
            $this->city = $organization->primaryLocation->city;
            $this->state = $organization->primaryLocation->state;
            $this->country = $organization->primaryLocation->country;
            $this->postal_code = $organization->primaryLocation->postal_code;
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'currency' => ['required', 'string', new CurrencyCode],
            'location_name' => 'required|string|max:255',
            'gstin' => 'nullable|string|max:50',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'emails' => 'required|array|min:1',
            'emails.*' => 'nullable|email',
        ]);

        $filteredEmails = array_filter($this->emails, fn ($email) => ! empty(trim($email)));

        if (empty($filteredEmails)) {
            $this->addError('emails.0', 'At least one email is required.');

            return;
        }

        $emailCollection = new EmailCollection($filteredEmails);

        if ($this->editingId) {
            $organization = Organization::findOrFail($this->editingId);
            $organization->update([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'currency' => $this->currency,
            ]);

            if ($organization->primaryLocation) {
                $organization->primaryLocation->update([
                    'name' => $this->location_name,
                    'gstin' => $this->gstin ?: null,
                    'address_line_1' => $this->address_line_1,
                    'address_line_2' => $this->address_line_2 ?: null,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'postal_code' => $this->postal_code,
                ]);
            }
        } else {
            $location = Location::create([
                'name' => $this->location_name,
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'locatable_type' => Organization::class,
                'locatable_id' => 0,
            ]);

            $organization = Organization::create([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'primary_location_id' => $location->id,
                'team_id' => auth()->user()?->currentTeam?->id,
                'currency' => $this->currency,
            ]);

            $location->update([
                'locatable_id' => $organization->id,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->resetPage();

        session()->flash('message', $this->editingId ? 'Organization updated successfully!' : 'Organization created successfully!');
    }

    public function delete(Organization $organization): void
    {
        // Handle foreign key constraint by setting primary_location_id to null first
        $organization->primary_location_id = null;
        $organization->save();

        // Then delete locations and organization
        $organization->locations()->delete();
        $organization->delete();

        $this->resetPage();
        session()->flash('message', 'Organization deleted successfully!');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = '';
        $this->emails = [''];
        $this->currency = Currency::default()->value;
        $this->location_name = '';
        $this->gstin = '';
        $this->address_line_1 = '';
        $this->address_line_2 = '';
        $this->city = '';
        $this->state = '';
        $this->country = '';
        $this->postal_code = '';
        $this->resetValidation();
    }

    #[Computed]
    public function organizations()
    {
        return Organization::with('primaryLocation')
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.organization-manager')
            ->layout('layouts.app', ['title' => 'Organizations']);
    }
}

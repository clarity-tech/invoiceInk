<?php

namespace App\Livewire;

use App\Currency;
use App\Models\Company;
use App\Models\Location;
use App\Rules\CurrencyCode;
use App\ValueObjects\EmailCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyManager extends Component
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

    public function edit(Company $company): void
    {
        $company->load('primaryLocation');

        $this->editingId = $company->id;
        $this->name = $company->name;
        $this->phone = $company->phone ?? '';
        $this->emails = $company->emails->toArray() ?: [''];
        $this->currency = $company->currency?->value ?? Currency::default()->value;

        if ($company->primaryLocation) {
            $this->location_name = $company->primaryLocation->name;
            $this->gstin = $company->primaryLocation->gstin ?? '';
            $this->address_line_1 = $company->primaryLocation->address_line_1;
            $this->address_line_2 = $company->primaryLocation->address_line_2 ?? '';
            $this->city = $company->primaryLocation->city;
            $this->state = $company->primaryLocation->state;
            $this->country = $company->primaryLocation->country;
            $this->postal_code = $company->primaryLocation->postal_code;
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
            $company = Company::findOrFail($this->editingId);
            $company->update([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'currency' => $this->currency,
            ]);

            if ($company->primaryLocation) {
                $company->primaryLocation->update([
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
                'locatable_type' => Company::class,
                'locatable_id' => 0,
            ]);

            $company = Company::create([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'primary_location_id' => $location->id,
                'team_id' => auth()->user()?->currentTeam?->id,
                'currency' => $this->currency,
            ]);

            $location->update([
                'locatable_id' => $company->id,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->resetPage();

        session()->flash('message', $this->editingId ? 'Company updated successfully!' : 'Company created successfully!');
    }

    public function delete(Company $company): void
    {
        // Handle foreign key constraint by setting primary_location_id to null first
        $company->primary_location_id = null;
        $company->save();

        // Then delete locations and company
        $company->locations()->delete();
        $company->delete();

        $this->resetPage();
        session()->flash('message', 'Company deleted successfully!');
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
    public function companies()
    {
        return Company::with('primaryLocation')
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.company-manager')
            ->layout('layouts.app', ['title' => 'Companies']);
    }
}

<?php

namespace App\Livewire;

use App\Enums\Country;
use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\ContactCollection;
use Illuminate\Validation\Rule as ValidationRule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerManager extends Component
{
    use WithPagination;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    #[Rule('required|string|max:3')]
    public string $currency = 'INR';

    public array $contacts = [['name' => '', 'email' => '']];

    #[Rule('nullable|string|max:255')]
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

    #[Rule('required|string|max:3')]
    public string $country = '';

    #[Rule('nullable|string|max:20')]
    public string $postal_code = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public function addContactField(): void
    {
        $this->contacts[] = ['name' => '', 'email' => ''];
    }

    public function removeContactField(int $index): void
    {
        if (count($this->contacts) > 1) {
            unset($this->contacts[$index]);
            $this->contacts = array_values($this->contacts);
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Customer $customer): void
    {
        $customer->load('primaryLocation');

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->currency = $customer->currency?->value ?? 'INR';
        $this->contacts = $customer->emails->toArray() ?: [['name' => '', 'email' => '']];

        if ($customer->primaryLocation) {
            $this->location_name = $customer->primaryLocation->name;
            $this->gstin = $customer->primaryLocation->gstin ?? '';
            $this->address_line_1 = $customer->primaryLocation->address_line_1;
            $this->address_line_2 = $customer->primaryLocation->address_line_2 ?? '';
            $this->city = $customer->primaryLocation->city;
            $this->state = $customer->primaryLocation->state;
            $this->country = $customer->primaryLocation->country;
            $this->postal_code = $customer->primaryLocation->postal_code;
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'currency' => ['required', 'string', ValidationRule::enum(\App\Currency::class)],
            'location_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|max:50',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => ['required', 'string', ValidationRule::enum(Country::class)],
            'postal_code' => 'nullable|string|max:20',
            'contacts' => 'required|array|min:1',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.email' => 'required|email|max:255',
        ]);

        $filteredContacts = array_filter($this->contacts, fn ($contact) => ! empty(trim($contact['email'])));

        if (empty($filteredContacts)) {
            $this->addError('contacts.0.email', 'At least one contact with email is required.');

            return;
        }

        $contactCollection = new ContactCollection($filteredContacts);

        if ($this->editingId) {
            $customer = Customer::findOrFail($this->editingId);
            $customer->update([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'currency' => $this->currency,
                'emails' => $contactCollection,
            ]);

            if ($customer->primaryLocation) {
                $customer->primaryLocation->update([
                    'name' => $this->location_name ?: ($this->name ? $this->name.' Office' : 'Main Office'),
                    'gstin' => $this->gstin ?: null,
                    'address_line_1' => $this->address_line_1,
                    'address_line_2' => $this->address_line_2 ?: null,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'postal_code' => $this->postal_code ?: null,
                ]);
            }
        } else {
            $location = Location::create([
                'name' => $this->location_name ?: ($this->name ? $this->name.' Office' : 'Main Office'),
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code ?: null,
                'locatable_type' => Customer::class,
                'locatable_id' => 0,
            ]);

            $customer = Customer::create([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'currency' => $this->currency,
                'emails' => $contactCollection,
                'primary_location_id' => $location->id,
                'organization_id' => auth()->user()?->currentTeam?->id,
            ]);

            $location->update([
                'locatable_id' => $customer->id,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->resetPage();

        session()->flash('message', $this->editingId ? 'Customer updated successfully!' : 'Customer created successfully!');
    }

    public function delete(Customer $customer): void
    {
        // Handle foreign key constraint by setting primary_location_id to null first
        $customer->primary_location_id = null;
        $customer->save();

        // Then delete locations and customer
        $customer->locations()->delete();
        $customer->delete();

        $this->resetPage();
        session()->flash('message', 'Customer deleted successfully!');
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
        $this->currency = 'INR';
        $this->contacts = [['name' => '', 'email' => '']];
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
    public function customers()
    {
        $query = Customer::with('primaryLocation');

        // Scope to current organization if user is authenticated
        if (auth()->check() && auth()->user()->currentTeam) {
            $query->where('organization_id', auth()->user()->currentTeam->id);
        }

        return $query->latest()
            ->orderBy('id') // Secondary sort for deterministic ordering
            ->paginate(10);
    }

    #[Computed]
    public function currentOrganization()
    {
        return auth()->user()?->currentTeam;
    }

    public function render()
    {
        return view('livewire.customer-manager')
            ->layout('layouts.app', ['title' => 'Customers']);
    }
}

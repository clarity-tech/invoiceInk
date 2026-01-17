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

    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['nullable', 'string', 'max:20'])]
    public string $phone = '';

    #[Rule(['required', 'string', 'max:3'])]
    public string $currency = 'INR';

    public array $contacts = [['name' => '', 'email' => '']];

    // Location modal fields
    #[Rule(['nullable', 'string', 'max:255'])]
    public string $location_name = '';

    #[Rule(['nullable', 'string', 'max:50'])]
    public string $gstin = '';

    #[Rule(['required', 'string', 'max:500'])]
    public string $address_line_1 = '';

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $address_line_2 = '';

    #[Rule(['required', 'string', 'max:100'])]
    public string $city = '';

    #[Rule(['required', 'string', 'max:100'])]
    public string $state = '';

    #[Rule(['required', 'string', 'max:3'])]
    public string $country = '';

    #[Rule(['nullable', 'string', 'max:20'])]
    public string $postal_code = '';

    public bool $is_primary = false;

    public bool $showForm = false;

    public bool $showLocationModal = false;

    public ?int $editingId = null;

    public ?int $editingLocationId = null;

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

    private function authorizeCustomerAccess(Customer $customer): void
    {
        abort_unless(
            auth()->check() && auth()->user()->allTeams()->contains('id', $customer->organization_id),
            403,
            __('messages.authorization.unauthorized_customer')
        );
    }

    public function edit(Customer $customer): void
    {
        $this->authorizeCustomerAccess($customer);
        $customer->load('locations');

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->currency = $customer->currency?->value ?? 'INR';
        $this->contacts = $customer->emails->toArray() ?: [['name' => '', 'email' => '']];

        $this->showForm = true;
    }

    public function addLocation(): void
    {
        if (! $this->editingId) {
            $this->addError('location', __('forms.validation.save_customer_first_locations'));

            return;
        }

        $this->resetLocationForm();
        $this->showLocationModal = true;
    }

    public function editLocation(Location $location): void
    {
        // Verify location belongs to the customer being edited
        if ($location->locatable_type !== Customer::class || $location->locatable_id !== $this->editingId) {
            abort(403, __('messages.authorization.unauthorized_location'));
        }

        // Verify user owns the customer
        $customer = Customer::findOrFail($this->editingId);
        $this->authorizeCustomerAccess($customer);

        $this->editingLocationId = $location->id;
        $this->location_name = $location->name;
        $this->gstin = $location->gstin ?? '';
        $this->address_line_1 = $location->address_line_1;
        $this->address_line_2 = $location->address_line_2 ?? '';
        $this->city = $location->city;
        $this->state = $location->state;
        $this->country = $location->country;
        $this->postal_code = $location->postal_code ?? '';

        $customer = Customer::find($this->editingId);
        $this->is_primary = $customer && $customer->primary_location_id === $location->id;

        $this->showLocationModal = true;
    }

    public function saveLocation(): void
    {
        if (! $this->editingId) {
            $this->addError('location', __('forms.validation.save_customer_first'));

            return;
        }

        // Verify user owns the customer
        $customer = Customer::findOrFail($this->editingId);
        $this->authorizeCustomerAccess($customer);

        $this->validate([
            'location_name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', ValidationRule::enum(Country::class)],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $customer = Customer::findOrFail($this->editingId);

        if ($this->editingLocationId) {
            // Update existing location
            $location = Location::findOrFail($this->editingLocationId);
            $location->update([
                'name' => $this->location_name,
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code ?: null,
            ]);
        } else {
            // Create new location
            $location = Location::create([
                'name' => $this->location_name,
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code ?: null,
                'locatable_type' => Customer::class,
                'locatable_id' => $customer->id,
            ]);
        }

        // Update primary location if marked as primary
        if ($this->is_primary) {
            $customer->update(['primary_location_id' => $location->id]);
        }

        // If this is the first location, make it primary automatically
        if ($customer->locations()->count() === 1 && ! $customer->primary_location_id) {
            $customer->update(['primary_location_id' => $location->id]);
        }

        $this->showLocationModal = false;
        $this->resetLocationForm();

        session()->flash('message', $this->editingLocationId ? __('messages.notifications.location_updated') : __('messages.notifications.location_added'));
    }

    public function deleteLocation(Location $location): void
    {
        // Verify location belongs to the customer being edited
        if ($location->locatable_type !== Customer::class || $location->locatable_id !== $this->editingId) {
            abort(403, __('messages.authorization.unauthorized_location'));
        }

        $customer = Customer::findOrFail($this->editingId);
        $this->authorizeCustomerAccess($customer);

        // Don't allow deleting the last location
        if ($customer->locations()->count() <= 1) {
            $this->addError('location', __('forms.validation.cannot_delete_last_location'));

            return;
        }

        // If deleting primary location, set another location as primary
        if ($customer->primary_location_id === $location->id) {
            $newPrimary = $customer->locations()->where('id', '!=', $location->id)->first();
            $customer->update(['primary_location_id' => $newPrimary->id]);
        }

        $location->delete();

        session()->flash('message', __('messages.notifications.location_deleted'));
    }

    public function setPrimaryLocation(Location $location): void
    {
        // Verify location belongs to the customer being edited
        if ($location->locatable_type !== Customer::class || $location->locatable_id !== $this->editingId) {
            abort(403, __('messages.authorization.unauthorized_location'));
        }

        $customer = Customer::findOrFail($this->editingId);
        $this->authorizeCustomerAccess($customer);
        $customer->update(['primary_location_id' => $location->id]);

        session()->flash('message', __('messages.notifications.primary_location_updated'));
    }

    public function cancelLocation(): void
    {
        $this->showLocationModal = false;
        $this->resetLocationForm();
    }

    private function resetLocationForm(): void
    {
        $this->editingLocationId = null;
        $this->location_name = '';
        $this->gstin = '';
        $this->address_line_1 = '';
        $this->address_line_2 = '';
        $this->city = '';
        $this->state = '';
        $this->country = '';
        $this->postal_code = '';
        $this->is_primary = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[+]?[\d\s\-().]+$/'],
            'currency' => ['required', 'string', ValidationRule::enum(\App\Currency::class)],
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['required', 'email', 'max:255'],
        ]);

        $filteredContacts = array_filter($this->contacts, fn ($contact) => ! empty(trim($contact['email'])));

        if (empty($filteredContacts)) {
            $this->addError('contacts.0.email', __('forms.validation.at_least_one_contact_email'));

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

            $this->resetForm();
            $this->showForm = false;
            $this->resetPage();

            session()->flash('message', __('messages.notifications.customer_updated'));
        } else {
            $customer = Customer::create([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'currency' => $this->currency,
                'emails' => $contactCollection,
                'organization_id' => auth()->user()?->currentTeam?->id,
            ]);

            // Stay in edit mode to allow adding locations
            $this->editingId = $customer->id;

            session()->flash('message', __('messages.notifications.customer_created_add_location'));
        }
    }

    public function delete(Customer $customer): void
    {
        $this->authorizeCustomerAccess($customer);

        // Handle foreign key constraint by setting primary_location_id to null first
        $customer->primary_location_id = null;
        $customer->save();

        // Then delete locations and customer
        $customer->locations()->delete();
        $customer->delete();

        $this->resetPage();
        session()->flash('message', __('messages.notifications.customer_deleted'));
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
        $this->resetValidation();
    }

    #[Computed]
    public function currentCustomerLocations()
    {
        if (! $this->editingId) {
            return collect();
        }

        $customer = Customer::with('locations')->find($this->editingId);

        return $customer ? $customer->locations : collect();
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

<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\EmailCollection;
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

    #[Rule('required|array|min:1')]
    #[Rule('emails.*', 'required|email')]
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

    public function edit(Customer $customer): void
    {
        $customer->load('primaryLocation');
        
        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->emails = $customer->emails->toArray() ?: [''];
        
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
        $this->validate();

        $filteredEmails = array_filter($this->emails, fn($email) => !empty(trim($email)));
        
        if (empty($filteredEmails)) {
            $this->addError('emails.0', 'At least one email is required.');
            return;
        }

        $emailCollection = new EmailCollection($filteredEmails);

        if ($this->editingId) {
            $customer = Customer::findOrFail($this->editingId);
            $customer->update([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
            ]);

            if ($customer->primaryLocation) {
                $customer->primaryLocation->update([
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
                'locatable_type' => Customer::class,
                'locatable_id' => 0,
            ]);

            $customer = Customer::create([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'primary_location_id' => $location->id,
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
        $this->emails = [''];
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
        return Customer::with('primaryLocation')
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.customer-manager')
            ->layout('layouts.app', ['title' => 'Customers']);
    }
}

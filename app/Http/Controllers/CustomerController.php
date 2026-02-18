<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Enums\Country;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Location;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = Customer::with(['primaryLocation', 'locations'])
            ->withCount('locations')
            ->latest()
            ->orderBy('id')
            ->paginate(10);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'currencies' => Currency::options(),
            'countries' => Country::options(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $filteredContacts = array_filter($validated['contacts'], fn ($c) => ! empty(trim($c['email'])));

        if (empty($filteredContacts)) {
            return back()->withErrors(['contacts.0.email' => __('forms.validation.at_least_one_contact_email')]);
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'currency' => $validated['currency'],
            'emails' => new ContactCollection($filteredContacts),
            'organization_id' => $request->user()->currentTeam->id,
        ]);

        return back()->with('success', __('messages.notifications.customer_created_add_location'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validated();

        $filteredContacts = array_filter($validated['contacts'], fn ($c) => ! empty(trim($c['email'])));

        if (empty($filteredContacts)) {
            return back()->withErrors(['contacts.0.email' => __('forms.validation.at_least_one_contact_email')]);
        }

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'currency' => $validated['currency'],
            'emails' => new ContactCollection($filteredContacts),
        ]);

        return back()->with('success', __('messages.notifications.customer_updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $invoiceCount = Invoice::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->count();

        if ($invoiceCount > 0) {
            return back()->with('error', "Cannot delete customer with {$invoiceCount} existing ".Str::plural('invoice', $invoiceCount).'. Delete or reassign the invoices first.');
        }

        $customer->primary_location_id = null;
        $customer->save();
        $customer->locations()->delete();
        $customer->delete();

        return back()->with('success', __('messages.notifications.customer_deleted'));
    }

    public function storeLocation(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', Rule::enum(Country::class)],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $location = Location::create([
            'name' => $validated['name'],
            'gstin' => ($validated['gstin'] ?? null) ?: null,
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => ($validated['address_line_2'] ?? null) ?: null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'postal_code' => ($validated['postal_code'] ?? '') ?: '',
            'locatable_type' => Customer::class,
            'locatable_id' => $customer->id,
        ]);

        // If this is the first location, make it primary automatically
        if ($customer->locations()->count() === 1 || ! $customer->primary_location_id) {
            $customer->update(['primary_location_id' => $location->id]);
        }

        return back()->with('success', __('messages.notifications.location_added'));
    }

    public function updateLocation(Request $request, Customer $customer, Location $location): RedirectResponse
    {
        $this->authorizeCustomer($customer);
        $this->authorizeLocation($customer, $location);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', Rule::enum(Country::class)],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $location->update([
            'name' => $validated['name'],
            'gstin' => ($validated['gstin'] ?? null) ?: null,
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => ($validated['address_line_2'] ?? null) ?: null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'postal_code' => ($validated['postal_code'] ?? '') ?: '',
        ]);

        return back()->with('success', __('messages.notifications.location_updated'));
    }

    public function destroyLocation(Request $request, Customer $customer, Location $location): RedirectResponse
    {
        $this->authorizeCustomer($customer);
        $this->authorizeLocation($customer, $location);

        if ($customer->locations()->count() <= 1) {
            return back()->with('error', __('forms.validation.cannot_delete_last_location'));
        }

        if ($customer->primary_location_id === $location->id) {
            $newPrimary = $customer->locations()->where('id', '!=', $location->id)->first();
            $customer->update(['primary_location_id' => $newPrimary->id]);
        }

        $location->delete();

        return back()->with('success', __('messages.notifications.location_deleted'));
    }

    public function setPrimaryLocation(Request $request, Customer $customer, Location $location): RedirectResponse
    {
        $this->authorizeCustomer($customer);
        $this->authorizeLocation($customer, $location);

        $customer->update(['primary_location_id' => $location->id]);

        return back()->with('success', __('messages.notifications.primary_location_updated'));
    }

    private function authorizeCustomer(Customer $customer): void
    {
        abort_unless(
            auth()->user()->allTeams()->contains('id', $customer->organization_id),
            403
        );
    }

    private function authorizeLocation(Customer $customer, Location $location): void
    {
        abort_unless(
            $location->locatable_type === Customer::class && $location->locatable_id === $customer->id,
            403,
            __('messages.authorization.unauthorized_location')
        );
    }
}

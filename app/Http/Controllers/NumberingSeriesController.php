<?php

namespace App\Http\Controllers;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NumberingSeriesController extends Controller
{
    public function index(Request $request): Response
    {
        $userTeamIds = $request->user()->allTeams()->pluck('id');

        $series = InvoiceNumberingSeries::with(['organization', 'location'])
            ->whereIn('organization_id', $userTeamIds)
            ->orderBy('is_default', 'desc')
            ->orderBy('organization_id')
            ->orderBy('name')
            ->paginate(10);

        $organizations = Organization::with('primaryLocation')
            ->whereIn('id', $userTeamIds)
            ->get();

        return Inertia::render('NumberingSeries/Index', [
            'series' => $series,
            'organizations' => $organizations,
            'resetFrequencyOptions' => ResetFrequency::getOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:teams,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:100'],
            'prefix' => ['required', 'string', 'max:20'],
            'format_pattern' => ['required', 'string', 'max:100'],
            'current_number' => ['required', 'integer', 'min:0'],
            'reset_frequency' => ['required', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        // Authorization: user must have access to the organization
        abort_unless(
            $request->user()->allTeams()->contains('id', $validated['organization_id']),
            403
        );

        // Verify location belongs to organization
        if (! empty($validated['location_id'])) {
            $locationBelongsToOrg = Location::where('id', $validated['location_id'])
                ->where('locatable_type', Organization::class)
                ->where('locatable_id', $validated['organization_id'])
                ->exists();

            if (! $locationBelongsToOrg) {
                return back()->withErrors(['location_id' => 'Location must belong to the selected organization.']);
            }
        }

        InvoiceNumberingSeries::create($validated);

        return back()->with('success', 'Numbering series created successfully.');
    }

    public function update(Request $request, InvoiceNumberingSeries $series): RedirectResponse
    {
        $this->authorize('update', $series);

        $validated = $request->validate([
            'organization_id' => ['required', 'exists:teams,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:100'],
            'prefix' => ['required', 'string', 'max:20'],
            'format_pattern' => ['required', 'string', 'max:100'],
            'current_number' => ['required', 'integer', 'min:0'],
            'reset_frequency' => ['required', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        abort_unless(
            $request->user()->allTeams()->contains('id', $validated['organization_id']),
            403
        );

        $series->update($validated);

        return back()->with('success', 'Numbering series updated successfully.');
    }

    public function destroy(InvoiceNumberingSeries $series): RedirectResponse
    {
        $this->authorize('delete', $series);

        if ($series->invoices()->exists()) {
            return back()->with('error', 'Cannot delete numbering series that has invoices.');
        }

        $series->delete();

        return back()->with('success', 'Numbering series deleted successfully.');
    }

    public function toggleActive(InvoiceNumberingSeries $series): RedirectResponse
    {
        $this->authorize('update', $series);

        $series->update(['is_active' => ! $series->is_active]);

        $status = $series->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Numbering series {$status} successfully.");
    }

    public function setDefault(InvoiceNumberingSeries $series): RedirectResponse
    {
        $this->authorize('update', $series);

        InvoiceNumberingSeries::where('organization_id', $series->organization_id)
            ->update(['is_default' => false]);

        $series->update(['is_default' => true]);

        return back()->with('success', 'Default series updated successfully.');
    }

    public function preview(Request $request, InvoiceNumberingServiceInterface $numberingService): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:teams,id'],
            'prefix' => ['required', 'string', 'max:20'],
            'format_pattern' => ['required', 'string', 'max:100'],
            'current_number' => ['required', 'integer', 'min:0'],
            'reset_frequency' => ['required', 'string'],
        ]);

        abort_unless(
            $request->user()->allTeams()->contains('id', $validated['organization_id']),
            403
        );

        try {
            $organization = Organization::find($validated['organization_id']);

            $tempSeries = new InvoiceNumberingSeries([
                'organization_id' => $validated['organization_id'],
                'prefix' => $validated['prefix'],
                'format_pattern' => $validated['format_pattern'],
                'current_number' => $validated['current_number'],
                'reset_frequency' => $validated['reset_frequency'],
                'last_reset_at' => now(),
            ]);

            if ($organization) {
                $tempSeries->setRelation('organization', $organization);
            }

            $preview = $numberingService->previewNextNumber($tempSeries);

            return response()->json(['preview' => $preview]);
        } catch (\Exception $e) {
            return response()->json(['preview' => 'Invalid format pattern'], 422);
        }
    }
}
